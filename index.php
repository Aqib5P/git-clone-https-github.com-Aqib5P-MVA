<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

function decodeResponse(?string $response): ?array
{
    if (!is_string($response) || $response === '') {
        return null;
    }

    $trimmed = trim($response);
    if ($trimmed === '') {
        return null;
    }

    $decoded = json_decode($trimmed, true);
    if (is_array($decoded)) {
        return $decoded;
    }

    if (str_starts_with($trimmed, '<')) {
        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($trimmed, 'SimpleXMLElement', LIBXML_NOCDATA);
        if ($xml !== false) {
            $xmlJson = json_encode($xml);
            if ($xmlJson !== false) {
                $xmlDecoded = json_decode($xmlJson, true);
                if (is_array($xmlDecoded)) {
                    libxml_clear_errors();
                    return $xmlDecoded;
                }
            }
        }
        libxml_clear_errors();
    }

    $fallback = [];
    parse_str($response, $fallback);
    return !empty($fallback) ? $fallback : null;
}

function logToGoogle(string $googleWebhook, array $logData): void
{
    $ch = curl_init($googleWebhook);
    curl_setopt_array($ch, [
        CURLOPT_POST => 1,
        CURLOPT_POSTFIELDS => json_encode($logData),
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_RETURNTRANSFER => true,
    ]);
    curl_exec($ch);
    curl_close($ch);
}

function maskValue(string $value): string
{
    $length = strlen($value);
    if ($length <= 4) {
        return str_repeat('*', $length);
    }

    return substr($value, 0, 2) . str_repeat('*', $length - 4) . substr($value, -2);
}

function payloadMaskKeys(): array
{
    return [
        'phone_number',
        'caller_id',
        'CID',
        'trusted_form_cert_id',
        'trusted_form_cert_url',
        'Terminating_Phone',
        'Terminating_Phone.',
        'API_Key',
        'API Key',
        'email',
        'Email',
    ];
}

function maskPayload(array $payload, array $keysToMask): array
{
    foreach ($payload as $key => $value) {
        if (is_array($value)) {
            $payload[$key] = maskPayload($value, $keysToMask);
            continue;
        }

        if (in_array($key, $keysToMask, true) && is_string($value)) {
            $payload[$key] = maskValue($value);
        }
    }

    return $payload;
}

function buildPayloadBody(string $format, array $fields): string
{
    if ($format === 'json') {
        $encoded = json_encode($fields);
        return $encoded === false ? '' : $encoded;
    }

    return http_build_query($fields);
}

function flattenArray(array $data, string $prefix): array
{
    $flat = [];
    foreach ($data as $key => $value) {
        $safeKey = is_int($key) ? (string) $key : $key;
        $fullKey = $prefix . $safeKey;
        if (is_array($value)) {
            $flat = array_merge($flat, flattenArray($value, $fullKey . '_'));
            continue;
        }

        if (is_bool($value)) {
            $flat[$fullKey] = $value ? 'true' : 'false';
        } elseif ($value === null) {
            $flat[$fullKey] = '';
        } else {
            $flat[$fullKey] = (string) $value;
        }
    }

    return $flat;
}

function unwrapResponse(array $decoded, string $wrapperKey): array
{
    if (isset($decoded[$wrapperKey]) && is_array($decoded[$wrapperKey])) {
        return $decoded[$wrapperKey];
    }

    return $decoded;
}

function buildGoogleLogData(
    string $phone,
    string $buyer,
    string $status,
    $bid,
    $minDuration,
    string $rejectReason,
    string $ip,
    ?array $decoded,
    ?string $rawResponse,
    string $payloadFormat,
    string $endpoint,
    array $payloadFields,
    bool $maskSensitive,
    bool $includePayloadColumns
): array {
    $safeFields = $maskSensitive ? maskPayload($payloadFields, payloadMaskKeys()) : $payloadFields;
    $payloadBody = buildPayloadBody($payloadFormat, $safeFields);
    $payloadColumns = $includePayloadColumns ? flattenArray($safeFields, 'payload_') : [];
    $responseColumns = $includePayloadColumns && is_array($decoded) ? flattenArray($decoded, 'response_') : [];

    $logData = [
        "number" => $phone,
        "buyer" => $buyer,
        "status" => $status,
        "payout" => $bid,
        "duration" => $minDuration,
        "reason" => $rejectReason,
        "ip" => $ip,
        "api_response" => is_array($decoded) ? json_encode($decoded, JSON_PRETTY_PRINT) : $rawResponse,
    ];

    if ($includePayloadColumns) {
        $logData["payload_format"] = $payloadFormat;
        $logData["payload_endpoint"] = $endpoint;
        $logData["payload_body"] = $payloadBody;
    }

    return array_merge($logData, $payloadColumns, $responseColumns);
}

function logBuyerPayload(
    bool $enabled,
    string $path,
    string $buyer,
    string $endpoint,
    string $format,
    array $fields,
    bool $maskSensitive
): void {
    if (!$enabled) {
        return;
    }

    $safeFields = $maskSensitive
        ? maskPayload($fields, payloadMaskKeys())
        : $fields;
    $payloadBody = buildPayloadBody($format, $safeFields);

    $entry = [
        'time' => date('c'),
        'buyer' => $buyer,
        'endpoint' => $endpoint,
        'format' => $format,
        'fields' => $safeFields,
        'payload_body' => $payloadBody,
    ];

    $line = json_encode($entry);
    if ($line === false) {
        $line = json_encode([
            'time' => date('c'),
            'buyer' => $buyer,
            'endpoint' => $endpoint,
            'format' => $format,
            'fields' => 'json_encode_failed',
        ]);
    }

    if (@file_put_contents($path, $line . PHP_EOL, FILE_APPEND | LOCK_EX) === false) {
        error_log('Failed to write payload log to ' . $path);
    }
}

$logPayloads = getenv('LOG_BUYER_PAYLOADS') === '1';
$payloadLogPath = getenv('BUYER_PAYLOAD_LOG') ?: '/tmp/buyer_payloads.log';
$maskPayloads = getenv('MASK_BUYER_PAYLOADS') === '1';
$logGooglePayloads = getenv('LOG_GOOGLE_PAYLOADS') !== '0';

$results = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $phone = trim($_POST['phone_number'] ?? '');
    $caller_id_input = trim($_POST['caller_id'] ?? '');
    $cid_input = trim($_POST['CID'] ?? '');
    if ($phone === '' && $caller_id_input !== '') {
        $phone = $caller_id_input;
    }
    if ($phone === '' && $cid_input !== '') {
        $phone = $cid_input;
    }
    $zip = trim($_POST['zip_code'] ?? '');
    $state = trim($_POST['state'] ?? '');
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $trusted_form = trim($_POST['trusted_form_cert_id'] ?? '');
    $have_attorney = trim($_POST['have_attorney'] ?? '');
    $caller_id = $phone !== '' ? $phone : $caller_id_input;
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';

    $googleWebhook = "https://script.google.com/macros/s/AKfycbzA8zl5bkPPqFVcLi0GzwsLfLn27CIdXBe5apoa_A8JoHVnMrS9jgUR13Y7WhQUwCKnWQ/exec";
    $d31ApiKey = getenv('D31_API_KEY') ?: '3fadcec3bd4409b070d6a0cabfed15ad249786ba65ad6292500ef714982da2dd';
    $d31Src = getenv('D31_SRC') ?: 'AA_IPXQ_RTB_41';
    $d31PingEndpoint = getenv('D31_PING_ENDPOINT') ?: 'https://backping.leadportal.com/new_api/api.php';
    $d31PostEndpoint = getenv('D31_POST_ENDPOINT') ?: $d31PingEndpoint;

    // =======================
    // Buyers Array
    // =======================
    $buyers = [
        'D4' => ['type' => 'json', 'endpoint' => 'https://rtb.ringba.com/v1/production/694b1593ec8e48a1ba53587be6049296.json', 'fields' => ['exposeCallerId' => 'yes', 'call_type' => 'o', 'CID' => $phone, 'zipcode' => $zip]],
        'D4One' => ['type' => 'json', 'endpoint' => 'https://rtb.ringba.com/v1/production/f085f7edfa444d3ebc677bbf9383d7ca.json', 'fields' => ['exposeCallerId' => 'yes', 'call_type' => 'o', 'CID' => $phone, 'zipcode' => $zip]],
        'D5' => ['type' => 'json', 'endpoint' => 'https://rtb.ringba.com/v1/production/49fa2bb19e3a493da406c1b7f53293bb.json', 'fields' => ['exposeCallerId' => 'yes', 'source' => 'paid-search', 'CID' => $phone, 'zipcode' => $zip]],
        'D9' => ['type' => 'json', 'endpoint' => 'https://rtb.ringba.com/v1/production/c291358f01414e1f9508cf80cbbbb010.json', 'fields' => ['exposeCallerId' => 'yes', 'source' => 'paid-search', 'CID' => $phone, 'zipcode' => $zip]],
        'D22' => ['type' => 'json', 'endpoint' => 'https://rtb.ringba.com/v1/production/af5459c5676840579aca82d260857b32.json', 'fields' => ['exposeCallerId' => 'yes', 'CID' => $phone, 'zipcode' => $zip]],
        'D20' => [
            'type' => 'form',
            'endpoint' => 'https://growmyfirmonline.leadspediatrack.com/call-preping.do',
            'fields' => [
                'lp_campaign_id' => '68cd891e54a5d',
                'lp_campaign_key' => '4nFvcHX2JNWmDbxqBkhg',
                'first_name' => $first_name,
                'last_name' => $last_name,
                'zip_code' => $zip,
                'phone_number' => $phone,
                'trusted_form_cert_id' => $trusted_form,
                'caller_id' => $phone,
            ],
        ],
        'D29 ping' => [
            'type' => 'form',
            'endpoint' => 'https://assured-health.trackdrive.com/api/v1/inbound_webhooks/ping/check_for_the_available_buyers_on_mva_transfers',
            'fields' => [
                'trackdrive_number' => '+18445189671',
                'traffic_source_id' => '3055',
                'first_name' => $first_name,
                'last_name' => $last_name,
                'ZIP' => $zip,
                'caller_id' => $phone,
                'trusted_form_cert_url' => $trusted_form,
            ],
        ],
        'D29 post' => [
            'type' => 'form',
            'endpoint' => 'https://assured-health.trackdrive.com/api/v1/inbound_webhooks/post/check_for_the_available_buyers_on_mva_transfers',
            'fields' => [
                'trackdrive_number' => '+18445189671',
                'traffic_source_id' => '3055',
                'first_name' => $first_name,
                'last_name' => $last_name,
                'ZIP' => $zip,
                'caller_id' => $phone,
                'trusted_form_cert_url' => $trusted_form,
            ],
        ],
        'D12 ping' => [
            'type' => 'form',
            'endpoint' => 'https://horizons-law-consultants.trackdrive.com/api/v1/inbound_webhooks/ping/check_for_available_mva_cpl_buyers',
            'fields' => [
                'trackdrive_number' => '+18772834769',
                'traffic_source_id' => '1002',
                'caller_id' => $caller_id,
                'zip' => $zip,
                'state' => $state,
                'trusted_form_cert_url' => $trusted_form,
                'have_attorney' => $have_attorney,
                'first_name' => $first_name,
                'last_name' => $last_name,
                'email' => $email,
            ],
        ],
        'D12 post' => [
            'type' => 'form',
            'endpoint' => 'https://horizons-law-consultants.trackdrive.com/api/v1/inbound_webhooks/post/check_for_available_mva_cpl_buyers',
            'fields' => [
                'trackdrive_number' => '+18772834769',
                'traffic_source_id' => '1002',
                'caller_id' => $caller_id,
                'zip' => $zip,
                'state' => $state,
                'trusted_form_cert_url' => $trusted_form,
                'have_attorney' => $have_attorney,
                'first_name' => $first_name,
                'last_name' => $last_name,
                'email' => $email,
            ],
        ],
        'D31 ping' => [
            'type' => 'form',
            'endpoint' => $d31PingEndpoint,
            'fields' => [
                'Return_Best_Price' => '1',
                'API_Key' => $d31ApiKey,
                'SRC' => $d31Src,
                'API_Action' => 'iprSubmitLead',
                'Mode' => 'ping',
                'Return_Min_Duration' => '1',
                'TYPE' => '9',
                'Terminating_Phone' => $phone,
            ],
        ],
        'D31 post' => [
            'type' => 'form',
            'endpoint' => $d31PostEndpoint,
            'fields' => [
                'Return_Best_Price' => '1',
                'API_Key' => $d31ApiKey,
                'SRC' => $d31Src,
                'API_Action' => 'iprSubmitLead',
                'Mode' => 'post',
                'Return_Min_Duration' => '1',
                'TYPE' => '9',
                'Terminating_Phone' => $phone,
            ],
        ],
        'D25' => [
            'type' => 'form',
            'endpoint' => 'https://trueblue.leadspediatrack.com/call-preping.do',
            'fields' => [
                'lp_campaign_id' => '6937514e0c245',
                'lp_campaign_key' => 'BRLfV3z4Z9Hc7TgkWyKF',
                'first_name' => $first_name,
                'last_name' => $last_name,
                'zip_code' => $zip,
                'phone_number' => $phone,
                'trusted_form_cert_id' => $trusted_form,
                'caller_id' => $phone,
            ],
        ],
    ];

    // =======================
    // D29 Ping / Post Execution (single flow)
    // =======================
    $ping_id = null;
    $ping_response = null;
    $ping_error = null;
    $ping_data = null;
    $d29_ping_status = 'Ping';
    $d29_ping_reason = '';
    $post_response = null;
    $post_error = null;

    if (!empty($buyers['D29 ping'])) {
        logBuyerPayload(
            $logPayloads,
            $payloadLogPath,
            'D29 ping',
            $buyers['D29 ping']['endpoint'],
            'json',
            $buyers['D29 ping']['fields'],
            $maskPayloads
        );

        $ch = curl_init($buyers['D29 ping']['endpoint']);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_POSTFIELDS => json_encode($buyers['D29 ping']['fields']),
            CURLOPT_TIMEOUT => 12,
        ]);
        $ping_response = curl_exec($ch);
        $ping_error = curl_error($ch);
        curl_close($ch);

        $ping_data = decodeResponse($ping_response);
        if (is_array($ping_data)) {
            $ping_id = $ping_data['try_all_buyers_ping_id']
                ?? $ping_data['try_all_buyers']['ping_id']
                ?? ($ping_data['buyers'][0]['ping_id'] ?? null);
        }

        if ($ping_error || !$ping_response) {
            $d29_ping_status = 'Error';
            $d29_ping_reason = $ping_error ?: 'No response received';
        } elseif (is_array($ping_data)) {
            if (array_key_exists('status', $ping_data)) {
                $d29_ping_status = 'Ping ' . (string) $ping_data['status'];
            } elseif (array_key_exists('success', $ping_data)) {
                $d29_ping_status = $ping_data['success'] ? 'Ping Accepted' : 'Ping Rejected';
            } else {
                $d29_ping_status = 'Ping Response';
            }
        } else {
            $d29_ping_status = 'Invalid Response';
            $d29_ping_reason = is_string($ping_response) ? substr($ping_response, 0, 80) : 'Empty response';
        }

        $d29_ping_log = buildGoogleLogData(
            $phone,
            'D29 ping',
            $d29_ping_status,
            0,
            is_array($ping_data) ? ($ping_data['ping_ids_expiry_in_seconds'] ?? 'N/A') : 'N/A',
            $d29_ping_reason,
            $ip,
            is_array($ping_data) ? $ping_data : null,
            $ping_response,
            'json',
            $buyers['D29 ping']['endpoint'],
            $buyers['D29 ping']['fields'],
            $maskPayloads,
            $logGooglePayloads
        );
        logToGoogle($googleWebhook, $d29_ping_log);

        if (!$ping_id) {
            error_log('D29 Ping failed: ' . ($ping_error ?: $ping_response));
        }
    }

    if ($ping_id && !empty($buyers['D29 post'])) {
        $buyers['D29 post']['fields']['ping_id'] = $ping_id;

        logBuyerPayload(
            $logPayloads,
            $payloadLogPath,
            'D29 post',
            $buyers['D29 post']['endpoint'],
            'json',
            $buyers['D29 post']['fields'],
            $maskPayloads
        );

        $ch = curl_init($buyers['D29 post']['endpoint']);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_POSTFIELDS => json_encode($buyers['D29 post']['fields']),
            CURLOPT_TIMEOUT => 12,
        ]);
        $post_response = curl_exec($ch);
        $post_error = curl_error($ch);
        curl_close($ch);
    }

    // =======================
    // Main Buyers Loop
    // =======================
    foreach ($buyers as $buyer => $info) {
        if (in_array($buyer, ['D29 ping', 'D29 post', 'D12 ping', 'D12 post', 'D31 ping', 'D31 post'], true)) {
            continue;
        }

        $payloadFormat = $info['type'] === 'json' ? 'json' : 'form';
        logBuyerPayload(
            $logPayloads,
            $payloadLogPath,
            $buyer,
            $info['endpoint'],
            $payloadFormat,
            $info['fields'],
            $maskPayloads
        );

        if ($info['type'] === 'json' && array_key_exists('CID', $info['fields'])) {
            $cidValue = trim((string) $info['fields']['CID']);
            if ($cidValue === '') {
                $status = 'Rejected — Missing CID';
                $rejectReason = 'Missing CID';
                $bid = 0;
                $expire = 'N/A';
                $phoneNumber = 'N/A';
                $minDuration = 'N/A';

                $logData = buildGoogleLogData(
                    $phone,
                    $buyer,
                    $status,
                    $bid,
                    $minDuration,
                    $rejectReason,
                    $ip,
                    null,
                    null,
                    $payloadFormat,
                    $info['endpoint'],
                    $info['fields'],
                    $maskPayloads,
                    $logGooglePayloads
                );
                logToGoogle($googleWebhook, $logData);

                $results[] = [
                    'buyer' => $buyer,
                    'status' => $status,
                    'bid' => $bid,
                    'expire' => $expire,
                    'phoneNumber' => $phoneNumber,
                    'minDuration' => $minDuration,
                ];
                continue;
            }
        }

        $ch = curl_init($info['endpoint']);

        if ($info['type'] === 'json') {
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode($info['fields']),
                CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
                CURLOPT_TIMEOUT => 12,
            ]);
        } else {
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => http_build_query($info['fields']),
                CURLOPT_TIMEOUT => 12,
            ]);
        }

        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);

        $decoded = decodeResponse($response);

        $bid = 0;
        $expire = 'N/A';
        $phoneNumber = 'N/A';
        $minDuration = 'N/A';
        $status = 'Rejected';
        $rejectReason = '';

        if ($error || !$response) {
            $status = "Error";
            $rejectReason = $error ?: "No response received";
        } elseif (is_array($decoded)) {

            // ===== Ringba / normal JSON buyers =====
            if ($info['type'] === 'json' && $buyer !== 'D20' && $buyer !== 'D25') {
                $bid = $decoded['bidAmount'] ?? $decoded['bidPrice'] ?? 0;
                $expire = $decoded['expireInSeconds'] ?? 'N/A';
                $phoneNumber = $decoded['phoneNumber'] ?? 'N/A';
                $rejectReason = $decoded['rejectReason'] ?? '';
                $minDuration = 'N/A';

                if (!empty($rejectReason)) {
                    $status = "Rejected — $rejectReason";
                    $bid = 0;
                } else {
                    $status = 'Accepted';
                    if (!empty($decoded['bidTerms'])) {
                        foreach ($decoded['bidTerms'] as $term) {
                            if (isset($term['callMinDuration'])) {
                                $minDuration = $term['callMinDuration'];
                                break;
                            }
                        }
                    }
                }
            }

            // ===== D20 LeadsPedia =====
            elseif (in_array($buyer, ['D20', 'D25'], true)) {
                $bid = $decoded['payout'] ?? 0;
                $status = $decoded['status'] ?? 'Unknown';
                $phoneNumber = $decoded['number'] ?? 'N/A';
                $minDuration = $decoded['duration'] ?? 'N/A';
                $rejectReason = $decoded['message'] ?? '';

                if (strtolower((string) $status) === 'accepted') {
                    $status = 'Accepted';
                } else {
                    $status = "Rejected — " . ($rejectReason ?: 'Unknown reason');
                    $bid = 0;
                }
            }

        } else {
            $status = 'Invalid Response';
            $rejectReason = is_string($response) ? substr($response, 0, 80) : 'Empty response';
        }

        $logData = buildGoogleLogData(
            $phone,
            $buyer,
            $status,
            $bid,
            $minDuration,
            $rejectReason,
            $ip,
            is_array($decoded) ? $decoded : null,
            $response,
            $payloadFormat,
            $info['endpoint'],
            $info['fields'],
            $maskPayloads,
            $logGooglePayloads
        );

        logToGoogle($googleWebhook, $logData);

        // Add to results
        if ($bid >= 20 && $status === 'Accepted') {
            $results[] = [
                'buyer' => $buyer,
                'status' => 'Accepted',
                'bid' => $bid,
                'expire' => $expire,
                'phoneNumber' => $phoneNumber,
                'minDuration' => $minDuration,
            ];
        } else {
            $reasonText = $rejectReason ?: ($bid < 20 ? 'Bid too low' : 'No reason given');
            $results[] = [
                'buyer' => $buyer,
                'status' => "Rejected — $reasonText",
                'bid' => $bid,
                'expire' => $expire,
                'phoneNumber' => $phoneNumber,
                'minDuration' => $minDuration,
            ];
        }
    }

    // =======================
    // D29 Post Result Handling
    // =======================
    $d29_bid = 0;
    $d29_expire = 'N/A';
    $d29_phoneNumber = 'N/A';
    $d29_minDuration = 'N/A';
    $d29_status = 'Rejected';
    $d29_rejectReason = '';
    $d29_decoded = null;

    if (!$ping_id) {
        $d29_status = 'Rejected — No ping_id returned';
        $d29_rejectReason = 'No ping_id returned';
    } elseif ($post_error || !$post_response) {
        $d29_status = 'Error';
        $d29_rejectReason = $post_error ?: 'No response received';
    } else {
        $d29_decoded = decodeResponse($post_response);
        if (is_array($d29_decoded)) {
            $d29_status = $d29_decoded['status'] ?? 'Rejected';
            $d29_minDuration = $d29_decoded['ping_ids_expiry_in_seconds'] ?? 'N/A';
            $d29_phoneNumber = $d29_decoded['forwarding_number'] ?? 'N/A';
            if (!empty($d29_decoded['errors'])) {
                if (is_array($d29_decoded['errors'])) {
                    $flatErrors = [];
                    foreach ($d29_decoded['errors'] as $err) {
                        if (is_array($err)) {
                            $flatErrors[] = implode(', ', $err);
                        } else {
                            $flatErrors[] = $err;
                        }
                    }
                    $d29_rejectReason = implode(' | ', $flatErrors);
                } else {
                    $d29_rejectReason = $d29_decoded['errors'];
                }
            } else {
                $d29_rejectReason = '';
            }

            if (strtolower((string) $d29_status) === 'accepted') {
                $d29_status = 'Accepted';
            } else {
                $d29_status = 'Rejected — ' . ($d29_rejectReason ?: 'Unknown reason');
            }
        } else {
            $d29_status = 'Invalid Response';
            $d29_rejectReason = is_string($post_response) ? substr($post_response, 0, 80) : 'Empty response';
        }
    }

    $d29_logData = buildGoogleLogData(
        $phone,
        'D29 post',
        $d29_status,
        $d29_bid,
        $d29_minDuration,
        $d29_rejectReason,
        $ip,
        is_array($d29_decoded) ? $d29_decoded : null,
        $post_response,
        'json',
        $buyers['D29 post']['endpoint'],
        $buyers['D29 post']['fields'],
        $maskPayloads,
        $logGooglePayloads
    );
    logToGoogle($googleWebhook, $d29_logData);

    $d29_reasonText = $d29_rejectReason ?: 'No reason given';
    $results[] = [
        'buyer' => 'D29 post',
        'status' => $d29_status ?: "Rejected — $d29_reasonText",
        'bid' => $d29_bid,
        'expire' => $d29_expire,
        'phoneNumber' => $d29_phoneNumber,
        'minDuration' => $d29_minDuration,
    ];

    // =======================
    // D12 Ping / Post Execution (single flow)
    // =======================
    $d12_ping_response = null;
    $d12_ping_error = null;
    $d12_ping_decoded = null;
    $d12_ping_data = null;
    $d12_post_response = null;
    $d12_post_error = null;
    $d12_post_decoded = null;
    $d12_post_data = null;
    $d12_ping_id = null;
    $d12_bid = 0;
    $d12_minDuration = 'N/A';
    $d12_ping_status = 'Ping Response';
    $d12_ping_reason = '';
    $d12_status = 'Rejected';
    $d12_rejectReason = '';

    if (!empty($buyers['D12 ping']['endpoint'])) {
        logBuyerPayload(
            $logPayloads,
            $payloadLogPath,
            'D12 ping',
            $buyers['D12 ping']['endpoint'],
            'form',
            $buyers['D12 ping']['fields'],
            $maskPayloads
        );

        $ch = curl_init($buyers['D12 ping']['endpoint']);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($buyers['D12 ping']['fields']),
            CURLOPT_TIMEOUT => 12,
        ]);
        $d12_ping_response = curl_exec($ch);
        $d12_ping_error = curl_error($ch);
        curl_close($ch);

        $d12_ping_decoded = decodeResponse($d12_ping_response);
        if (is_array($d12_ping_decoded)) {
            $d12_ping_data = $d12_ping_decoded;
            $d12_ping_id = $d12_ping_data['buyers'][0]['ping_id'] ?? null;
            $d12_bid = $d12_ping_data['buyers'][0]['offer_conversion_payout'] ?? 0;
            $d12_minDuration = $d12_ping_data['buyers'][0]['current_conversion_duration']
                ?? $d12_ping_data['min_duration']
                ?? 'N/A';
        }

        if ($d12_ping_error || !$d12_ping_response) {
            $d12_ping_status = 'Error';
            $d12_ping_reason = $d12_ping_error ?: 'No response received';
        } elseif (is_array($d12_ping_data)) {
            if ($d12_ping_id) {
                $d12_ping_status = 'Ping Accepted';
            } elseif (!empty($d12_ping_data['errors'])) {
                $d12_ping_status = 'Ping Rejected';
                if (is_array($d12_ping_data['errors'])) {
                    $d12_ping_reason = implode(' | ', $d12_ping_data['errors']);
                } else {
                    $d12_ping_reason = (string) $d12_ping_data['errors'];
                }
            } elseif (!empty($d12_ping_data['status'])) {
                $d12_ping_status = 'Ping ' . (string) $d12_ping_data['status'];
                $d12_ping_reason = (string) $d12_ping_data['status'];
            } else {
                $d12_ping_status = 'Ping Rejected';
                $d12_ping_reason = 'No ping_id returned';
            }
        } else {
            $d12_ping_status = 'Invalid Response';
            $d12_ping_reason = is_string($d12_ping_response) ? substr($d12_ping_response, 0, 80) : 'Empty response';
        }

        $d12_ping_log = buildGoogleLogData(
            $phone,
            'D12 ping',
            $d12_ping_status,
            $d12_bid,
            $d12_minDuration,
            $d12_ping_reason,
            $ip,
            is_array($d12_ping_decoded) ? $d12_ping_decoded : null,
            $d12_ping_response,
            'form',
            $buyers['D12 ping']['endpoint'],
            $buyers['D12 ping']['fields'],
            $maskPayloads,
            $logGooglePayloads
        );
        logToGoogle($googleWebhook, $d12_ping_log);

        if ($d12_ping_id) {
            $buyers['D12 post']['fields']['ping_id'] = $d12_ping_id;

            logBuyerPayload(
                $logPayloads,
                $payloadLogPath,
                'D12 post',
                $buyers['D12 post']['endpoint'],
                'form',
                $buyers['D12 post']['fields'],
                $maskPayloads
            );

            $ch = curl_init($buyers['D12 post']['endpoint']);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => http_build_query($buyers['D12 post']['fields']),
                CURLOPT_TIMEOUT => 12,
            ]);
            $d12_post_response = curl_exec($ch);
            $d12_post_error = curl_error($ch);
            curl_close($ch);

            $d12_post_decoded = decodeResponse($d12_post_response);
            if (is_array($d12_post_decoded)) {
                $d12_post_data = $d12_post_decoded;
            }

            if ($d12_post_error || !$d12_post_response) {
                $d12_rejectReason = $d12_post_error ?: 'No response received';
                $d12_status = 'Rejected — ' . $d12_rejectReason;
            } elseif (is_array($d12_post_data)) {
                $post_status_value = strtolower((string) ($d12_post_data['status'] ?? ''));
                if (in_array($post_status_value, ['accepted', 'success'], true)) {
                    $d12_status = 'Accepted';
                } else {
                    $d12_rejectReason = $d12_post_data['error'] ?? 'Unknown reason';
                    if (!empty($d12_post_data['errors'])) {
                        $d12_rejectReason = is_array($d12_post_data['errors'])
                            ? implode(' | ', $d12_post_data['errors'])
                            : (string) $d12_post_data['errors'];
                    }
                    $d12_status = 'Rejected — ' . $d12_rejectReason;
                }
            } else {
                $d12_rejectReason = is_string($d12_post_response) ? substr($d12_post_response, 0, 80) : 'Empty response';
                $d12_status = 'Rejected — ' . $d12_rejectReason;
            }

            $d12_post_log = buildGoogleLogData(
                $phone,
                'D12 post',
                $d12_status,
                $d12_bid,
                $d12_minDuration,
                $d12_rejectReason,
                $ip,
                is_array($d12_post_decoded) ? $d12_post_decoded : null,
                $d12_post_response,
                'form',
                $buyers['D12 post']['endpoint'],
                $buyers['D12 post']['fields'],
                $maskPayloads,
                $logGooglePayloads
            );
            logToGoogle($googleWebhook, $d12_post_log);
        } else {
            $d12_rejectReason = $d12_ping_reason ?: 'No ping_id returned';
            $d12_status = 'Rejected — ' . $d12_rejectReason;
        }
    } else {
        $d12_rejectReason = 'D12 ping endpoint not configured';
        $d12_status = 'Rejected — ' . $d12_rejectReason;
    }

    $d12_bid = is_numeric($d12_bid) ? (float) $d12_bid : 0;
    if ($d12_status === 'Accepted' && $d12_bid >= 20) {
        $results[] = [
            'buyer' => 'D12 post',
            'status' => 'Accepted',
            'bid' => $d12_bid,
            'expire' => 'N/A',
            'phoneNumber' => 'N/A',
            'minDuration' => $d12_minDuration,
        ];
    } else {
        $d12_reasonText = $d12_rejectReason ?: ($d12_bid < 20 ? 'Bid too low' : 'No reason given');
        $results[] = [
            'buyer' => 'D12 post',
            'status' => "Rejected — $d12_reasonText",
            'bid' => $d12_bid,
            'expire' => 'N/A',
            'phoneNumber' => 'N/A',
            'minDuration' => $d12_minDuration,
        ];
    }

    // =======================
    // D31 Ping / Post Execution (single flow)
    // =======================
    $d31_ping_response = null;
    $d31_ping_error = null;
    $d31_ping_decoded = null;
    $d31_ping_data = null;
    $d31_post_response = null;
    $d31_post_error = null;
    $d31_post_decoded = null;
    $d31_post_data = null;
    $d31_lead_id = null;
    $d31_price = 0;
    $d31_min_duration = 'N/A';
    $d31_matched = false;
    $d31_status = 'Rejected';
    $d31_rejectReason = '';

    if (!empty($buyers['D31 ping']['endpoint'])) {
        logBuyerPayload(
            $logPayloads,
            $payloadLogPath,
            'D31 ping',
            $buyers['D31 ping']['endpoint'],
            'form',
            $buyers['D31 ping']['fields'],
            $maskPayloads
        );

        $ch = curl_init($buyers['D31 ping']['endpoint']);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($buyers['D31 ping']['fields']),
            CURLOPT_TIMEOUT => 12,
        ]);
        $d31_ping_response = curl_exec($ch);
        $d31_ping_error = curl_error($ch);
        curl_close($ch);

        $d31_ping_decoded = decodeResponse($d31_ping_response);
        if (is_array($d31_ping_decoded)) {
            $d31_ping_data = unwrapResponse($d31_ping_decoded, 'response');
        }

        $d31_ping_status = 'Ping Response';
        $d31_ping_reason = '';

        if ($d31_ping_error || !$d31_ping_response) {
            $d31_ping_status = 'Error';
            $d31_ping_reason = $d31_ping_error ?: 'No response received';
            $d31_status = 'Rejected — ' . $d31_ping_reason;
            $d31_rejectReason = $d31_ping_reason;
        } elseif (is_array($d31_ping_data)) {
            $ping_status_value = strtolower((string) ($d31_ping_data['status'] ?? ''));
            $d31_lead_id = $d31_ping_data['lead_id'] ?? null;
            $d31_price = $d31_ping_data['price'] ?? 0;
            $d31_min_duration = $d31_ping_data['min_duration'] ?? 'N/A';

            if ($ping_status_value === 'matched' && !empty($d31_lead_id)) {
                $d31_ping_status = 'Ping Matched';
                $d31_matched = true;
            } elseif ($ping_status_value !== '') {
                $d31_ping_status = 'Ping ' . $ping_status_value;
                $d31_ping_reason = $d31_ping_data['status'] ?? 'Unmatched';
                $d31_status = 'Rejected — ' . $d31_ping_reason;
                $d31_rejectReason = $d31_ping_reason;
            } else {
                $d31_ping_status = 'Ping Response';
                $d31_ping_reason = 'Unknown ping status';
                $d31_status = 'Rejected — ' . $d31_ping_reason;
                $d31_rejectReason = $d31_ping_reason;
            }
        } else {
            $d31_ping_status = 'Invalid Response';
            $d31_ping_reason = is_string($d31_ping_response) ? substr($d31_ping_response, 0, 80) : 'Empty response';
            $d31_status = 'Rejected — ' . $d31_ping_reason;
            $d31_rejectReason = $d31_ping_reason;
        }

        $d31_ping_log = buildGoogleLogData(
            $phone,
            'D31 ping',
            $d31_ping_status,
            $d31_price,
            $d31_min_duration,
            $d31_ping_reason,
            $ip,
            is_array($d31_ping_decoded) ? $d31_ping_decoded : null,
            $d31_ping_response,
            'form',
            $buyers['D31 ping']['endpoint'],
            $buyers['D31 ping']['fields'],
            $maskPayloads,
            $logGooglePayloads
        );
        logToGoogle($googleWebhook, $d31_ping_log);

        if ($d31_matched) {
            if (!empty($buyers['D31 post']['endpoint'])) {
                $buyers['D31 post']['fields']['Lead_ID'] = $d31_lead_id;

                logBuyerPayload(
                    $logPayloads,
                    $payloadLogPath,
                    'D31 post',
                    $buyers['D31 post']['endpoint'],
                    'form',
                    $buyers['D31 post']['fields'],
                    $maskPayloads
                );

                $ch = curl_init($buyers['D31 post']['endpoint']);
                curl_setopt_array($ch, [
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_POST => true,
                    CURLOPT_POSTFIELDS => http_build_query($buyers['D31 post']['fields']),
                    CURLOPT_TIMEOUT => 12,
                ]);
                $d31_post_response = curl_exec($ch);
                $d31_post_error = curl_error($ch);
                curl_close($ch);

                $d31_post_decoded = decodeResponse($d31_post_response);
                if (is_array($d31_post_decoded)) {
                    $d31_post_data = unwrapResponse($d31_post_decoded, 'response');
                }

                if ($d31_post_error || !$d31_post_response) {
                    $d31_rejectReason = $d31_post_error ?: 'No response received';
                    $d31_status = 'Rejected — ' . $d31_rejectReason;
                } elseif (is_array($d31_post_data)) {
                    $post_status_value = strtolower((string) ($d31_post_data['status'] ?? ''));
                    if ($post_status_value === 'success') {
                        $d31_status = 'Accepted';
                    } else {
                        $d31_rejectReason = $d31_post_data['error'] ?? 'Unknown reason';
                        $d31_status = 'Rejected — ' . $d31_rejectReason;
                    }
                } else {
                    $d31_rejectReason = is_string($d31_post_response) ? substr($d31_post_response, 0, 80) : 'Empty response';
                    $d31_status = 'Rejected — ' . $d31_rejectReason;
                }

                $d31_post_log = buildGoogleLogData(
                    $phone,
                    'D31 post',
                    $d31_status,
                    $d31_price,
                    $d31_min_duration,
                    $d31_rejectReason,
                    $ip,
                    is_array($d31_post_decoded) ? $d31_post_decoded : null,
                    $d31_post_response,
                    'form',
                    $buyers['D31 post']['endpoint'],
                    $buyers['D31 post']['fields'],
                    $maskPayloads,
                    $logGooglePayloads
                );
                logToGoogle($googleWebhook, $d31_post_log);
            } else {
                $d31_rejectReason = 'D31 post endpoint not configured';
                $d31_status = 'Rejected — ' . $d31_rejectReason;
            }
        }
    } else {
        $d31_rejectReason = 'D31 ping endpoint not configured';
        $d31_status = 'Rejected — ' . $d31_rejectReason;
    }

    $d31_bid = is_numeric($d31_price) ? (float) $d31_price : 0;
    if ($d31_status === 'Accepted' && $d31_bid >= 20) {
        $results[] = [
            'buyer' => 'D31 post',
            'status' => 'Accepted',
            'bid' => $d31_bid,
            'expire' => 'N/A',
            'phoneNumber' => 'N/A',
            'minDuration' => $d31_min_duration,
        ];
    } else {
        $d31_reasonText = $d31_rejectReason ?: ($d31_bid < 20 ? 'Bid too low' : 'No reason given');
        $results[] = [
            'buyer' => 'D31 post',
            'status' => "Rejected — $d31_reasonText",
            'bid' => $d31_bid,
            'expire' => 'N/A',
            'phoneNumber' => 'N/A',
            'minDuration' => $d31_min_duration,
        ];
    }

    // Sort results by bid descending
    usort($results, fn($a, $b) => $b['bid'] <=> $a['bid']);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>RTB Bid Comparator</title>
<style>
body {font-family: 'Poppins', sans-serif; background: #f8fafc; display: flex; justify-content: center; padding: 40px;}
.container {background: white; border-radius: 16px; box-shadow: 0 4px 20px rgba(0,0,0,0.1); padding: 30px; width: 460px;}
h2 {text-align: center; color: #007bff; margin-bottom: 20px;}
label {display: block; margin-top: 10px; font-weight: 500;}
input {width: 100%; padding: 10px; border-radius: 8px; border: 1px solid #ccc; margin-top: 5px;}
select {width: 100%; padding: 10px; border-radius: 8px; border: 1px solid #ccc; margin-top: 5px; background: white;}
button {margin-top: 20px; width: 100%; padding: 12px; border: none; border-radius: 8px; background: #007bff; color: white; cursor: pointer;}
button:hover {background: #0056b3;}
.buyer {background: #f9fafb; padding: 12px; border-radius: 8px; margin-bottom: 10px;}
.rank1 {background: #d4edda; border-left: 4px solid #28a745;}
.rejected {background: #fdecea; border-left: 4px solid #dc3545; color: #721c24;}
.rank-label {font-weight: bold; color: #333;}
.loader {display: none; border: 5px solid #f3f3f3; border-top: 5px solid #007bff; border-radius: 50%; width: 40px; height: 40px; animation: spin 1s linear infinite; margin: 20px auto;}
@keyframes spin {0%{transform:rotate(0deg);}100%{transform:rotate(360deg);}}
</style>
<script>
function showLoader() {
  document.getElementById("loader").style.display = "block";
}
</script>
</head>
<body>
<div class="container">
<h2>RTB Bid Comparator</h2>
<form method="POST" onsubmit="showLoader()">
  <label>First Name</label><input type="text" name="first_name" required>
  <label>Last Name</label><input type="text" name="last_name" required>
  <label>Email</label><input type="email" name="email" required>
  <label>Phone Number</label><input type="text" name="phone_number" required>
  <label>ZIP Code</label><input type="text" name="zip_code" required>
  <label>State</label><input type="text" name="state" required>
  <label>Have Attorney</label>
  <select name="have_attorney" required>
    <option value="" selected>Select</option>
    <option value="yes">Yes</option>
    <option value="no">No</option>
  </select>
  <label>Trusted Form Cert ID</label><input type="text" name="trusted_form_cert_id" required>
  <button type="submit">Ping Buyers</button>
</form>

<div id="loader" class="loader"></div>

<?php if (!empty($results)): ?>
<div class="response">
  <?php foreach ($results as $i => $r):
    $rank = match($i) {
      0 => 'Highest Payout',
      1 => '2nd Highest',
      2 => '3rd Highest',
      default => ''
    };
  ?>
  <div class="buyer <?= str_contains($r['status'], 'Accepted') ? 'rank1' : 'rejected' ?>">
    <?php if ($rank && str_contains($r['status'], 'Accepted')): ?>
      <div class="rank-label"><?= $rank ?></div>
    <?php endif; ?>
    <strong><?= htmlspecialchars($r['buyer']) ?> — <?= htmlspecialchars($r['status']) ?></strong><br>
    <?= is_numeric($r['expire']) ? 'Expires In' : 'Status' ?>: <b><?= htmlspecialchars($r['expire']) ?></b><br>
    <?= htmlspecialchars($r['phoneNumber']) ?><br>
    Duration: <?= htmlspecialchars($r['minDuration']) ?>
  </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>
</div>
</body>
</html>

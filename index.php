<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

function decodeResponse(?string $response): ?array
{
    if (!is_string($response) || $response === '') {
        return null;
    }

    $decoded = json_decode($response, true);
    if (is_array($decoded)) {
        return $decoded;
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

$results = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $phone = trim($_POST['phone_number'] ?? '');
    $zip = trim($_POST['zip_code'] ?? '');
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $trusted_form = trim($_POST['trusted_form_cert_id'] ?? '');
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';

    $googleWebhook = "https://script.google.com/macros/s/AKfycbzA8zl5bkPPqFVcLi0GzwsLfLn27CIdXBe5apoa_A8JoHVnMrS9jgUR13Y7WhQUwCKnWQ/exec";

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
    $post_response = null;
    $post_error = null;

    if (!empty($buyers['D29 ping'])) {
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

        if (!$ping_id) {
            error_log('D29 Ping failed: ' . ($ping_error ?: $ping_response));
        }
    }

    if ($ping_id && !empty($buyers['D29 post'])) {
        $buyers['D29 post']['fields']['ping_id'] = $ping_id;

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
        if (in_array($buyer, ['D29 ping', 'D29 post'], true)) {
            continue;
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

        // Log to Google Sheets
        $logData = [
            "number" => $phone,
            "buyer" => $buyer,
            "status" => $status,
            "payout" => $bid,
            "duration" => $minDuration,
            "reason" => $rejectReason,
            "ip" => $ip,
            "api_response" => is_array($decoded) ? json_encode($decoded, JSON_PRETTY_PRINT) : $response,
        ];

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

    $d29_logData = [
        "number" => $phone,
        "buyer" => 'D29 post',
        "status" => $d29_status,
        "payout" => $d29_bid,
        "duration" => $d29_minDuration,
        "reason" => $d29_rejectReason,
        "ip" => $ip,
        "api_response" => is_array($d29_decoded) ? json_encode($d29_decoded, JSON_PRETTY_PRINT) : $post_response,
    ];
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
  <label>Phone Number</label><input type="text" name="phone_number" required>
  <label>ZIP Code</label><input type="text" name="zip_code" required>
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

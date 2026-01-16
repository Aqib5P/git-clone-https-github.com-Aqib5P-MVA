<?php
declare(strict_types=1);

require_once __DIR__ . '/lib/config.php';
require_once __DIR__ . '/lib/db.php';
require_once __DIR__ . '/lib/helpers.php';
require_once __DIR__ . '/lib/area_codes.php';

$config = app_config();
$connection = db_connect();

function respond(string $format, int $status, string $message, array $payload = []): void
{
    http_response_code($status);
    if ($format === 'json') {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'status' => $status,
            'message' => $message,
            'data' => $payload,
        ]);
        return;
    }

    header('Content-Type: text/plain; charset=utf-8');
    echo $message;
}

function ip_allowed(mysqli $connection, string $ip, array $fallback_ips): bool
{
    if ($ip === '') {
        return false;
    }

    $stmt = $connection->prepare('SELECT 1 FROM ip_whitelist WHERE ip_address = ? LIMIT 1');
    $stmt->bind_param('s', $ip);
    $stmt->execute();
    $result = $stmt->get_result();
    $allowed = $result->num_rows > 0;
    $stmt->close();

    if ($allowed) {
        return true;
    }

    if (!empty($fallback_ips)) {
        $result = $connection->query('SELECT 1 FROM ip_whitelist LIMIT 1');
        if ($result->num_rows === 0) {
            return in_array($ip, $fallback_ips, true);
        }
    }

    return false;
}

function select_did(mysqli $connection, string $usage_date, int $limit, ?int $area_code_id): ?array
{
    if ($area_code_id !== null) {
        $stmt = $connection->prepare('
            SELECT d.id, d.did_number, d.area_code_id
            FROM dids d
            LEFT JOIN did_usage_daily dud
                ON d.id = dud.did_id AND dud.usage_date = ?
            WHERE d.active = 1
              AND d.area_code_id = ?
              AND (dud.usage_count IS NULL OR dud.usage_count < ?)
            ORDER BY COALESCE(dud.usage_count, 0) ASC, dud.last_used_at ASC, d.id ASC
            LIMIT 1
            FOR UPDATE
        ');
        $stmt->bind_param('sii', $usage_date, $area_code_id, $limit);
    } else {
        $stmt = $connection->prepare('
            SELECT d.id, d.did_number, d.area_code_id
            FROM dids d
            LEFT JOIN did_usage_daily dud
                ON d.id = dud.did_id AND dud.usage_date = ?
            WHERE d.active = 1
              AND (dud.usage_count IS NULL OR dud.usage_count < ?)
            ORDER BY COALESCE(dud.usage_count, 0) ASC, dud.last_used_at ASC, d.id ASC
            LIMIT 1
            FOR UPDATE
        ');
        $stmt->bind_param('si', $usage_date, $limit);
    }

    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();

    return $row ?: null;
}

function format_did(string $digits, array $config): string
{
    if (empty($config['did']['return_e164'])) {
        return $digits;
    }

    $country_code = $config['did']['default_country_code'];
    if (strlen($digits) === 10 && $country_code !== '') {
        return '+' . $country_code . $digits;
    }

    return '+' . $digits;
}

$format = strtolower($_GET['format'] ?? $config['did']['return_format']);
header('Cache-Control: no-store, no-cache, must-revalidate');

$client_ip = client_ip($config['security']['trusted_proxies']);
if ($config['security']['ip_whitelist_enabled']) {
    if (!ip_allowed($connection, $client_ip, $config['security']['allowed_ips_fallback'])) {
        respond($format, 403, 'Access denied.');
        exit;
    }
}

$dialed_number = $_GET['dialed_number'] ?? $_POST['dialed_number'] ?? '';
if ($dialed_number === '') {
    respond($format, 400, 'Missing dialed_number.');
    exit;
}

$digits = normalize_phone($dialed_number);
if ($digits === '' || strlen($digits) < 3) {
    respond($format, 400, 'Invalid dialed_number.');
    exit;
}

$area_code = extract_area_code($digits);
$area_code_id = strlen($area_code) === 3 ? find_area_code_id($connection, $area_code) : null;
$limit = max(1, (int) $config['did']['daily_limit']);
$usage_date = date('Y-m-d');

try {
    $connection->begin_transaction();

    $did = null;
    if ($area_code_id !== null) {
        $did = select_did($connection, $usage_date, $limit, $area_code_id);
    }
    if ($did === null) {
        $did = select_did($connection, $usage_date, $limit, null);
    }

    if ($did === null) {
        $connection->commit();
        respond($format, 404, 'No available DID.');
        exit;
    }

    $stmt = $connection->prepare('
        INSERT INTO did_usage_daily (did_id, usage_date, usage_count, last_used_at)
        VALUES (?, ?, 1, NOW())
        ON DUPLICATE KEY UPDATE usage_count = usage_count + 1, last_used_at = NOW()
    ');
    $stmt->bind_param('is', $did['id'], $usage_date);
    $stmt->execute();
    $stmt->close();

    $connection->commit();
} catch (Throwable $e) {
    $connection->rollback();
    respond($format, 500, 'Server error.');
    exit;
}

$did_number = format_did($did['did_number'], $config);
if ($format === 'json') {
    respond($format, 200, 'OK', [
        'did_number' => $did_number,
        'did_id' => (int) $did['id'],
        'area_code_id' => (int) $did['area_code_id'],
    ]);
    exit;
}

respond($format, 200, $did_number);

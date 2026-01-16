<?php
declare(strict_types=1);

function h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function normalize_phone(string $value): string
{
    return preg_replace('/\D+/', '', $value);
}

function extract_area_code(string $digits): string
{
    if (strlen($digits) >= 10) {
        $digits = substr($digits, -10);
    }

    return substr($digits, 0, 3);
}

function ensure_session_started(string $session_name): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    session_name($session_name);
    session_start();
}

function csrf_token(string $session_name): string
{
    ensure_session_started($session_name);

    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function verify_csrf_token(string $session_name, string $token): bool
{
    ensure_session_started($session_name);

    if (empty($_SESSION['csrf_token'])) {
        return false;
    }

    return hash_equals($_SESSION['csrf_token'], $token);
}

function client_ip(array $trusted_proxies): string
{
    $remote = $_SERVER['REMOTE_ADDR'] ?? '';
    if ($remote !== '' && in_array($remote, $trusted_proxies, true)) {
        $forwarded = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? '';
        if ($forwarded !== '') {
            $parts = array_map('trim', explode(',', $forwarded));
            if (!empty($parts[0])) {
                return $parts[0];
            }
        }
    }

    return $remote;
}

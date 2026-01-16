<?php
declare(strict_types=1);

return [
    'db' => [
        'host' => getenv('DB_HOST') ?: '127.0.0.1',
        'user' => getenv('DB_USER') ?: 'callerid_user',
        'pass' => getenv('DB_PASS') ?: '',
        'name' => getenv('DB_NAME') ?: 'callerid_db',
        'port' => (int) (getenv('DB_PORT') ?: 3306),
    ],
    'security' => [
        'ip_whitelist_enabled' => (getenv('IP_WHITELIST_ENABLED') ?: '1') === '1',
        'allowed_ips_fallback' => array_values(array_filter(array_map('trim', explode(',', getenv('ALLOWED_IPS') ?: '')))),
        'trusted_proxies' => array_values(array_filter(array_map('trim', explode(',', getenv('TRUSTED_PROXIES') ?: '')))),
        'session_name' => getenv('SESSION_NAME') ?: 'did_admin',
    ],
    'did' => [
        'daily_limit' => (int) (getenv('DID_DAILY_LIMIT') ?: 50),
        'return_format' => getenv('DID_RETURN_FORMAT') ?: 'plain',
        'return_e164' => (getenv('DID_RETURN_E164') ?: '0') === '1',
        'default_country_code' => getenv('DID_COUNTRY_CODE') ?: '1',
    ],
    'admin' => [
        'allow_first_user_setup' => (getenv('ADMIN_ALLOW_FIRST_USER_SETUP') ?: '1') === '1',
    ],
];

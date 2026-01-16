<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';

function ensure_admin_session(): void
{
    $config = app_config();
    ensure_session_started($config['security']['session_name']);
}

function current_admin_user(): ?array
{
    ensure_admin_session();

    if (empty($_SESSION['admin_user'])) {
        return null;
    }

    return $_SESSION['admin_user'];
}

function login_admin_user(int $id, string $username): void
{
    ensure_admin_session();
    $_SESSION['admin_user'] = [
        'id' => $id,
        'username' => $username,
    ];
}

function logout_admin_user(): void
{
    ensure_admin_session();

    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
}

function require_admin_login(): void
{
    if (current_admin_user() === null) {
        header('Location: login.php');
        exit;
    }
}

function admin_user_exists(mysqli $connection): bool
{
    $result = $connection->query('SELECT 1 FROM admin_users LIMIT 1');
    return $result->num_rows > 0;
}

function find_admin_user(mysqli $connection, string $username): ?array
{
    $stmt = $connection->prepare('SELECT id, username, password_hash, is_active FROM admin_users WHERE username = ? LIMIT 1');
    $stmt->bind_param('s', $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();

    return $user ?: null;
}

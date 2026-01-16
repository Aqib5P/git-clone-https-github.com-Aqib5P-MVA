<?php
declare(strict_types=1);

require_once __DIR__ . '/../lib/config.php';
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/helpers.php';
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/layout.php';

$config = app_config();
ensure_admin_session();
$connection = db_connect();

$errors = [];
$success = '';
$has_admin = admin_user_exists($connection);
$mode = (!$has_admin && $config['admin']['allow_first_user_setup']) ? 'setup' : 'login';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($config['security']['session_name'], $token)) {
        $errors[] = 'Invalid session token. Refresh and try again.';
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($username === '' || $password === '') {
            $errors[] = 'Username and password are required.';
        } elseif ($mode === 'setup') {
            if (strlen($password) < 8) {
                $errors[] = 'Password must be at least 8 characters.';
            }
        }

        if (empty($errors)) {
            if ($mode === 'setup') {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $connection->prepare('INSERT INTO admin_users (username, password_hash) VALUES (?, ?)');
                $stmt->bind_param('ss', $username, $hash);
                $stmt->execute();
                $stmt->close();

                login_admin_user((int) $connection->insert_id, $username);
                header('Location: index.php');
                exit;
            }

            $user = find_admin_user($connection, $username);
            if (!$user || !(bool) $user['is_active']) {
                $errors[] = 'Invalid credentials.';
            } elseif (!password_verify($password, $user['password_hash'])) {
                $errors[] = 'Invalid credentials.';
            } else {
                $stmt = $connection->prepare('UPDATE admin_users SET last_login_at = NOW() WHERE id = ?');
                $stmt->bind_param('i', $user['id']);
                $stmt->execute();
                $stmt->close();

                login_admin_user((int) $user['id'], $user['username']);
                header('Location: index.php');
                exit;
            }
        }
    }
}

render_header($mode === 'setup' ? 'Create Admin User' : 'Admin Login');
?>

<?php if (!empty($errors)) : ?>
    <div class="messages">
        <?php foreach ($errors as $error) : ?>
            <div class="message error"><?php echo h($error); ?></div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php if ($success !== '') : ?>
    <div class="messages">
        <div class="message success"><?php echo h($success); ?></div>
    </div>
<?php endif; ?>

<form method="post" class="panel">
    <input type="hidden" name="csrf_token" value="<?php echo h(csrf_token($config['security']['session_name'])); ?>">
    <label for="username">Username</label>
    <input type="text" id="username" name="username" required>

    <label for="password">Password</label>
    <input type="password" id="password" name="password" required>

    <button type="submit" class="button"><?php echo $mode === 'setup' ? 'Create Admin' : 'Sign In'; ?></button>
</form>

<?php if ($mode === 'setup') : ?>
    <p class="note">First admin setup is enabled. This form is hidden once an admin exists.</p>
<?php endif; ?>

<?php
render_footer();

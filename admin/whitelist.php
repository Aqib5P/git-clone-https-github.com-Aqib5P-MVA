<?php
declare(strict_types=1);

require_once __DIR__ . '/../lib/config.php';
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/helpers.php';
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/layout.php';

require_admin_login();

$config = app_config();
$connection = db_connect();
$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($config['security']['session_name'], $token)) {
        $errors[] = 'Invalid session token. Refresh and try again.';
    } else {
        $action = $_POST['action'] ?? '';
        if ($action === 'add') {
            $ip_address = trim($_POST['ip_address'] ?? '');
            $label = trim($_POST['label'] ?? '');
            if ($ip_address === '' || !filter_var($ip_address, FILTER_VALIDATE_IP)) {
                $errors[] = 'Enter a valid IP address.';
            } else {
                $stmt = $connection->prepare('INSERT INTO ip_whitelist (ip_address, label) VALUES (?, ?)');
                $stmt->bind_param('ss', $ip_address, $label);
                $stmt->execute();
                $stmt->close();
                $success = 'IP added to whitelist.';
            }
        } elseif ($action === 'delete') {
            $ip_id = (int) ($_POST['ip_id'] ?? 0);
            if ($ip_id > 0) {
                $stmt = $connection->prepare('DELETE FROM ip_whitelist WHERE id = ?');
                $stmt->bind_param('i', $ip_id);
                $stmt->execute();
                $stmt->close();
                $success = 'IP removed.';
            }
        }
    }
}

$result = $connection->query('SELECT id, ip_address, label, created_at FROM ip_whitelist ORDER BY id DESC');
$ips = $result->fetch_all(MYSQLI_ASSOC);

render_header('IP Whitelist');
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

<form method="post">
    <input type="hidden" name="csrf_token" value="<?php echo h(csrf_token($config['security']['session_name'])); ?>">
    <input type="hidden" name="action" value="add">
    <label for="ip_address">Add IP</label>
    <input type="text" id="ip_address" name="ip_address" placeholder="203.0.113.10" required>
    <label for="label">Label (optional)</label>
    <input type="text" id="label" name="label" placeholder="Partner gateway">
    <button type="submit">Add IP</button>
</form>

<table>
    <thead>
        <tr>
            <th>ID</th>
            <th>IP Address</th>
            <th>Label</th>
            <th>Created</th>
            <th>Action</th>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($ips)) : ?>
            <tr>
                <td colspan="5">No IPs whitelisted yet.</td>
            </tr>
        <?php else : ?>
            <?php foreach ($ips as $ip) : ?>
                <tr>
                    <td><?php echo (int) $ip['id']; ?></td>
                    <td><?php echo h($ip['ip_address']); ?></td>
                    <td><?php echo h($ip['label'] ?? ''); ?></td>
                    <td><?php echo h($ip['created_at']); ?></td>
                    <td>
                        <form method="post" class="actions">
                            <input type="hidden" name="csrf_token" value="<?php echo h(csrf_token($config['security']['session_name'])); ?>">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="ip_id" value="<?php echo (int) $ip['id']; ?>">
                            <button type="submit" class="secondary" onclick="return confirm('Remove this IP?');">Remove</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
</table>

<?php
render_footer();

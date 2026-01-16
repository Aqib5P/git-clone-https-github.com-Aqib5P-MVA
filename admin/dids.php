<?php
declare(strict_types=1);

require_once __DIR__ . '/../lib/config.php';
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/helpers.php';
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/area_codes.php';
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
            $did_input = trim($_POST['did_number'] ?? '');
            $did_digits = normalize_phone($did_input);
            if ($did_digits === '' || strlen($did_digits) < 10) {
                $errors[] = 'Enter a DID with at least 10 digits.';
            } else {
                $area_code = extract_area_code($did_digits);
                if (strlen($area_code) !== 3) {
                    $errors[] = 'Unable to detect area code.';
                } else {
                    $area_code_id = get_or_create_area_code_id($connection, $area_code);
                    $stmt = $connection->prepare('INSERT INTO dids (area_code_id, did_number) VALUES (?, ?)');
                    $stmt->bind_param('is', $area_code_id, $did_digits);
                    $stmt->execute();
                    $stmt->close();
                    $success = 'DID added.';
                }
            }
        } elseif ($action === 'delete') {
            $did_id = (int) ($_POST['did_id'] ?? 0);
            if ($did_id > 0) {
                $stmt = $connection->prepare('DELETE FROM dids WHERE id = ?');
                $stmt->bind_param('i', $did_id);
                $stmt->execute();
                $stmt->close();
                $success = 'DID removed.';
            }
        }
    }
}

$result = $connection->query('
    SELECT d.id, d.did_number, d.active, d.created_at, a.area_code
    FROM dids d
    INNER JOIN area_codes a ON d.area_code_id = a.id
    ORDER BY d.id DESC
');
$dids = $result->fetch_all(MYSQLI_ASSOC);

render_header('Manage DIDs');
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
    <label for="did_number">Add DID (digits only)</label>
    <input type="text" id="did_number" name="did_number" placeholder="2125550199" required>
    <p class="note">Area code is derived from the last 10 digits.</p>
    <button type="submit">Add DID</button>
</form>

<table>
    <thead>
        <tr>
            <th>ID</th>
            <th>DID</th>
            <th>Area Code</th>
            <th>Created</th>
            <th>Action</th>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($dids)) : ?>
            <tr>
                <td colspan="5">No DIDs added yet.</td>
            </tr>
        <?php else : ?>
            <?php foreach ($dids as $did) : ?>
                <tr>
                    <td><?php echo (int) $did['id']; ?></td>
                    <td><?php echo h($did['did_number']); ?></td>
                    <td><?php echo h($did['area_code']); ?></td>
                    <td><?php echo h($did['created_at']); ?></td>
                    <td>
                        <form method="post" class="actions">
                            <input type="hidden" name="csrf_token" value="<?php echo h(csrf_token($config['security']['session_name'])); ?>">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="did_id" value="<?php echo (int) $did['id']; ?>">
                            <button type="submit" class="secondary" onclick="return confirm('Remove this DID?');">Remove</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
</table>

<?php
render_footer();

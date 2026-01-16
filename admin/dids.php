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
$search = trim($_GET['q'] ?? '');
$search_digits = normalize_phone($search);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($config['security']['session_name'], $token)) {
        $errors[] = 'Invalid session token. Refresh and try again.';
    } else {
        if (isset($_POST['delete_id'])) {
            $did_id = (int) $_POST['delete_id'];
            if ($did_id > 0) {
                $stmt = $connection->prepare('DELETE FROM dids WHERE id = ?');
                $stmt->bind_param('i', $did_id);
                $stmt->execute();
                $stmt->close();
                $success = 'DID removed.';
            }
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
            } elseif ($action === 'bulk_upload') {
                if (empty($_FILES['csv_file']) || !is_uploaded_file($_FILES['csv_file']['tmp_name'])) {
                    $errors[] = 'Select a CSV file to upload.';
                } elseif ($_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
                    $errors[] = 'Upload failed. Try again.';
                } else {
                    $added = 0;
                    $duplicate = 0;
                    $invalid = 0;
                    $area_code_cache = [];

                    $handle = fopen($_FILES['csv_file']['tmp_name'], 'r');
                    if ($handle === false) {
                        $errors[] = 'Unable to read uploaded file.';
                    } else {
                        $stmt = $connection->prepare('INSERT IGNORE INTO dids (area_code_id, did_number) VALUES (?, ?)');
                        try {
                            $connection->begin_transaction();
                            $row_index = 0;
                            while (($row = fgetcsv($handle)) !== false) {
                                $row_index++;
                                if (empty($row)) {
                                    continue;
                                }

                                $raw_row = implode(' ', $row);
                                $has_letters = preg_match('/[a-zA-Z]/', $raw_row) === 1;
                                $candidate = '';
                                foreach ($row as $cell) {
                                    $cell = trim((string) $cell);
                                    if ($cell === '') {
                                        continue;
                                    }
                                    $digits = normalize_phone($cell);
                                    if ($digits !== '') {
                                        $candidate = $digits;
                                        break;
                                    }
                                }

                                if ($row_index === 1 && $candidate === '' && $has_letters) {
                                    continue;
                                }

                                if ($candidate === '' || strlen($candidate) < 10) {
                                    $invalid++;
                                    continue;
                                }

                                $area_code = extract_area_code($candidate);
                                if (strlen($area_code) !== 3) {
                                    $invalid++;
                                    continue;
                                }

                                if (!isset($area_code_cache[$area_code])) {
                                    $area_code_cache[$area_code] = get_or_create_area_code_id($connection, $area_code);
                                }

                                $area_code_id = $area_code_cache[$area_code];
                                $stmt->bind_param('is', $area_code_id, $candidate);
                                $stmt->execute();

                                if ($stmt->affected_rows === 1) {
                                    $added++;
                                } else {
                                    $duplicate++;
                                }
                            }
                            $connection->commit();
                            fclose($handle);
                            $stmt->close();

                            $success = 'Bulk upload complete. Added ' . $added . ', skipped ' . $duplicate . ' duplicates, ' . $invalid . ' invalid.';
                        } catch (Throwable $e) {
                            $connection->rollback();
                            fclose($handle);
                            $stmt->close();
                            $errors[] = 'Bulk upload failed. Try again.';
                        }
                    }
                }
            } elseif ($action === 'bulk_delete') {
                $selected = $_POST['did_ids'] ?? [];
                $ids = array_values(array_unique(array_filter(array_map('intval', (array) $selected))));
                if (empty($ids)) {
                    $errors[] = 'Select at least one DID to remove.';
                } else {
                    $placeholders = implode(',', array_fill(0, count($ids), '?'));
                    $types = str_repeat('i', count($ids));
                    $stmt = $connection->prepare("DELETE FROM dids WHERE id IN ($placeholders)");
                    $stmt->bind_param($types, ...$ids);
                    $stmt->execute();
                    $removed = $stmt->affected_rows;
                    $stmt->close();
                    $success = 'Removed ' . $removed . ' DIDs.';
                }
            }
        }
    }
}

$sql = '
    SELECT d.id, d.did_number, d.active, d.created_at, a.area_code
    FROM dids d
    INNER JOIN area_codes a ON d.area_code_id = a.id
';

if ($search_digits !== '') {
    $sql .= ' WHERE d.did_number LIKE ? OR a.area_code LIKE ?';
}

$sql .= ' ORDER BY d.id DESC';

if ($search_digits !== '') {
    $like = '%' . $search_digits . '%';
    $stmt = $connection->prepare($sql);
    $stmt->bind_param('ss', $like, $like);
    $stmt->execute();
    $result = $stmt->get_result();
    $dids = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} else {
    $result = $connection->query($sql);
    $dids = $result->fetch_all(MYSQLI_ASSOC);
}

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

<form method="get" class="panel form-inline">
    <label for="q">Search DIDs</label>
    <input type="text" id="q" name="q" value="<?php echo h($search); ?>" placeholder="DID or area code">
    <button type="submit" class="button">Search</button>
    <a class="button secondary" href="dids.php">Clear</a>
</form>

<form method="post" class="panel">
    <input type="hidden" name="csrf_token" value="<?php echo h(csrf_token($config['security']['session_name'])); ?>">
    <input type="hidden" name="action" value="add">
    <label for="did_number">Add DID (digits only)</label>
    <input type="text" id="did_number" name="did_number" placeholder="2125550199" required>
    <p class="note">Area code is derived from the last 10 digits.</p>
    <button type="submit" class="button">Add DID</button>
</form>

<form method="post" enctype="multipart/form-data" class="panel">
    <input type="hidden" name="csrf_token" value="<?php echo h(csrf_token($config['security']['session_name'])); ?>">
    <input type="hidden" name="action" value="bulk_upload">
    <label for="csv_file">Bulk upload DIDs (CSV)</label>
    <input type="file" id="csv_file" name="csv_file" accept=".csv,text/csv" required>
    <p class="note">Use one DID per row. Header row is optional.</p>
    <button type="submit" class="button">Upload CSV</button>
</form>

<?php if (empty($dids)) : ?>
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
            <tr>
                <td colspan="5">No DIDs added yet.</td>
            </tr>
        </tbody>
    </table>
<?php else : ?>
    <form method="post" class="panel table-form">
        <input type="hidden" name="csrf_token" value="<?php echo h(csrf_token($config['security']['session_name'])); ?>">
        <div class="table-actions">
            <label class="checkbox">
                <input type="checkbox" id="select_all">
                Select all
            </label>
            <button type="submit" name="action" value="bulk_delete" class="button danger" id="bulk_delete_button" disabled>Delete selected</button>
            <span class="note" id="selected_count">0 selected</span>
        </div>
        <table>
            <thead>
                <tr>
                    <th class="checkbox-cell">Select</th>
                    <th>ID</th>
                    <th>DID</th>
                    <th>Area Code</th>
                    <th>Created</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($dids as $did) : ?>
                    <tr>
                        <td class="checkbox-cell">
                            <input type="checkbox" name="did_ids[]" value="<?php echo (int) $did['id']; ?>">
                        </td>
                        <td><?php echo (int) $did['id']; ?></td>
                        <td><?php echo h($did['did_number']); ?></td>
                        <td><?php echo h($did['area_code']); ?></td>
                        <td><?php echo h($did['created_at']); ?></td>
                        <td>
                            <button type="submit" name="delete_id" value="<?php echo (int) $did['id']; ?>" class="button danger" onclick="return confirm('Remove this DID?');">Remove</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </form>
<?php endif; ?>

<script>
    (function () {
        var selectAll = document.getElementById('select_all');
        var checkboxes = Array.prototype.slice.call(document.querySelectorAll('input[name="did_ids[]"]'));
        var countEl = document.getElementById('selected_count');
        var bulkButton = document.getElementById('bulk_delete_button');

        if (!selectAll || checkboxes.length === 0 || !countEl || !bulkButton) {
            return;
        }

        function updateState() {
            var selected = checkboxes.filter(function (checkbox) {
                return checkbox.checked;
            }).length;

            countEl.textContent = selected + ' selected';
            bulkButton.disabled = selected === 0;
            selectAll.checked = selected === checkboxes.length;
            selectAll.indeterminate = selected > 0 && selected < checkboxes.length;
        }

        selectAll.addEventListener('change', function () {
            checkboxes.forEach(function (checkbox) {
                checkbox.checked = selectAll.checked;
            });
            updateState();
        });

        checkboxes.forEach(function (checkbox) {
            checkbox.addEventListener('change', updateState);
        });

        updateState();
    })();
</script>

<?php
render_footer();

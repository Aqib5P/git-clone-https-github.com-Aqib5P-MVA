<?php
declare(strict_types=1);

require_once __DIR__ . '/../lib/config.php';
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/helpers.php';
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/layout.php';

require_admin_login();

$connection = db_connect();
$date_input = $_GET['date'] ?? date('Y-m-d');
$date = preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_input) ? $date_input : date('Y-m-d');
$search = trim($_GET['q'] ?? '');
$search_digits = normalize_phone($search);

$sql = '
    SELECT d.did_number, a.area_code, dud.usage_date, dud.usage_count, dud.last_used_at
    FROM did_usage_daily dud
    INNER JOIN dids d ON dud.did_id = d.id
    INNER JOIN area_codes a ON d.area_code_id = a.id
    WHERE dud.usage_date = ?
';

$types = 's';
$params = [$date];
if ($search_digits !== '') {
    $sql .= ' AND d.did_number LIKE ?';
    $types .= 's';
    $params[] = '%' . $search_digits . '%';
}

$sql .= ' ORDER BY dud.usage_count DESC, d.did_number ASC';

$stmt = $connection->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
$rows = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$total = 0;
foreach ($rows as $row) {
    $total += (int) $row['usage_count'];
}

render_header('Daily Usage');
?>

<form method="get" class="panel form-inline">
    <label for="date">Date</label>
    <input type="date" id="date" name="date" value="<?php echo h($date); ?>" required>
    <label for="q">Search DID</label>
    <input type="text" id="q" name="q" value="<?php echo h($search); ?>" placeholder="DID digits">
    <button type="submit" class="button">Filter</button>
    <a class="button secondary" href="usage.php">Clear</a>
</form>

<p class="note">Total uses for selected date: <?php echo (int) $total; ?></p>

<table>
    <thead>
        <tr>
            <th>DID</th>
            <th>Area Code</th>
            <th>Date</th>
            <th>Usage Count</th>
            <th>Last Used</th>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($rows)) : ?>
            <tr>
                <td colspan="5">No usage recorded for this date.</td>
            </tr>
        <?php else : ?>
            <?php foreach ($rows as $row) : ?>
                <tr>
                    <td><?php echo h($row['did_number']); ?></td>
                    <td><?php echo h($row['area_code']); ?></td>
                    <td><?php echo h($row['usage_date']); ?></td>
                    <td><?php echo (int) $row['usage_count']; ?></td>
                    <td><?php echo h($row['last_used_at'] ?? ''); ?></td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
</table>

<?php
render_footer();

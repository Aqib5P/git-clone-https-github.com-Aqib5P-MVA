<?php
declare(strict_types=1);

require_once __DIR__ . '/../lib/config.php';
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/layout.php';

require_admin_login();

$connection = db_connect();
$did_count = (int) $connection->query('SELECT COUNT(*) AS count FROM dids')->fetch_assoc()['count'];
$ip_count = (int) $connection->query('SELECT COUNT(*) AS count FROM ip_whitelist')->fetch_assoc()['count'];
$usage_today = $connection->query('
    SELECT COALESCE(SUM(usage_count), 0) AS total_uses,
           COUNT(*) AS dids_used
    FROM did_usage_daily
    WHERE usage_date = CURDATE()
')->fetch_assoc();

render_header('Dashboard');
?>

<p class="note">Manage DIDs and IP whitelist entries from the menu. View detailed counts on the <a href="usage.php">Daily Usage</a> page.</p>

<table>
    <thead>
        <tr>
            <th>Metric</th>
            <th>Count</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>Total DIDs</td>
            <td><?php echo $did_count; ?></td>
        </tr>
        <tr>
            <td>Whitelisted IPs</td>
            <td><?php echo $ip_count; ?></td>
        </tr>
        <tr>
            <td>Today's DID uses</td>
            <td><?php echo (int) $usage_today['total_uses']; ?></td>
        </tr>
        <tr>
            <td>DIDs used today</td>
            <td><?php echo (int) $usage_today['dids_used']; ?></td>
        </tr>
    </tbody>
</table>

<?php
render_footer();

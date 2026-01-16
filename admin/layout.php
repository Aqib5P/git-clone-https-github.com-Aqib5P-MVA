<?php
declare(strict_types=1);

require_once __DIR__ . '/../lib/helpers.php';
require_once __DIR__ . '/../lib/auth.php';

function render_header(string $title): void
{
    $user = current_admin_user();
    $page_title = $title . ' - DID Admin';

    echo '<!DOCTYPE html>';
    echo '<html lang="en">';
    echo '<head>';
    echo '<meta charset="utf-8">';
    echo '<meta name="viewport" content="width=device-width, initial-scale=1">';
    echo '<title>' . h($page_title) . '</title>';
    echo '<link rel="stylesheet" href="styles.css">';
    echo '</head>';
    echo '<body>';
    echo '<header class="site-header">';
    echo '<div class="container">';
    echo '<div class="site-title">DID Admin</div>';
    if ($user) {
        echo '<nav class="site-nav">';
        echo '<a href="index.php">Dashboard</a>';
        echo '<a href="dids.php">DIDs</a>';
        echo '<a href="whitelist.php">IP Whitelist</a>';
        echo '<a href="usage.php">Daily Usage</a>';
        echo '<a href="logout.php">Logout</a>';
        echo '</nav>';
        echo '<div class="user-chip">Signed in as ' . h($user['username']) . '</div>';
    }
    echo '</div>';
    echo '</header>';
    echo '<main class="container">';
    echo '<h1>' . h($title) . '</h1>';
}

function render_footer(): void
{
    echo '</main>';
    echo '</body>';
    echo '</html>';
}

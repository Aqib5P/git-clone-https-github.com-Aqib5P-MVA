<?php
declare(strict_types=1);

require_once __DIR__ . '/../lib/auth.php';

logout_admin_user();
header('Location: login.php');
exit;

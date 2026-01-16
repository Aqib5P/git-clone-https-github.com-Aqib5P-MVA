<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

function db_connect(): mysqli
{
    $db = app_config()['db'];
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

    $connection = new mysqli($db['host'], $db['user'], $db['pass'], $db['name'], $db['port']);
    $connection->set_charset('utf8mb4');

    return $connection;
}

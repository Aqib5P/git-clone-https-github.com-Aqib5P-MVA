<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';

function find_area_code_id(mysqli $connection, string $area_code): ?int
{
    $stmt = $connection->prepare('SELECT id FROM area_codes WHERE area_code = ?');
    $stmt->bind_param('s', $area_code);
    $stmt->execute();
    $stmt->bind_result($area_code_id);
    $stmt->fetch();
    $stmt->close();

    return $area_code_id ? (int) $area_code_id : null;
}

function get_or_create_area_code_id(mysqli $connection, string $area_code): int
{
    $existing = find_area_code_id($connection, $area_code);
    if ($existing !== null) {
        return $existing;
    }

    $stmt = $connection->prepare('INSERT INTO area_codes (area_code) VALUES (?)');
    $stmt->bind_param('s', $area_code);
    $stmt->execute();
    $stmt->close();

    return (int) $connection->insert_id;
}

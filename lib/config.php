<?php
declare(strict_types=1);

function app_config(): array
{
    static $config = null;
    if ($config === null) {
        $config = require __DIR__ . '/../config.php';
    }

    return $config;
}

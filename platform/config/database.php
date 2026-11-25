<?php
declare(strict_types=1);

$host = getenv('DB_HOST') ?: '127.0.0.1';
$port = (int) (getenv('DB_PORT') ?: 3306);
$name = getenv('DB_NAME') ?: 'mats_platform_dev';
$dsn = getenv('DB_DSN');
if (!$dsn) {
    $dsn = "mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4";
}

return [
    'dsn' => $dsn,
    'user' => getenv('DB_USER') ?: '',
    'pass' => getenv('DB_PASS') ?: '',
    'options' => [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ],
];

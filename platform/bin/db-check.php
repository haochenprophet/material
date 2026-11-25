<?php
declare(strict_types=1);

/**
 * 数据层检查：默认只打印配置，不连接。
 * 用法：
 *   php platform/bin/db-check.php
 *   php platform/bin/db-check.php --connect   # 需要 DB_USER/DB_PASS
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    echo 'CLI only';
    exit(1);
}

require dirname(__DIR__) . '/app/bootstrap.php';

$connect = in_array('--connect', $argv, true);
echo 'dsn: ' . Database::dsn() . PHP_EOL;
echo 'configured: ' . (Database::configured() ? 'yes' : 'no') . PHP_EOL;
echo 'connect: ' . ($connect ? 'requested' : 'skipped') . PHP_EOL;

if (!$connect) {
    echo 'OK（未连接；设置 DB_USER/DB_PASS 后加 --connect）' . PHP_EOL;
    exit(0);
}
if (!Database::configured()) {
    echo 'SKIP：DB_USER/DB_PASS 未设置' . PHP_EOL;
    exit(2);
}
try {
    $pdo = Database::pdo();
    $version = $pdo->query('SELECT VERSION()')->fetchColumn();
    echo 'connected: MySQL ' . $version . PHP_EOL;
    exit(0);
} catch (Throwable $e) {
    echo 'FAIL: ' . $e->getMessage() . PHP_EOL;
    exit(1);
}

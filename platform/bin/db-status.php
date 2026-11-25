<?php
declare(strict_types=1);

/**
 * 只读数据库状态：表数/种子/旧库，不写任何数据。
 * 用法：
 *   $env:DB_USER='patent'; $env:DB_PASS='patent'
 *   php platform/bin/db-status.php
 * 可选 DB_NAME（默认 mats_platform_next2，只允许 mats*）。
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    echo 'CLI only';
    exit(1);
}

require dirname(__DIR__) . '/app/bootstrap.php';

$name = getenv('DB_NAME') ?: 'mats_platform_next2';
if (!preg_match('/^mats[a-z0-9_]*$/', $name) || $name === 'materiel') {
    fwrite(STDERR, "拒绝数据库名: {$name}\n");
    exit(1);
}
$user = (string) getenv('DB_USER');
$pass = (string) getenv('DB_PASS');
if ($user === '') {
    fwrite(STDERR, "DB_USER 未设置\n");
    exit(2);
}
$host = getenv('DB_HOST') ?: '127.0.0.1';
$port = (int) (getenv('DB_PORT') ?: 3306);

try {
    $pdo = new PDO("mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    $tables = (int) $pdo->query("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema=" . $pdo->quote($name))->fetchColumn();
    $seed = [];
    foreach (['role', 'permission', 'warehouse', 'category', 'ledger_account', 'commission_rule'] as $table) {
        $seed[$table] = (int) $pdo->query("SELECT COUNT(*) FROM `{$table}`")->fetchColumn();
    }
    $legacyTables = (int) $pdo->query("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='materiel'")->fetchColumn();
    $legacyRows = 0;
    try {
        $legacyRows = (int) $pdo->query('SELECT COUNT(*) FROM materiel.materiel')->fetchColumn();
    } catch (Throwable) {
        $legacyRows = -1;
    }
    echo json_encode([
        'database' => $name,
        'tables' => $tables,
        'seed' => $seed,
        'legacy' => ['tables' => $legacyTables, 'materiel_rows' => $legacyRows],
        'read_only' => true,
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
    exit($tables >= 45 ? 0 : 1);
} catch (Throwable $e) {
    fwrite(STDERR, 'FAIL: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}

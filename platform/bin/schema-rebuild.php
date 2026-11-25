<?php
declare(strict_types=1);

/**
 * 用基础 001–006 + 平台增量 007–013 重建一个全新验证库。
 * 只创建新库；不覆盖已有表；不删除任何库。
 * 用法：DB_USER=... DB_PASS=... php platform/bin/schema-rebuild.php mats_platform_rebuild_20261125 --execute
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    echo 'CLI only';
    exit(1);
}
require dirname(__DIR__) . '/app/bootstrap.php';

$target = $argv[1] ?? '';
if (!preg_match('/^mats_platform_rebuild_[a-z0-9_]{2,40}$/', $target)) {
    fwrite(STDERR, "目标必须是 mats_platform_rebuild_*，拒绝: {$target}\n");
    exit(1);
}
$execute = in_array('--execute', $argv, true);
$baseDir = PLATFORM_PATH . '/database/generated/mats_platform_next2';
$baseFiles = ['001_master.sql', '002_inventory.sql', '003_listing.sql', '004_bom_trade.sql', '005_migration_legacy.sql', '005b_migration_templates.sql', '006_seed.sql'];
$addFiles = ['007_commerce.sql', '008_reputation.sql', '009_merchant_kyc.sql', '010_material_image.sql', '011_sandbox_payment.sql', '012_commission_version.sql', '013_api_auth.sql'];
$files = [];
foreach ($baseFiles as $f) {
    $p = $baseDir . '/' . $f;
    if (!is_file($p)) { fwrite(STDERR, "缺少基础文件: {$f}\n"); exit(1); }
    $files[] = $p;
}
foreach ($addFiles as $f) {
    $p = PLATFORM_PATH . '/database/' . $f;
    if (!is_file($p)) { fwrite(STDERR, "缺少增量文件: {$f}\n"); exit(1); }
    $files[] = $p;
}
echo 'target: ' . $target . PHP_EOL;
echo 'files:  ' . count($files) . ' (7 base + 7 additive)' . PHP_EOL;
echo 'mode:   ' . ($execute ? 'EXECUTE' : 'DRY-RUN') . PHP_EOL;
if (!$execute) {
    echo "OK: dry-run; add --execute\n";
    exit(0);
}
$user = (string) getenv('DB_USER');
$pass = (string) getenv('DB_PASS');
if ($user === '') { fwrite(STDERR, "DB_USER 未设置\n"); exit(2); }
$host = getenv('DB_HOST') ?: '127.0.0.1';
$port = (int) (getenv('DB_PORT') ?: 3306);
try {
    $server = new PDO("mysql:host={$host};port={$port};charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::MYSQL_ATTR_MULTI_STATEMENTS => true,
    ]);
    $exists = (int) $server->query("SELECT COUNT(*) FROM information_schema.schemata WHERE schema_name=" . $server->quote($target))->fetchColumn();
    if ($exists > 0) {
        $tables = (int) $server->query("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema=" . $server->quote($target))->fetchColumn();
        if ($tables > 0) {
            fwrite(STDERR, "目标库已有 {$tables} 张表，拒绝覆盖；请换新名称\n");
            exit(1);
        }
    }
    $runner = new MigrationRunner();
    foreach ($files as $file) {
        $sql = (string) file_get_contents($file);
        if (str_starts_with(basename($file), '00') && str_ends_with(basename($file), '.sql')) {
            // 基础文件需要把 CREATE DATABASE/USE 重写到目标库。
            if (file_exists($baseDir . '/' . basename($file))) {
                $sql = $runner->rewrite($sql, $target);
            }
        }
        $server->exec($sql);
        echo 'applied: ' . basename($file) . PHP_EOL;
    }
    $pdo = new PDO("mysql:host={$host};port={$port};dbname={$target};charset=utf8mb4", $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $tables = (int) $pdo->query("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema=" . $pdo->quote($target))->fetchColumn();
    echo 'tables: ' . $tables . ' (expected 92)' . PHP_EOL;
    $required = ['material', 'listing', 'material_provenance', 'material_comment', 'merchant_kyc', 'material_review', 'sandbox_payment', 'api_token'];
    $found = $pdo->query("SELECT table_name FROM information_schema.tables WHERE table_schema=" . $pdo->quote($target))->fetchAll(PDO::FETCH_COLUMN) ?: [];
    $missing = array_values(array_diff($required, $found));
    if ($missing !== []) {
        fwrite(STDERR, '缺失关键表: ' . implode(', ', $missing) . PHP_EOL);
        exit(1);
    }
    echo 'required tables: OK' . PHP_EOL;
    exit($tables === 92 ? 0 : 1);
} catch (Throwable $e) {
    fwrite(STDERR, 'FAIL: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}

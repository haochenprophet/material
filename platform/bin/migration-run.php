<?php
declare(strict_types=1);

/**
 * 迁移执行器：只允许新的 mats_platform_* 空库；默认 dry-run，--execute 才执行。
 * 用法：
 *   php platform/bin/migration-run.php mats_platform_next
 *   php platform/bin/migration-run.php mats_platform_next --execute
 *
 * 需要环境变量：DB_USER、DB_PASS；可选 DB_HOST/DB_PORT。
 * 不删除库、不覆盖非空库；失败即停，保留现场。
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    echo 'CLI only';
    exit(1);
}

require dirname(__DIR__) . '/app/bootstrap.php';

$target = $argv[1] ?? 'mats_platform_next';
$execute = in_array('--execute', $argv, true);
$allowExistingEmpty = in_array('--allow-existing-empty', $argv, true);

if (!preg_match('/^mats_platform_[a-z0-9_]{2,48}$/', $target) || $target === 'mats' || $target === 'materiel') {
    fwrite(STDERR, "拒绝目标库：只允许 mats_platform_* 新库\n");
    exit(1);
}
$dir = PLATFORM_PATH . '/database/generated/' . $target;
if (!is_dir($dir)) {
    fwrite(STDERR, "生成目录不存在：{$dir}\n先运行 php bin/migration-plan.php {$target} --write\n");
    exit(1);
}
$files = glob($dir . '/*.sql') ?: [];
usort($files, static function (string $a, string $b): int {
    $order = ['001_master.sql' => 1, '002_inventory.sql' => 2, '003_listing.sql' => 3, '004_bom_trade.sql' => 4, '005_migration_legacy.sql' => 5, '005b_migration_templates.sql' => 6, '006_seed.sql' => 7];
    return ($order[basename($a)] ?? 99) <=> ($order[basename($b)] ?? 99);
});
$expected = ['001_master.sql', '002_inventory.sql', '003_listing.sql', '004_bom_trade.sql', '005_migration_legacy.sql', '005b_migration_templates.sql', '006_seed.sql'];
if (count($files) !== count($expected) || array_diff($expected, array_map('basename', $files)) !== []) {
    fwrite(STDERR, "SQL 文件不完整，拒绝执行\n");
    exit(1);
}

$runner = new MigrationRunner();
try {
    $runner->validateTarget($target);
} catch (Throwable $e) {
    fwrite(STDERR, 'FAIL: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}
$plan = $runner->plan($files, $target);
echo 'target: ' . $target . PHP_EOL;
echo 'files:  ' . count($files) . PHP_EOL;
echo 'tables: ' . $plan['total_tables'] . PHP_EOL;
echo 'stmts:  ' . $plan['statements'] . PHP_EOL;
echo 'mode:   ' . ($execute ? 'EXECUTE' : 'DRY-RUN') . PHP_EOL;
if (!$execute) {
    echo 'OK: dry-run; add --execute to apply' . PHP_EOL;
    exit(0);
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
    $pdo = new PDO("mysql:host={$host};port={$port};charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::MYSQL_ATTR_MULTI_STATEMENTS => true,
    ]);
    $exists = (int) $pdo->query("SELECT COUNT(*) FROM information_schema.schemata WHERE schema_name = " . $pdo->quote($target))->fetchColumn();
    if ($exists > 0) {
        $tables = (int) $pdo->query("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = " . $pdo->quote($target))->fetchColumn();
        if ($tables > 0 && !$allowExistingEmpty) {
            fwrite(STDERR, "目标库已有 {$tables} 张表；拒绝覆盖。\n");
            exit(1);
        }
    }
    foreach ($files as $file) {
        $sql = preg_replace('/^\xEF\xBB\xBF/', '', (string) file_get_contents($file)) ?? '';
        try {
            $pdo->exec($sql);
        } catch (Throwable $e) {
            fwrite(STDERR, 'FAIL ' . basename($file) . ': ' . $e->getMessage() . PHP_EOL);
            exit(1);
        }
        echo 'done ' . basename($file) . PHP_EOL;
    }
    $tables = (int) $pdo->query("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = " . $pdo->quote($target))->fetchColumn();
    echo 'tables_after: ' . $tables . PHP_EOL;
    if ($tables < 45) {
        fwrite(STDERR, "表数少于 45，视为失败\n");
        exit(1);
    }
    echo 'OK: migrated ' . $tables . ' tables' . PHP_EOL;
    exit(0);
} catch (Throwable $e) {
    fwrite(STDERR, 'FAIL: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}

<?php
declare(strict_types=1);

/**
 * 只生成迁移计划/重写 SQL，不连数据库、不执行 SQL。
 * 用法：
 *   php platform/bin/migration-plan.php mats_platform_next
 *   php platform/bin/migration-plan.php mats_platform_next --write
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    echo 'CLI only';
    exit(1);
}

require dirname(__DIR__) . '/app/bootstrap.php';

$target = $argv[1] ?? (getenv('MIGRATION_TARGET') ?: 'mats_platform_plan');
$write = in_array('--write', $argv, true);
$dir = getenv('MATERIAL_SQL_DIR') ?: 'E:/patent-sys/docs/material-platform/sql';
if (!is_dir($dir)) {
    fwrite(STDERR, "SQL 目录不存在: {$dir}\n");
    exit(2);
}
$files = glob(rtrim($dir, '/\\') . '/*.sql') ?: [];
usort($files, static function (string $a, string $b): int {
    $order = ['001_master.sql' => 1, '002_inventory.sql' => 2, '003_listing.sql' => 3, '004_bom_trade.sql' => 4, '005_migration_legacy.sql' => 5, '005b_migration_templates.sql' => 6, '006_seed.sql' => 7];
    return ($order[basename($a)] ?? 99) <=> ($order[basename($b)] ?? 99);
});

try {
    $runner = new MigrationRunner();
    $runner->validateTarget($target);
} catch (Throwable $e) {
    fwrite(STDERR, 'FAIL: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}

$plan = $runner->plan($files, $target);
echo 'target: ' . $target . PHP_EOL;
echo 'files:  ' . count($plan['files']) . PHP_EOL;
echo 'tables: ' . $plan['total_tables'] . PHP_EOL;
echo 'stmts:  ' . $plan['statements'] . PHP_EOL;
foreach ($plan['files'] as $name => $meta) {
    echo str_pad($name, 32) . count($meta['tables']) . ' tables · ' . $meta['statements'] . ' stmts' . PHP_EOL;
}

if ($write) {
    $outDir = PLATFORM_PATH . '/database/generated/' . $target;
    if (!is_dir($outDir) && !mkdir($outDir, 0777, true) && !is_dir($outDir)) {
        fwrite(STDERR, "无法创建 {$outDir}\n");
        exit(1);
    }
    foreach ($files as $file) {
        $sql = $runner->rewrite((string) file_get_contents($file), $target);
        file_put_contents($outDir . '/' . basename($file), $sql);
    }
    echo 'written: ' . $outDir . PHP_EOL;
}
echo 'OK: plan only; no SQL executed' . PHP_EOL;
exit(0);

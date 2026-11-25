<?php
declare(strict_types=1);

/**
 * 迁移审计：只读 SQL 文件，报告表数量/重复/缺失；不执行 SQL、不连库。
 * 用法：php platform/bin/migration-audit.php
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    echo 'CLI only';
    exit(1);
}

require dirname(__DIR__) . '/app/bootstrap.php';

$dir = getenv('MATERIAL_SQL_DIR') ?: 'E:/patent-sys/docs/material-platform/sql';
if (!is_dir($dir)) {
    fwrite(STDERR, "SQL 目录不存在: {$dir}\n");
    exit(2);
}
$files = glob(rtrim($dir, '/\\') . '/*.sql') ?: [];
$audit = (new SqlMigrationInspector())->audit($files);

echo 'sql dir: ' . $dir . PHP_EOL;
foreach ($audit['files'] as $name => $count) {
    echo str_pad($name, 32) . $count . ' tables' . PHP_EOL;
}
echo 'tables: ' . count($audit['tables']) . ' unique / ' . $audit['total'] . ' total' . PHP_EOL;
if ($audit['duplicates'] !== []) {
    echo 'duplicates: ' . implode(', ', $audit['duplicates']) . PHP_EOL;
}
if ($audit['missing'] !== []) {
    echo 'missing: ' . implode(', ', $audit['missing']) . PHP_EOL;
}
$ok = $audit['missing'] === [] && $audit['duplicates'] === [] && count($audit['tables']) >= 45;
echo $ok ? 'OK: ≥45 unique tables, no duplicate/missing' . PHP_EOL : 'FAIL: 检查缺失/重复/表数' . PHP_EOL;
exit($ok ? 0 : 1);

<?php
declare(strict_types=1);

/**
 * SQL 迁移审计测试（不连库、不执行 SQL）。
 * 用法：php platform/tests/migration.php
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    echo 'CLI only';
    exit(1);
}

require dirname(__DIR__) . '/app/bootstrap.php';

$pass = 0;
$fail = 0;
function check(string $name, bool $cond, string $detail = ''): void
{
    global $pass, $fail;
    if ($cond) {
        $pass++;
        echo "  PASS  {$name}" . PHP_EOL;
    } else {
        $fail++;
        echo "  FAIL  {$name}" . ($detail !== '' ? "  ({$detail})" : '') . PHP_EOL;
    }
}

echo '物格云 migration tests · v' . APP_VERSION . PHP_EOL . PHP_EOL;
$inspector = new SqlMigrationInspector();
check('必需文件 7 个', count(SqlMigrationInspector::REQUIRED_FILES) === 7);
$tables = $inspector->tables("CREATE TABLE `a` (id INT);\nCREATE TABLE IF NOT EXISTS b (id INT);\n");
check('解析 CREATE TABLE', $tables === ['a', 'b']);
check('重复表去重', count($inspector->tables("CREATE TABLE a (id INT);\nCREATE TABLE a (id INT);")) === 1);

$dir = STORAGE_PATH . '/migration-test-' . bin2hex(random_bytes(3));
mkdir($dir, 0777, true);
$map = [
    '001_master.sql' => 'CREATE TABLE a (id INT);',
    '002_inventory.sql' => 'CREATE TABLE b (id INT);',
    '003_listing.sql' => 'CREATE TABLE c (id INT);',
    '004_bom_trade.sql' => 'CREATE TABLE d (id INT);',
    '005_migration_legacy.sql' => 'CREATE TABLE e (id INT);',
    '005b_migration_templates.sql' => 'CREATE TABLE f (id INT);',
    '006_seed.sql' => 'INSERT INTO a VALUES (1);',
];
foreach ($map as $name => $sql) {
    file_put_contents($dir . '/' . $name, $sql);
}
$files = glob($dir . '/*.sql') ?: [];
$audit = $inspector->audit($files);
check('audit 6 表', $audit['total'] === 6);
check('audit 无缺失', $audit['missing'] === []);
check('audit 无重复', $audit['duplicates'] === []);

file_put_contents($dir . '/002_inventory.sql', 'CREATE TABLE a (id INT);');
$dupAudit = $inspector->audit(glob($dir . '/*.sql') ?: []);
check('重复表被发现', $dupAudit['duplicates'] === ['a']);

mkdir($dir . '/missing', 0777, true);
foreach (array_slice($map, 0, 3, true) as $name => $sql) {
    file_put_contents($dir . '/missing/' . $name, $sql);
}
$missingAudit = $inspector->audit(glob($dir . '/missing/*.sql') ?: []);
check('缺失文件被发现', count($missingAudit['missing']) === 4);

echo PHP_EOL . '结果：PASS ' . $pass . ' / FAIL ' . $fail . PHP_EOL;
exit($fail > 0 ? 1 : 0);

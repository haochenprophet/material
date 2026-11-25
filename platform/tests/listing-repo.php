<?php
declare(strict_types=1);

/**
 * 真实 listing+material 仓储测试（SQLite 内存，不连 MySQL）。
 * 用法：php platform/tests/listing-repo.php
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

echo '物格云 listing repository tests · v' . APP_VERSION . PHP_EOL . PHP_EOL;
$pdo = new PDO('sqlite::memory:');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->exec('CREATE TABLE material (id INTEGER PRIMARY KEY, part_number TEXT, description TEXT, deleted_at TEXT)');
$pdo->exec('CREATE TABLE listing (id INTEGER PRIMARY KEY, material_id INT, store_id INT, title TEXT, grade TEXT, price REAL, quantity REAL, warehouse_id INT, location_id INT, lead_time_days INT, status TEXT, deleted_at TEXT)');
$pdo->exec('CREATE TABLE warehouse (id INTEGER PRIMARY KEY, name TEXT)');
$pdo->exec('CREATE TABLE location (id INTEGER PRIMARY KEY, code TEXT)');
$pdo->exec("INSERT INTO material VALUES (1,'TE 170364-1','4Pin',NULL)");
$pdo->exec("INSERT INTO material VALUES (2,'GRM188','0402',NULL)");
$pdo->exec("INSERT INTO warehouse VALUES (1,'深圳仓')");
$pdo->exec("INSERT INTO location VALUES (1,'A-03')");
$pdo->exec("INSERT INTO listing VALUES (1,1,1,'TE 连接器','A',18.50,1240,1,1,2,'listed',NULL)");
$pdo->exec("INSERT INTO listing VALUES (2,2,2,'Murata 电容','B',0.12,86000,1,1,3,'listed',NULL)");
$pdo->exec("INSERT INTO listing VALUES (3,2,2,'待审 listing','NA',1.0,10,1,1,1,'pending_review',NULL)");
$repo = new PdoListingRepository($pdo);

check('all 2 条', count($repo->all()) === 2);
check('pending_review 不进目录', $repo->find('p3') === null);
$one = $repo->find('p1');
check('find p1', ($one['title'] ?? '') === 'TE 连接器');
check('价格格式化', ($one['price'] ?? '') === '¥18.50');
check('库存格式化', ($one['stock'] ?? '') === '库存 1,240');
check('A 级 badge', ($one['badge'] ?? '') === 'A 级质检');
check('位置合并', str_contains((string) ($one['location'] ?? ''), '深圳仓'));
check('find 不存在', $repo->find('p999') === null);
check('非数字 id 拒绝', $repo->find("p1' OR '1'='1") === null);
check('接口实现', $repo instanceof MaterialRepository);

echo PHP_EOL . '结果：PASS ' . $pass . ' / FAIL ' . $fail . PHP_EOL;
exit($fail > 0 ? 1 : 0);

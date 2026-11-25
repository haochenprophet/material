<?php
declare(strict_types=1);

/**
 * 品类配置/样本/报价查询测试。用法：php platform/tests/category-pages.php
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
    if ($cond) { $pass++; echo "  PASS  {$name}" . PHP_EOL; }
    else { $fail++; echo "  FAIL  {$name}" . ($detail !== '' ? "  ({$detail})" : '') . PHP_EOL; }
}

echo '物格云 category pages tests · v' . APP_VERSION . PHP_EOL . PHP_EOL;
$cats = MaterialCategory::all();
check('13 个品类', count($cats) === 13);
check('总样本 25270', array_sum(array_map(static fn ($c) => (int) $c['count'], $cats)) === 25270);
check('字段完整', count(array_filter($cats, static fn ($c) => isset($c['name'], $c['legacy_type'], $c['subs'], $c['params'], $c['icon']))) === 13);
check('默认品类', MaterialCategory::find(MaterialCategory::defaultKey()) !== null);
check('非法品类', MaterialCategory::find('no-such') === null);
$fixture = json_decode((string) file_get_contents(PLATFORM_PATH . '/database/fixtures/category-samples.json'), true);
check('fixture 13 类', is_array($fixture) && count($fixture['categories']) === 13);
$samples = 0;
$badSample = false;
foreach ($fixture['categories'] as $k => $c) {
    $samples += count($c['samples']);
    foreach ($c['samples'] as $s) {
        if (!isset($s['part_number'], $s['component_name'], $s['package'], $s['subcategory_key'])) { $badSample = true; }
    }
}
check('fixture 104 样本', $samples === 104);
check('样本字段', !$badSample);
check('子类映射存在', count(array_filter($fixture['categories'], static fn ($c) => isset($cats[$c['key']]))) === 13);

$pdo = new PDO('sqlite::memory:');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->exec('CREATE TABLE store (id INTEGER PRIMARY KEY,name TEXT,slug TEXT,rating REAL,rating_count INT)');
$pdo->exec('CREATE TABLE material (id INTEGER PRIMARY KEY,part_number TEXT,name TEXT,description TEXT,attributes_summary TEXT,subcategory_key TEXT,category_key TEXT,deleted_at TEXT)');
$pdo->exec('CREATE TABLE listing (id INTEGER PRIMARY KEY,title TEXT,price REAL,currency TEXT,quantity REAL,moq REAL,lead_time_days INT,grade TEXT,item_condition TEXT,warranty_days INT,status TEXT,deleted_at TEXT,material_id INT,store_id INT)');
$pdo->exec("INSERT INTO store VALUES (1,'华强北工控优选','hqb-industrial',4.8,12),(2,'苏州余料科技','suzhou-surplus',4.6,8)");
$pdo->exec("INSERT INTO material VALUES (1,'R-1','10K 电阻','0402 1%','{\"value\":\"10K\",\"package\":\"0402\"}','chip_resistor','resistor',NULL),(2,'C-1','100nF 电容','0603 50V','{\"value\":\"100nF\",\"package\":\"0603\"}','mlcc','capacitor',NULL)");
$pdo->exec("INSERT INTO listing VALUES (1,'10K 电阻',0.02,'CNY',1000,1,2,'A','surplus',30,'listed',NULL,1,1),(2,'100nF 电容',0.05,'CNY',500,10,5,'A','surplus',30,'listed',NULL,2,2)");
$repo = new PdoCategoryCatalogRepository($pdo);
$offers = $repo->offers('resistor');
check('电阻报价', count($offers) === 1 && $offers[0]['part_number'] === 'R-1');
check('参数 JSON', ($offers[0]['attrs']['value'] ?? '') === '10K');
$stats = $repo->stats('resistor');
check('统计', $stats['offers'] === 1 && $stats['merchants'] === 1);
check('商家过滤', count($repo->offers('resistor', ['merchant' => 'hqb-industrial'])) === 1);
check('无匹配', $repo->offers('resistor', ['merchant' => 'suzhou-surplus']) === []);
check('子类过滤', $repo->offers('resistor', ['sub' => 'mlcc']) === []);

echo PHP_EOL . '结果：PASS ' . $pass . ' / FAIL ' . $fail . PHP_EOL;
exit($fail > 0 ? 1 : 0);

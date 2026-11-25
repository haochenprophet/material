<?php
declare(strict_types=1);

/**
 * 商家商城仓储测试（SQLite）。用法：php platform/tests/storefront.php
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

echo '物格云 storefront tests · v' . APP_VERSION . PHP_EOL . PHP_EOL;
$pdo = new PDO('sqlite::memory:');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->exec('CREATE TABLE store (id INTEGER PRIMARY KEY, uuid TEXT, org_id INT, name TEXT, slug TEXT, logo TEXT, description TEXT, status TEXT, rating REAL, rating_count INT)');
$pdo->exec('CREATE TABLE merchant_profile (id INTEGER PRIMARY KEY, store_id INT, merchant_code TEXT, contact_name TEXT, phone TEXT, email TEXT, address TEXT, banner TEXT, description TEXT, status TEXT)');
$pdo->exec('CREATE TABLE merchant_credit (store_id INTEGER PRIMARY KEY, review_count INT, avg_rating REAL, score INT, grade TEXT, material_count INT, listed_count INT, verified_count INT, positive_rate REAL)');
$pdo->exec('CREATE TABLE material (id INTEGER PRIMARY KEY, part_number TEXT, name TEXT, deleted_at TEXT)');
$pdo->exec('CREATE TABLE material_credit (material_id INTEGER PRIMARY KEY, score INT, grade TEXT, avg_rating REAL)');
$pdo->exec('CREATE TABLE listing (id INTEGER PRIMARY KEY, store_id INT, material_id INT, title TEXT, price REAL, quantity REAL, grade TEXT, status TEXT, deleted_at TEXT)');
$pdo->exec('CREATE TABLE material_attachment (id INTEGER PRIMARY KEY, material_id INT, type TEXT, path TEXT, is_primary INT, sort INT)');
$pdo->exec("INSERT INTO store VALUES (1,'u1',1,'HQB','hqb','', '','active',4.8,12)");
$pdo->exec("INSERT INTO merchant_profile VALUES (1,1,'HQB001','陈工','1','a@b.c','深圳','','', 'active')");
$pdo->exec("INSERT INTO merchant_credit VALUES (1,8,4.5,73,'A',3,2,6,80)");
$pdo->exec("INSERT INTO material VALUES (1,'TE-1','TE',NULL)");
$pdo->exec("INSERT INTO material_credit VALUES (1,81,'AA',4.5)");
$pdo->exec("INSERT INTO listing VALUES (1,1,1,'listed item',18.5,100,'A','listed',NULL)");
$pdo->exec("INSERT INTO listing VALUES (2,1,1,'pending item',18.5,100,'A','pending_review',NULL)");
$pdo->exec("INSERT INTO material_attachment VALUES (1,1,'image','/x.svg',1,0)");

$repo = new PdoStoreRepository($pdo);
$store = $repo->findBySlug('hqb');
check('按 slug 找店', ($store['id'] ?? 0) === 1 && ($store['merchant_code'] ?? '') === 'HQB001');
check('信用字段', (int) ($store['score'] ?? 0) === 73 && ($store['grade'] ?? '') === 'A');
$listings = $repo->listings(1);
check('只取 listed', count($listings) === 1 && $listings[0]['title'] === 'listed item');
check('商品带物料信用', (int) ($listings[0]['material_score'] ?? 0) === 81);
check('商品带图片', ($listings[0]['image_path'] ?? '') === '/x.svg');
check('非法状态拒绝', (static function () use ($repo) { try { $repo->setStatus(1, 'delete'); return false; } catch (Throwable) { return true; } })());
check('暂停店铺', $repo->setStatus(1, 'paused') === true);
$all = $repo->all(true);
check('只列 active', count($all) === 0);

echo PHP_EOL . '结果：PASS ' . $pass . ' / FAIL ' . $fail . PHP_EOL;
exit($fail > 0 ? 1 : 0);

<?php
declare(strict_types=1);

/**
 * 卖家数据隔离测试（SQLite）。
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

echo '物格云 data isolation tests · v' . APP_VERSION . PHP_EOL . PHP_EOL;
putenv('AUTH_SOURCE=demo');
Auth::logout();
Auth::attempt('seller@demo', 'demo123');
check('demo seller 店铺 6', MerchantContext::storeId() === 6);
check('seller 可访问 6', MerchantContext::canAccessStore(6));
check('seller 不可访问 7', MerchantContext::canAccessStore(7) === false);
Auth::logout();
Auth::attempt('buyer@demo', 'demo123');
check('buyer 无店铺', MerchantContext::storeId() === null);
Auth::logout();
Auth::attempt('admin@demo', 'demo123');
check('admin 跨店', MerchantContext::canAccessStore(7) === true && MerchantContext::storeId() === null);

$pdo = new PDO('sqlite::memory:');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->exec('CREATE TABLE material (id INTEGER PRIMARY KEY, part_number TEXT, deleted_at TEXT)');
$pdo->exec('CREATE TABLE listing (id INTEGER PRIMARY KEY, store_id INT, material_id INT, title TEXT, status TEXT, deleted_at TEXT)');
$pdo->exec("INSERT INTO material VALUES (1,'A1',NULL),(2,'B2',NULL),(3,'C3',NULL)");
$pdo->exec("INSERT INTO listing VALUES (1,6,1,'a','listed',NULL),(2,7,2,'b','listed',NULL),(3,6,3,'c','listed',NULL)");
$pdo->exec('CREATE TABLE material_attachment (id INTEGER PRIMARY KEY, material_id INT, type TEXT, path TEXT, is_primary INT, sort INT, deleted_at TEXT)');
$imgRepo = new PdoMaterialImageRepository($pdo);
check('物料 1 属店 6', $imgRepo->storeIdForMaterial(1) === 6);
check('物料 2 属店 7', $imgRepo->storeIdForMaterial(2) === 7);
check('物料 3 属店 6', $imgRepo->storeIdForMaterial(3) === 6);

echo PHP_EOL . '结果：PASS ' . $pass . ' / FAIL ' . $fail . PHP_EOL;
exit($fail > 0 ? 1 : 0);

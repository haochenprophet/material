<?php
declare(strict_types=1);

/**
 * 卖家提报列表测试（SQLite）。用法：php platform/tests/seller-listings.php
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

echo '物格云 seller listings tests · v' . APP_VERSION . PHP_EOL . PHP_EOL;
$pdo = new PDO('sqlite::memory:');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->exec('CREATE TABLE material (id INTEGER PRIMARY KEY, part_number TEXT, deleted_at TEXT)');
$pdo->exec('CREATE TABLE listing (id INTEGER PRIMARY KEY, material_id INT, title TEXT, status TEXT, price REAL, quantity REAL, created_at TEXT, deleted_at TEXT)');
$pdo->exec('CREATE TABLE listing_review (id INTEGER PRIMARY KEY AUTOINCREMENT, listing_id INT, reviewer_id INT, action TEXT, reason TEXT)');
$pdo->exec("INSERT INTO material VALUES (1,'TE-1',NULL)");
$pdo->exec("INSERT INTO listing VALUES (1,1,'待审', 'pending_review', 10, 1, '2026-11-24', NULL)");
$pdo->exec("INSERT INTO listing VALUES (2,1,'已上架','listed',20,2,'2026-11-24',NULL)");
$pdo->exec("INSERT INTO listing VALUES (3,1,'已驳回','rejected',30,3,'2026-11-24',NULL)");
$pdo->exec("INSERT INTO listing_review (listing_id,reviewer_id,action,reason) VALUES (3,1,'reject','资料不全')");
$repo = new PdoSellerListingRepository($pdo);
$all = $repo->all();
check('返回 3 条', count($all) === 3);
check('倒序', (int) $all[0]['id'] === 3);
check('状态标签', PdoSellerListingRepository::label('pending_review') === '待审核');
check('badge kind', PdoSellerListingRepository::kind('listed') === 'ok');
check('驳回原因', ($all[0]['review_reason'] ?? '') === '资料不全');
check('未知状态', PdoSellerListingRepository::label('x') === 'x');
check('limit 夹取', count($repo->all(1)) === 1);

echo PHP_EOL . '结果：PASS ' . $pass . ' / FAIL ' . $fail . PHP_EOL;
exit($fail > 0 ? 1 : 0);

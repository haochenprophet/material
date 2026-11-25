<?php
declare(strict_types=1);

/**
 * 审核写入测试（SQLite 内存）。用法：php platform/tests/listing-review.php
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

echo '物格云 listing review tests · v' . APP_VERSION . PHP_EOL . PHP_EOL;
$pdo = new PDO('sqlite::memory:');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->exec('CREATE TABLE material (id INTEGER PRIMARY KEY, part_number TEXT, deleted_at TEXT)');
$pdo->exec('CREATE TABLE listing (id INTEGER PRIMARY KEY, material_id INT, title TEXT, price REAL, quantity REAL, status TEXT, published_at TEXT, updated_at TEXT, created_at TEXT, deleted_at TEXT)');
$pdo->exec('CREATE TABLE listing_review (id INTEGER PRIMARY KEY AUTOINCREMENT, listing_id INT, reviewer_id INT, action TEXT, reason TEXT, created_at TEXT)');
$pdo->exec('CREATE TABLE audit_log (id INTEGER PRIMARY KEY AUTOINCREMENT, actor_id INT, actor_type TEXT, action TEXT, object_type TEXT, object_id TEXT, after_json TEXT, request_id TEXT)');
$pdo->exec("INSERT INTO material VALUES (1,'TE-1',NULL)");
$pdo->exec("INSERT INTO listing VALUES (1,1,'待审 1',18.5,100,'pending_review',NULL,NULL,'2026-11-24',NULL)");
$pdo->exec("INSERT INTO listing VALUES (2,1,'待审 2',20,50,'pending_review',NULL,NULL,'2026-11-24',NULL)");
$pdo->exec("INSERT INTO listing VALUES (3,1,'已上架',5,10,'listed',NULL,NULL,'2026-11-24',NULL)");
$review = new PdoListingReview($pdo);

check('pending 2 条', count($review->pending()) === 2);
check('approve id1', $review->approve(1, 7) === true);
check('approve 后 listed', $pdo->query("SELECT status FROM listing WHERE id=1")->fetchColumn() === 'listed');
check('写 listing_review', (int) $pdo->query('SELECT COUNT(*) FROM listing_review WHERE listing_id=1 AND action="approve" AND reviewer_id=7')->fetchColumn() === 1);
check('写 audit_log', (int) $pdo->query('SELECT COUNT(*) FROM audit_log WHERE object_id="1" AND action="listing.approve" AND actor_id=7')->fetchColumn() === 1);
check('重复 approve 不生效', $review->approve(1, 7) === false);
check('reject id2 带原因', $review->reject(2, 8, '资料不全') === true);
check('reject 后 rejected', $pdo->query("SELECT status FROM listing WHERE id=2")->fetchColumn() === 'rejected');
check('reject 原因入库', $pdo->query('SELECT reason FROM listing_review WHERE listing_id=2 AND action="reject"')->fetchColumn() === '资料不全');
check('reject reviewer 入库', (int) $pdo->query('SELECT reviewer_id FROM listing_review WHERE listing_id=2 AND action="reject"')->fetchColumn() === 8);
check('pending 清零', count($review->pending()) === 0);

echo PHP_EOL . '结果：PASS ' . $pass . ' / FAIL ' . $fail . PHP_EOL;
exit($fail > 0 ? 1 : 0);

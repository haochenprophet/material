<?php
declare(strict_types=1);

/**
 * 点评回复/评分分布测试（SQLite）。用法：php platform/tests/review-reply.php
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

echo '物格云 review reply tests · v' . APP_VERSION . PHP_EOL . PHP_EOL;
$pdo = new PDO('sqlite::memory:');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->exec('CREATE TABLE material_review (id INTEGER PRIMARY KEY, material_id INT, listing_id INT, store_id INT, status TEXT, rating INT)');
$pdo->exec('CREATE TABLE review_reply (id INTEGER PRIMARY KEY AUTOINCREMENT, review_id INT, store_id INT, author_name TEXT, content TEXT, created_at TEXT)');
$pdo->exec("INSERT INTO material_review VALUES (1,1,1,6,'visible',5),(2,1,1,6,'visible',4),(3,1,1,6,'visible',5),(4,1,1,6,'hidden',1)");
$repo = new PdoReviewRepository($pdo);
$dist = $repo->distribution(1);
check('分布只算 visible', $dist[5] === 2 && $dist[4] === 1 && $dist[1] === 0);
check('回复初始空', $repo->replies(1) === []);
$id = $repo->reply(1, ['name' => '华强北客服'], '已安排复检。');
check('回复写入', $id > 0);
check('回复可读', count($repo->replies(1)) === 1 && $repo->replies(1)[0]['content'] === '已安排复检。');
check('短回复拒绝', (static function () use ($repo) { try { $repo->reply(1, ['name' => 'x'], 'a'); return false; } catch (Throwable) { return true; } })());
check('隐藏点评不可回复', (static function () use ($repo) { try { $repo->reply(4, ['name' => 'x'], 'hello'); return false; } catch (Throwable) { return true; } })());

echo PHP_EOL . '结果：PASS ' . $pass . ' / FAIL ' . $fail . PHP_EOL;
exit($fail > 0 ? 1 : 0);

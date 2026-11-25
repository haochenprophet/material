<?php
declare(strict_types=1);

/**
 * 点评审核/申诉测试（SQLite）。用法：php platform/tests/review-moderation.php
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

echo '物格云 review moderation tests · v' . APP_VERSION . PHP_EOL . PHP_EOL;
$pdo = new PDO('sqlite::memory:');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->exec('CREATE TABLE material_review (id INTEGER PRIMARY KEY, material_id INT, listing_id INT, store_id INT, author_name TEXT, content TEXT, rating INT, status TEXT, moderation_note TEXT, moderated_by INT, moderated_at TEXT)');
$pdo->exec('CREATE TABLE review_appeal (id INTEGER PRIMARY KEY AUTOINCREMENT, review_id INT, store_id INT, reason TEXT, status TEXT, resolver_id INT, resolved_at TEXT, created_at TEXT)');
$pdo->exec('CREATE TABLE store (id INTEGER PRIMARY KEY, name TEXT)');
$pdo->exec('CREATE TABLE audit_log (id INTEGER PRIMARY KEY AUTOINCREMENT, actor_id INT, actor_type TEXT, action TEXT, object_type TEXT, object_id TEXT, before_json TEXT, after_json TEXT, request_id TEXT)');
$pdo->exec("INSERT INTO store VALUES (6,'HQB')");
$pdo->exec("INSERT INTO material_review VALUES (1,1,1,6,'A','待审','4','pending',NULL,NULL,NULL)");
$pdo->exec("INSERT INTO material_review VALUES (2,1,1,6,'B','可见','5','visible',NULL,NULL,NULL)");
$repo = new PdoReviewRepository($pdo);
check('通过 pending', $repo->approve(1, 9, '资料完整') === true);
check('状态 visible', $pdo->query('SELECT status FROM material_review WHERE id=1')->fetchColumn() === 'visible');
check('备注/审核人', $pdo->query('SELECT moderation_note FROM material_review WHERE id=1')->fetchColumn() === '资料完整');
check('审计写入', (int) $pdo->query("SELECT COUNT(*) FROM audit_log WHERE object_id='1'")->fetchColumn() === 1);
check('隐藏 visible', $repo->hide(2, 9, '广告') === true);
check('隐藏后申诉', $repo->appeal(2, '该点评是恶意差评') > 0);
$appeals = $repo->appeals();
check('申诉列表', count($appeals) === 1 && $appeals[0]['status'] === 'pending');
check('处理申诉', $repo->resolveAppeal((int) $appeals[0]['id'], 'accepted', 9) === true);
check('非法申诉状态', (static function () use ($repo) { try { $repo->resolveAppeal(1, 'x'); return false; } catch (Throwable) { return true; } })());

echo PHP_EOL . '结果：PASS ' . $pass . ' / FAIL ' . $fail . PHP_EOL;
exit($fail > 0 ? 1 : 0);

<?php
declare(strict_types=1);

/**
 * 评论树测试（SQLite）。用法：php platform/tests/comment-tree.php
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

echo '物格云 comment tree tests · v' . APP_VERSION . PHP_EOL . PHP_EOL;
$pdo = new PDO('sqlite::memory:');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->exec('CREATE TABLE material_comment (id INTEGER PRIMARY KEY AUTOINCREMENT, uuid TEXT, material_id INT, parent_id INT, user_id INT, author_name TEXT, author_role TEXT, content TEXT, status TEXT, depth INT, path TEXT, created_at TEXT)');
$pdo->exec("INSERT INTO material_comment (uuid,material_id,parent_id,user_id,author_name,author_role,content,status,depth,path,created_at) VALUES ('u1',1,NULL,NULL,'张工','buyer','root','visible',0,'1','2026-11-25 08:00')");
$pdo->exec("INSERT INTO material_comment (uuid,material_id,parent_id,user_id,author_name,author_role,content,status,depth,path,created_at) VALUES ('u2',1,1,NULL,'李采购','buyer','child','visible',1,'1/2','2026-11-25 08:10')");
$pdo->exec("INSERT INTO material_comment (uuid,material_id,parent_id,user_id,author_name,author_role,content,status,depth,path,created_at) VALUES ('u3',1,2,NULL,'客服','seller','grand','visible',2,'1/2/3','2026-11-25 08:20')");
$pdo->exec("INSERT INTO material_comment (uuid,material_id,parent_id,user_id,author_name,author_role,content,status,depth,path,created_at) VALUES ('u4',2,NULL,NULL,'王工','buyer','other','visible',0,'4','2026-11-25 08:30')");
$repo = new PdoCommerceRepository($pdo);
$tree = $repo->commentsTree(1);
check('根评论 1 条', count($tree) === 1);
check('三级树', count($tree[0]['replies']) === 1 && count($tree[0]['replies'][0]['replies']) === 1);
check('跨物料隔离', $tree[0]['content'] === 'root' && $repo->commentsTree(2)[0]['content'] === 'other');
$id = $repo->addComment(1, 3, ['id' => 7, 'name' => '王工', 'role' => 'buyer'], 'new reply');
check('新增回复 id', $id > 0);
$tree2 = $repo->commentsTree(1);
check('新增后深层 3', count($tree2[0]['replies'][0]['replies'][0]['replies']) === 1);
check('超 3 层拒绝', (static function () use ($repo) { try { $repo->addComment(1, 5, ['name' => 'x'], 'too deep'); return false; } catch (Throwable) { return true; } })());
check('短内容拒绝', (static function () use ($repo) { try { $repo->addComment(1, null, ['name' => 'x'], 'a'); return false; } catch (Throwable) { return true; } })());

echo PHP_EOL . '结果：PASS ' . $pass . ' / FAIL ' . $fail . PHP_EOL;
exit($fail > 0 ? 1 : 0);

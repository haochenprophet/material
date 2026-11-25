<?php
declare(strict_types=1);

/**
 * DB 认证仓储测试（SQLite 内存）。用法：php platform/tests/auth-db.php
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

echo '物格云 DB auth tests · v' . APP_VERSION . PHP_EOL . PHP_EOL;
$pdo = new PDO('sqlite::memory:');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->exec('CREATE TABLE `user` (id INTEGER PRIMARY KEY, email TEXT, phone TEXT, display_name TEXT, password_hash TEXT, status TEXT, deleted_at TEXT)');
$pdo->exec('CREATE TABLE org_member (org_id INT, user_id INT, role_id INT, status TEXT, PRIMARY KEY (org_id,user_id))');
$pdo->exec('CREATE TABLE role (id INTEGER PRIMARY KEY, code TEXT, name TEXT)');
$pdo->exec("INSERT INTO role VALUES (1,'admin','管理员')");
$pdo->exec("INSERT INTO role VALUES (2,'seller_admin','卖家')");
$pdo->exec("INSERT INTO role VALUES (3,'warehouse','仓管')");
$hash = password_hash('demo123', PASSWORD_DEFAULT);
$stmt = $pdo->prepare('INSERT INTO `user` (email,phone,display_name,password_hash,status,deleted_at) VALUES (?,?,?,?,?,?)');
$stmt->execute(['dbadmin@demo', null, 'DB Admin', $hash, 'active', null]);
$stmt->execute(['seller@db', null, 'DB Seller', $hash, 'active', null]);
$stmt->execute(['off@db', null, 'Off', $hash, 'disabled', null]);
$pdo->exec("INSERT INTO org_member VALUES (1,1,1,'active')");
$pdo->exec("INSERT INTO org_member VALUES (1,2,2,'active')");
$pdo->exec("INSERT INTO org_member VALUES (1,3,1,'active')");
$repo = new PdoAuthRepository($pdo);

$admin = $repo->attempt('dbadmin@demo', 'demo123');
check('DB 管理员登录', ($admin['role'] ?? '') === 'admin');
check('名称/账号', ($admin['name'] ?? '') === 'DB Admin' && ($admin['account'] ?? '') === 'dbadmin@demo');
check('错误密码拒绝', $repo->attempt('dbadmin@demo', 'bad') === null);
check('禁用用户拒绝', $repo->attempt('off@db', 'demo123') === null);
check('不存在拒绝', $repo->attempt('nope@db', 'demo123') === null);
$seller = $repo->attempt('seller@db', 'demo123');
check('seller_admin 映射 seller', ($seller['role'] ?? '') === 'seller');
check('warehouse 映射 qc', $repo->mapRole('warehouse') === 'qc');
check('未知角色默认 buyer', $repo->mapRole('unknown') === 'buyer');

echo PHP_EOL . '结果：PASS ' . $pass . ' / FAIL ' . $fail . PHP_EOL;
exit($fail > 0 ? 1 : 0);

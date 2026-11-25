<?php
declare(strict_types=1);

/**
 * 只读真实库测试。未设置 DB_USER 时 SKIP（不阻塞离线 CI）。
 * 用法：DB_USER=... DB_PASS=... php platform/tests/db-live.php
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

echo '物格云 live DB tests · v' . APP_VERSION . PHP_EOL . PHP_EOL;
$user = (string) getenv('DB_USER');
$passwd = (string) getenv('DB_PASS');
$name = getenv('DB_NAME') ?: 'mats_platform_next2';
if ($user === '') {
    echo 'SKIP: DB_USER 未设置' . PHP_EOL;
    exit(0);
}
if (!preg_match('/^mats[a-z0-9_]*$/', $name) || $name === 'materiel') {
    echo 'FAIL: 数据库名必须是 mats*' . PHP_EOL;
    exit(1);
}
$host = getenv('DB_HOST') ?: '127.0.0.1';
$port = (int) (getenv('DB_PORT') ?: 3306);
try {
    $pdo = new PDO("mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4", $user, $passwd, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (Throwable $e) {
    echo 'FAIL: ' . $e->getMessage() . PHP_EOL;
    exit(1);
}
$tables = (int) $pdo->query("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema=" . $pdo->quote($name))->fetchColumn();
check('表数 >=45', $tables >= 45, (string) $tables);
check('表数 92', $tables === 92, (string) $tables);
$role = (int) $pdo->query('SELECT COUNT(*) FROM role')->fetchColumn();
check('role 10', $role === 10, (string) $role);
$perm = (int) $pdo->query('SELECT COUNT(*) FROM permission')->fetchColumn();
check('permission 25', $perm === 25, (string) $perm);
$warehouse = (int) $pdo->query('SELECT COUNT(*) FROM warehouse')->fetchColumn();
check('warehouse 3', $warehouse === 3, (string) $warehouse);
$legacy = (int) $pdo->query("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='materiel'")->fetchColumn();
check('旧 materiel 13 表', $legacy === 13, (string) $legacy);

echo PHP_EOL . '结果：PASS ' . $pass . ' / FAIL ' . $fail . PHP_EOL;
exit($fail > 0 ? 1 : 0);

<?php
declare(strict_types=1);

/**
 * 数据层测试：只测 DSN/接口/演示仓储，不连接数据库。
 * 用法：php platform/tests/db.php
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

echo '物格云 data-layer tests · v' . APP_VERSION . PHP_EOL . PHP_EOL;
check('DSN 是 mysql', str_starts_with(Database::dsn(), 'mysql:'));
check('未配置账号不连接', Database::configured() === false || getenv('DB_USER') !== '');
$repo = new DemoMaterialRepository();
check('仓储 all 4 条', count($repo->all()) === 4);
check('仓储 find p1', ($repo->find('p1')['id'] ?? '') === 'p1');
check('仓储 find 不存在', $repo->find('nope') === null);
check('接口实现', $repo instanceof MaterialRepository);
$threw = false;
if (!Database::configured()) {
    try {
        Database::pdo();
    } catch (RuntimeException) {
        $threw = true;
    }
    check('未配置 pdo 抛异常', $threw);
} else {
    check('已配置 PDO 可连接', Database::pdo() instanceof PDO);
}

echo PHP_EOL . '结果：PASS ' . $pass . ' / FAIL ' . $fail . PHP_EOL;
exit($fail > 0 ? 1 : 0);

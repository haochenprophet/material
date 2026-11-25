<?php
declare(strict_types=1);

/**
 * 登录限流测试（显式时间，无 sleep）。
 * 用法：php platform/tests/throttle.php
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

echo '物格云 login throttle tests · v' . APP_VERSION . PHP_EOL . PHP_EOL;
$t = new LoginThrottle();
$account = 'user@example.com';
$t->clear($account);
check('初始允许', $t->check($account, 1000)['allowed'] === true);
for ($i = 1; $i <= 4; $i++) {
    $t->failure($account, 1000 + $i);
}
check('4 次仍允许', $t->check($account, 1005)['allowed'] === true);
$t->failure($account, 1006);
$locked = $t->check($account, 1007);
check('第 5 次锁定', $locked['allowed'] === false && $locked['retry_after'] > 0);
check('锁定剩余 900 内', $locked['retry_after'] <= LoginThrottle::LOCK_SECONDS);

$t->clear($account);
check('clear 后允许', $t->check($account, 1008)['allowed'] === true);

for ($i = 1; $i <= 5; $i++) {
    $t->failure($account, 2000 + $i);
}
check('锁定期满后窗口重置', $t->check($account, 3000)['allowed'] === true);

$t2 = new LoginThrottle();
$t2->clear('a@example.com');
$t2->clear('b@example.com');
for ($i = 1; $i <= 5; $i++) {
    $t2->failure('a@example.com', 3000 + $i);
}
check('账号隔离', $t2->check('b@example.com', 3006)['allowed'] === true);

echo PHP_EOL . '结果：PASS ' . $pass . ' / FAIL ' . $fail . PHP_EOL;
exit($fail > 0 ? 1 : 0);

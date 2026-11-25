<?php
declare(strict_types=1);

/**
 * 点评防刷策略测试。用法：php platform/tests/review-policy.php
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

echo '物格云 review policy tests · v' . APP_VERSION . PHP_EOL . PHP_EOL;
check('DB 用户 key', ReviewPolicy::key(42, 'x') === 'user:42');
$k1 = ReviewPolicy::key(null, 'Buyer@Demo');
$k2 = ReviewPolicy::key(null, 'buyer@demo');
check('Demo key 稳定/大小写', $k1 === $k2 && str_starts_with($k1, 'demo:'));
check('空账号 anon', ReviewPolicy::key(null, '  ') === 'anon');
check('首次允许', ReviewPolicy::canCreate(false, 0));
check('已评拒绝', ReviewPolicy::canCreate(true, 0) === false);
check('频率拒绝', ReviewPolicy::canCreate(false, 5) === false);
check('4 次允许', ReviewPolicy::canCreate(false, 4));
check('原因-已评', ReviewPolicy::reason(true, 0) !== '');
check('原因-频率', ReviewPolicy::reason(false, 5) !== '');

echo PHP_EOL . '结果：PASS ' . $pass . ' / FAIL ' . $fail . PHP_EOL;
exit($fail > 0 ? 1 : 0);

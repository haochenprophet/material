<?php
declare(strict_types=1);

/**
 * 认证/权限矩阵测试。用法：php platform/tests/auth.php
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

echo '物格云 auth tests · v' . APP_VERSION . PHP_EOL . PHP_EOL;
putenv('AUTH_SOURCE=demo');
Auth::logout();
check('初始未登录', !Auth::check());
check('错误密码拒绝', !Auth::attempt('admin@demo', 'wrong'));
check('未知账号拒绝', !Auth::attempt('nobody@demo', 'demo123'));
check('buyer 登录', Auth::attempt('buyer@demo', 'demo123') && Auth::role() === 'buyer');
check('demo 无 DB id', Auth::id() === null);
check('buyer 可看目录', Auth::can('catalog.view'));
check('buyer 不可看后台', !Auth::can('admin.view'));
check('buyer 不可审批结算', !Auth::can('settlement.approve'));
Auth::logout();
check('logout 后未登录', !Auth::check());

check('seller 登录', Auth::attempt('seller@demo', 'demo123') && Auth::role() === 'seller');
check('seller 可提报', Auth::can('listing.create'));
check('seller 可看结算', Auth::can('settlement.view'));
check('seller 不可质检', !Auth::can('qc.report'));

Auth::logout();
check('qc 登录', Auth::attempt('qc@demo', 'demo123') && Auth::role() === 'qc');
check('qc 可质检', Auth::can('qc.report'));
check('qc 不可审批结算', !Auth::can('settlement.approve'));

Auth::logout();
check('finance 登录', Auth::attempt('finance@demo', 'demo123') && Auth::role() === 'finance');
check('finance 可审批结算', Auth::can('settlement.approve'));
check('finance 不可提报', !Auth::can('listing.create'));

Auth::logout();
check('admin 登录', Auth::attempt('admin@demo', 'demo123') && Auth::role() === 'admin');
check('admin 全权限', Auth::can('admin.view') && Auth::can('qc.report') && Auth::can('settlement.approve'));
check('未知角色无权限', !Rbac::can('guest', 'admin.view'));
check('角色数 5', count(Rbac::roles()) === 5);
check('矩阵非空', count(Rbac::matrix()) === 5);

echo PHP_EOL . '结果：PASS ' . $pass . ' / FAIL ' . $fail . PHP_EOL;
exit($fail > 0 ? 1 : 0);

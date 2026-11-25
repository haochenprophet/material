<?php
declare(strict_types=1);

/**
 * 订单展示/会话存储测试。用法：php platform/tests/order-presenter.php
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

echo '物格云 order presenter tests · v' . APP_VERSION . PHP_EOL . PHP_EOL;
putenv('AUTH_SOURCE=demo');
Auth::logout();
Auth::attempt('seller@demo', 'demo123');
$service = new OrderService();
$order = $service->create(
    [['mpn' => 'TE 170364-1', 'qty' => 10, 'unit' => 18.50]],
    ['qc_fee' => 20, 'fulfillment_fee' => 10, 'shipping' => 5, 'service_rate' => 0.03, 'tax_rate' => 0.13]
);
$view = (new OrderPresenter())->present($order);
check('订单号', ($view['no'] ?? '') === $order['id']);
check('待支付标签', ($view['status'] ?? '') === '待支付');
check('金额 225.55', ($view['amount'] ?? '') === '¥225.55');
check('买家为当前用户', ($view['buyer'] ?? '') === '华强北-01');
check('订单行数量', ($view['lines'][0][1] ?? '') === '10');
check('时间线 4 条', count($view['timeline'] ?? []) === 4);

$store = new OrderStore();
$store->put($order);
check('会话存储可读', ($store->get($order['id'])['id'] ?? '') === $order['id']);
check('不存在返回 null', $store->get('nope') === null);

$paid = $service->transition($order, 'paid');
$viewPaid = (new OrderPresenter())->present($paid);
check('已支付标签', ($viewPaid['status'] ?? '') === '已支付');

echo PHP_EOL . '结果：PASS ' . $pass . ' / FAIL ' . $fail . PHP_EOL;
exit($fail > 0 ? 1 : 0);

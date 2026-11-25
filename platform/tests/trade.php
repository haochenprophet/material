<?php
declare(strict_types=1);

/**
 * 订单/结算服务测试。用法：php platform/tests/trade.php
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

echo '物格云 trade tests · v' . APP_VERSION . PHP_EOL . PHP_EOL;
$orders = new OrderService();
$settlements = new SettlementService();
$lines = [['qty' => 10, 'unit' => 18.50]];
$opts = ['qc_fee' => 20, 'fulfillment_fee' => 10, 'shipping' => 5, 'service_rate' => 0.03, 'tax_rate' => 0.13];

$order = $orders->create($lines, $opts);
check('订单 ID 前缀', str_starts_with($order['id'], 'SO-'));
check('初始待支付', $order['status'] === 'pending_payment');
check('订单小计 225.55', abs($order['pricing']['subtotal'] - 225.55) < 0.001);
check('订单可支付', $orders->can($order, 'paid'));
check('订单不可直发', !$orders->can($order, 'shipped'));

$paid = $orders->transition($order, 'paid');
check('支付后 paid', $paid['status'] === 'paid');
$allocating = $orders->transition($paid, 'allocating');
$shipped = $orders->transition($allocating, 'shipped');
$received = $orders->transition($shipped, 'received');
$completed = $orders->transition($received, 'completed');
check('完整履约到 completed', $completed['status'] === 'completed');

$threw = false;
try {
    $orders->transition($order, 'shipped');
} catch (DomainException) {
    $threw = true;
}
check('非法流转抛异常', $threw);

$bad = false;
try {
    $orders->create([]);
} catch (InvalidArgumentException) {
    $bad = true;
}
check('空行拒绝', $bad);
$bad = false;
try {
    $orders->create([['qty' => 0, 'unit' => 1]]);
} catch (InvalidArgumentException) {
    $bad = true;
}
check('数量 0 拒绝', $bad);

$settlement = $settlements->fromOrder($order, ['commission_rate' => 0.20, 'qc' => 20, 'logistics' => 5]);
check('结算 ID 前缀', str_starts_with($settlement['id'], 'ST-'));
check('结算初始 pending', $settlement['status'] === 'pending');
check('佣金 20%', abs($settlement['commission'] - 37.00) < 0.001);
check('卖家应结 123', abs($settlement['seller_net'] - 123.00) < 0.001);

$frozen = $settlements->transition($settlement, 'frozen');
$approved = $settlements->transition($frozen, 'approved');
$paidS = $settlements->transition($approved, 'paid');
$reconciled = $settlements->transition($paidS, 'reconciled');
check('结算到 reconciled', $reconciled['status'] === 'reconciled');

$bad = false;
try {
    $settlements->transition($settlement, 'paid');
} catch (DomainException) {
    $bad = true;
}
check('结算跳级拒绝', $bad);

$clamp = Pricing::consignment(100, 0.20, 100, 10, 0);
check('卖家应结不为负', $clamp['seller_net'] === 0.0);

echo PHP_EOL . '结果：PASS ' . $pass . ' / FAIL ' . $fail . PHP_EOL;
exit($fail > 0 ? 1 : 0);

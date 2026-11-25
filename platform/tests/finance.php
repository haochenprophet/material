<?php
declare(strict_types=1);

/**
 * 支付/复式记账测试。用法：php platform/tests/finance.php
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
function throws(callable $fn): bool
{
    try {
        $fn();
        return false;
    } catch (Throwable) {
        return true;
    }
}

echo '物格云 finance tests · v' . APP_VERSION . PHP_EOL . PHP_EOL;
$payments = new PaymentService();
$ledger = new LedgerService();
$order = ['id' => 'SO-TEST', 'pricing' => ['total' => 225.55]];

$pay = $payments->create($order);
check('支付 ID 前缀', str_starts_with($pay['id'], 'PAY-'));
check('支付 created', $pay['status'] === 'created');
check('支付金额 225.55', abs($pay['amount'] - 225.55) < 0.001);

$captured = $payments->capture($pay, 'TXN-001');
check('捕获 succeeded', $captured['status'] === 'succeeded');
check('transaction_id 写入', $captured['transaction_id'] === 'TXN-001');

$refunded = $payments->refund($captured);
check('全额退款 refunded', $refunded['status'] === 'refunded');

$pay2 = $payments->create($order);
$cap2 = $payments->capture($pay2, 'TXN-002');
$partial = $payments->refund($cap2, 100);
check('部分退款 partial_refunded', $partial['status'] === 'partial_refunded');
check('退款金额 100', abs($partial['refunded_amount'] - 100) < 0.001);

check('超额退款拒绝', throws(fn () => $payments->refund($cap2, 999)));
check('空 transaction 拒绝', throws(fn () => $payments->capture($pay2, '')));
check('0 金额订单拒绝', throws(fn () => $payments->create(['id' => 'X', 'pricing' => ['total' => 0]])));

$batch = $ledger->post('SO-TEST-PAY', [
    ['account' => 'payment_gateway', 'debit' => 225.55],
    ['account' => 'seller_payable', 'credit' => 123.00],
    ['account' => 'platform_revenue', 'credit' => 37.00],
    ['account' => 'tax_payable', 'credit' => 65.55],
]);
check('分录平衡', abs($batch['debit'] - $batch['credit']) < 0.001);
$balance = $ledger->balance();
check('gateway 余额 225.55', abs(($balance['payment_gateway'] ?? 0) - 225.55) < 0.001);
check('seller 余额 -123', abs(($balance['seller_payable'] ?? 0) + 123.00) < 0.001);
check('借贷总额相等', abs($ledger->totals()['debit'] - $ledger->totals()['credit']) < 0.001);

check('不平衡拒绝', throws(fn () => $ledger->post('BAD', [
    ['account' => 'a', 'debit' => 10],
    ['account' => 'b', 'credit' => 9],
])));
check('负数拒绝', throws(fn () => $ledger->post('NEG', [
    ['account' => 'a', 'debit' => -1],
    ['account' => 'b', 'credit' => -1],
])));
check('单条零金额拒绝', throws(fn () => $ledger->post('ZERO', [
    ['account' => 'a', 'debit' => 0],
    ['account' => 'b', 'credit' => 0],
])));
check('单条同时借贷拒绝', throws(fn () => $ledger->post('BOTH', [
    ['account' => 'a', 'debit' => 1, 'credit' => 1],
    ['account' => 'b', 'credit' => 1],
])));

$ledger->post('REFUND-001', [
    ['account' => 'seller_payable', 'debit' => 20],
    ['account' => 'payment_gateway', 'credit' => 20],
]);
check('多批次分录累计', count($ledger->entries()) === 6);
check('累计后仍平衡', abs($ledger->totals()['debit'] - $ledger->totals()['credit']) < 0.001);

echo PHP_EOL . '结果：PASS ' . $pass . ' / FAIL ' . $fail . PHP_EOL;
exit($fail > 0 ? 1 : 0);

<?php
declare(strict_types=1);

/**
 * 支付回调/幂等/对账测试。用法：php platform/tests/payment-callback.php
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

echo '物格云 payment callback tests · v' . APP_VERSION . PHP_EOL . PHP_EOL;
$service = new PaymentCallbackService();
$payments = new PaymentService();
$store = new IdempotencyStore();
$secret = 'demo-secret';
$order = ['id' => 'SO-CB', 'pricing' => ['total' => 225.55]];
$payment = $payments->create($order);

$event = json_encode(['event_id' => 'evt_1', 'transaction_id' => 'TXN-1', 'status' => 'succeeded', 'amount' => 225.55], JSON_UNESCAPED_UNICODE);
$sig = $service->sign($event, $secret);
check('签名可复算', $sig === $service->sign($event, $secret));
$result = $service->handle($payment, $event, $sig, $secret, $store);
check('成功回调 succeeded', ($result['payment']['status'] ?? '') === 'succeeded');
check('transaction_id 写入', ($result['payment']['transaction_id'] ?? '') === 'TXN-1');
check('首次非重放', ($result['replayed'] ?? true) === false);

$replay = $service->handle($payment, $event, $sig, $secret, $store);
check('重放识别', ($replay['replayed'] ?? false) === true);
check('重放状态仍 succeeded', ($replay['payment']['status'] ?? '') === 'succeeded');
check('幂等存储 1 条', $store->count() === 1);

$badSig = throws(fn () => $service->handle($payment, $event, 'bad', $secret, $store));
check('坏签名拒绝', $badSig);
$badAmount = json_encode(['event_id' => 'evt_2', 'transaction_id' => 'TXN-2', 'status' => 'succeeded', 'amount' => 999]);
$badAmountSig = $service->sign($badAmount, $secret);
check('金额不一致拒绝', throws(fn () => $service->handle($payment, $badAmount, $badAmountSig, $secret, $store)));

$payment2 = $payments->create($order);
$failEvent = json_encode(['event_id' => 'evt_3', 'transaction_id' => 'TXN-3', 'status' => 'failed', 'amount' => 225.55]);
$failSig = $service->sign($failEvent, $secret);
$failed = $service->handle($payment2, $failEvent, $failSig, $secret, $store);
check('失败回调 failed', ($failed['payment']['status'] ?? '') === 'failed');

$badStatus = json_encode(['event_id' => 'evt_4', 'transaction_id' => 'TXN-4', 'status' => 'unknown', 'amount' => 225.55]);
$badStatusSig = $service->sign($badStatus, $secret);
check('未知状态拒绝', throws(fn () => $service->handle($payment2, $badStatus, $badStatusSig, $secret, $store)));
$missing = json_encode(['transaction_id' => 'TXN-5', 'status' => 'succeeded', 'amount' => 225.55]);
$missingSig = $service->sign($missing, $secret);
check('缺 event_id 拒绝', throws(fn () => $service->handle($payment, $missing, $missingSig, $secret, $store)));

$ledger = new LedgerService();
$ledger->post('MATCH', [
    ['account' => 'payment_gateway', 'debit' => 225.55],
    ['account' => 'seller_payable', 'credit' => 123.00],
    ['account' => 'platform_revenue', 'credit' => 37.00],
    ['account' => 'tax_payable', 'credit' => 65.55],
]);
$recon = (new ReconciliationService())->reconcile($payment, $ledger);
check('对账 matched', $recon['status'] === 'matched' && abs($recon['diff']) < 0.01);

$ledger2 = new LedgerService();
$ledger2->post('DIFF', [
    ['account' => 'payment_gateway', 'debit' => 200],
    ['account' => 'platform_revenue', 'credit' => 200],
]);
$recon2 = (new ReconciliationService())->reconcile($payment, $ledger2);
check('对账差异 diff', $recon2['status'] === 'diff' && abs($recon2['diff'] + 25.55) < 0.01);

$idem = new IdempotencyStore();
$idem->put('k', ['ok' => true]);
check('幂等 store 读写', $idem->has('k') && ($idem->get('k')['ok'] ?? false) === true);

echo PHP_EOL . '结果：PASS ' . $pass . ' / FAIL ' . $fail . PHP_EOL;
exit($fail > 0 ? 1 : 0);

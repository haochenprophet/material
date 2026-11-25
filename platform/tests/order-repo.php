<?php
declare(strict_types=1);

/**
 * PDO 订单仓储测试（SQLite 内存）。用法：php platform/tests/order-repo.php
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

echo '物格云 order repository tests · v' . APP_VERSION . PHP_EOL . PHP_EOL;
if (!extension_loaded('pdo_sqlite')) {
    echo 'SKIP: pdo_sqlite 未安装' . PHP_EOL;
    exit(1);
}

$pdo = new PDO('sqlite::memory:');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->exec((string) file_get_contents(PLATFORM_PATH . '/database/dev-schema.sql'));
$repo = new PdoOrderRepository($pdo);
$service = new OrderService();

$order = $service->create(
    [['mpn' => 'TE 170364-1', 'qty' => 10, 'unit' => 18.50]],
    ['qc_fee' => 20, 'fulfillment_fee' => 10, 'shipping' => 5, 'service_rate' => 0.03, 'tax_rate' => 0.13]
);
$repo->save($order);
$found = $repo->find($order['id']);
check('find 订单', is_array($found) && $found['id'] === $order['id']);
check('状态 pending_payment', ($found['status'] ?? '') === 'pending_payment');
check('金额 225.55', abs((float) ($found['pricing']['subtotal'] ?? 0) - 225.55) < 0.001);
check('订单行 MPN', ($found['lines'][0]['mpn'] ?? '') === 'TE 170364-1');
check('订单行数量', abs((float) ($found['lines'][0]['qty'] ?? 0) - 10) < 0.001);

$paid = $service->transition($order, 'paid');
$repo->save($paid);
$repo->save($paid);
$found2 = $repo->find($order['id']);
check('更新后 paid', ($found2['status'] ?? '') === 'paid');
check('重复保存不重复行', count($found2['lines'] ?? []) === 1);
check('all 1 条', count($repo->all()) === 1);
check('不存在 null', $repo->find('nope') === null);
check('SQL 注入不生效', $repo->find("x' OR '1'='1") === null);
check('接口实现', $repo instanceof OrderRepository);

echo PHP_EOL . '结果：PASS ' . $pass . ' / FAIL ' . $fail . PHP_EOL;
exit($fail > 0 ? 1 : 0);

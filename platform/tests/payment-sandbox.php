<?php
declare(strict_types=1);

/**
 * 沙箱支付/分账/退款测试（SQLite）。
 * 用法：php platform/tests/payment-sandbox.php
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

echo '物格云 sandbox payment tests · v' . APP_VERSION . PHP_EOL . PHP_EOL;
$pdo = new PDO('sqlite::memory:');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->exec('CREATE TABLE sandbox_payment (id INTEGER PRIMARY KEY AUTOINCREMENT,payment_no TEXT UNIQUE,order_no TEXT,channel TEXT,amount REAL,currency TEXT,status TEXT,provider TEXT,external_id TEXT,idempotency_key TEXT UNIQUE,paid_at TEXT,created_at TEXT,updated_at TEXT)');
$pdo->exec('CREATE TABLE sandbox_payment_split (id INTEGER PRIMARY KEY AUTOINCREMENT,payment_id INT,account_type TEXT,account_id INT,amount REAL,status TEXT,created_at TEXT)');
$pdo->exec('CREATE TABLE sandbox_refund (id INTEGER PRIMARY KEY AUTOINCREMENT,refund_no TEXT UNIQUE,payment_id INT,amount REAL,reason TEXT,status TEXT,refunded_at TEXT,created_at TEXT)');
$repo = new PdoSandboxPaymentRepository($pdo);
$p = $repo->create('SO-1', 100, 'key-1');
check('创建支付', ($p['status'] ?? '') === 'created' && ($p['amount'] ?? 0) === 100.0);
$p2 = $repo->create('SO-1', 100, 'key-1');
check('幂等键复用', ($p2['payment_no'] ?? '') === $p['payment_no'] && ($p2['idempotent'] ?? false) === true);
$c = $repo->capture((string) $p['payment_no']);
check('捕获成功', ($c['status'] ?? '') === 'succeeded');
$splits = $repo->splits((int) $p['id']);
check('两条分账', count($splits) === 2);
$sum = array_sum(array_column($splits, 'amount'));
check('分账合计 100', abs($sum - 100.0) < 0.01);
$r = $repo->refund((string) $p['payment_no'], 30, 'test');
check('部分退款', ($r['status'] ?? '') === 'partial_refunded');
$p3 = $repo->find((string) $p['payment_no']);
check('可退余额 70', abs((float) $p3['amount'] - $repo->refundedAmount((int) $p['id']) - 70.0) < 0.01);
$r2 = $repo->refund((string) $p['payment_no'], 70);
check('全额退款', ($r2['status'] ?? '') === 'refunded');
$recon = $repo->reconcile();
check('对账差 0', abs($recon['diff']) < 0.01);
check('超退拒绝', (static function () use ($repo, $p) { try { $repo->refund((string) $p['payment_no'], 1); return false; } catch (Throwable) { return true; } })());

echo PHP_EOL . '结果：PASS ' . $pass . ' / FAIL ' . $fail . PHP_EOL;
exit($fail > 0 ? 1 : 0);

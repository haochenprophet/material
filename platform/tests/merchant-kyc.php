<?php
declare(strict_types=1);

/**
 * 商家 KYC 测试（SQLite）。用法：php platform/tests/merchant-kyc.php
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

echo '物格云 merchant KYC tests · v' . APP_VERSION . PHP_EOL . PHP_EOL;
$pdo = new PDO('sqlite::memory:');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->exec('CREATE TABLE store (id INTEGER PRIMARY KEY, name TEXT, slug TEXT, status TEXT)');
$pdo->exec('CREATE TABLE merchant_kyc (id INTEGER PRIMARY KEY AUTOINCREMENT, store_id INT UNIQUE, legal_name TEXT, license_no TEXT, tax_no TEXT, legal_person TEXT, contact_name TEXT, contact_phone TEXT, documents TEXT, deposit_amount REAL, agreement_version TEXT, agreement_signed_at TEXT, status TEXT, review_note TEXT, reviewed_by INT, reviewed_at TEXT)');
$pdo->exec("INSERT INTO store VALUES (6,'HQB','hqb','active'),(7,'SZ','sz','active')");
$repo = new PdoMerchantKycRepository($pdo);
$id = $repo->submit(6, ['legal_name' => 'HQB Ltd', 'license_no' => 'L1', 'agreement_version' => 'v1', 'deposit_amount' => 1000, 'contact_name' => '陈工']);
check('提交创建', $id > 0);
check('默认 pending', $repo->find(6)['status'] === 'pending');
check('审核 verified', $repo->review($id, 'verified', 9, '资料齐全') === true);
check('状态 verified', $repo->find(6)['status'] === 'verified');
check('审核备注', $repo->find(6)['review_note'] === '资料齐全');
check('重复审核拒绝', $repo->review($id, 'rejected', 9) === false);
$id2 = $repo->submit(7, ['legal_name' => 'SZ Ltd', 'license_no' => 'L2', 'agreement_version' => 'v1', 'deposit_amount' => 0]);
check('第二个商店独立', $id2 > 0 && $repo->find(7)['status'] === 'pending');
check('缺证件拒绝', (static function () use ($repo) { try { $repo->submit(6, ['legal_name' => 'x', 'agreement_version' => 'v1']); return false; } catch (Throwable) { return true; } })());
check('负保证金拒绝', (static function () use ($repo) { try { $repo->submit(6, ['legal_name' => 'x', 'license_no' => 'y', 'agreement_version' => 'v1', 'deposit_amount' => -1]); return false; } catch (Throwable) { return true; } })());

echo PHP_EOL . '结果：PASS ' . $pass . ' / FAIL ' . $fail . PHP_EOL;
exit($fail > 0 ? 1 : 0);

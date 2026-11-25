<?php
declare(strict_types=1);

/**
 * 卖家提报写入测试（SQLite 内存）。用法：php platform/tests/listing-writer.php
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

echo '物格云 listing writer tests · v' . APP_VERSION . PHP_EOL . PHP_EOL;
$pdo = new PDO('sqlite::memory:');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->exec('CREATE TABLE material (id INTEGER PRIMARY KEY AUTOINCREMENT, uuid TEXT, code TEXT, part_number TEXT, name TEXT, description TEXT, category_id INT, uom_id INT, brand_id INT, manufacturer_id INT, package_id INT, lifecycle_status TEXT)');
$pdo->exec('CREATE TABLE listing (id INTEGER PRIMARY KEY AUTOINCREMENT, uuid TEXT, store_id INT, material_id INT, category_id INT, title TEXT, description TEXT, item_condition TEXT, grade TEXT, qc_status TEXT, status TEXT, price_mode TEXT, price REAL, currency TEXT, quantity REAL, moq REAL, uom_id INT, warehouse_id INT, location_id INT, lead_time_days INT, warranty_days INT)');
$pdo->exec('CREATE TABLE idempotency_key (key TEXT, scope TEXT, response TEXT, created_at TEXT, expires_at TEXT, PRIMARY KEY (key,scope))');
$pdo->exec('CREATE TABLE listing_review (id INTEGER PRIMARY KEY AUTOINCREMENT, listing_id INT, reviewer_id INT, action TEXT, reason TEXT, created_at TEXT)');
$pdo->exec('CREATE TABLE audit_log (id INTEGER PRIMARY KEY AUTOINCREMENT, actor_id INT, actor_type TEXT, action TEXT, object_type TEXT, object_id TEXT, after_json TEXT, request_id TEXT)');
$writer = new PdoListingWriter($pdo);
$id = $writer->create(['part_number' => 'TE-NEW-1', 'title' => 'TE new connector', 'qty' => 100, 'price' => 12.50, 'spec' => '4Pin']);
check('返回 listing id', $id > 0);
$row = $pdo->query('SELECT * FROM listing WHERE id=' . $id)->fetch(PDO::FETCH_ASSOC);
check('listing pending_review', ($row['status'] ?? '') === 'pending_review');
check('listing pending qc', ($row['qc_status'] ?? '') === 'pending');
check('数量/价格', (float) $row['quantity'] === 100.0 && (float) $row['price'] === 12.5);
check('标题', ($row['title'] ?? '') === 'TE new connector');
$material = $pdo->query('SELECT * FROM material WHERE id=' . (int) $row['material_id'])->fetch(PDO::FETCH_ASSOC);
check('material part_number', ($material['part_number'] ?? '') === 'TE-NEW-1');
check('submit 审计写入', (int) $pdo->query("SELECT COUNT(*) FROM listing_review WHERE listing_id=$id AND action='submit'")->fetchColumn() === 1);
check('audit listing.submit', (int) $pdo->query("SELECT COUNT(*) FROM audit_log WHERE object_id='$id' AND action='listing.submit'")->fetchColumn() === 1);
check('缺型号拒绝', throws(fn () => $writer->create(['title' => 'x', 'qty' => 1, 'price' => 1])));
check('数量 0 拒绝', throws(fn () => $writer->create(['part_number' => 'x', 'title' => 'x', 'qty' => 0, 'price' => 1])));

$data = ['part_number' => 'IDEM-1', 'title' => 'Idem listing', 'qty' => 1, 'price' => 1.0];
$id1 = $writer->create($data, 'idem-key-1');
$id2 = $writer->create($data, 'idem-key-1');
check('幂等键返回同一 id', $id1 === $id2);
check('幂等只写一条 listing', (int) $pdo->query("SELECT COUNT(*) FROM listing WHERE title='Idem listing'")->fetchColumn() === 1);
check('幂等不重复审计', (int) $pdo->query("SELECT COUNT(*) FROM audit_log WHERE action='listing.submit' AND object_id='$id1'")->fetchColumn() === 1);
$id3 = $writer->create($data, 'idem-key-2');
check('不同幂等键新 id', $id3 !== $id1);

echo PHP_EOL . '结果：PASS ' . $pass . ' / FAIL ' . $fail . PHP_EOL;
exit($fail > 0 ? 1 : 0);

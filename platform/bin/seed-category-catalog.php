<?php
declare(strict_types=1);

/**
 * 从 category-samples.json 生成品类页演示报价（可重复运行）。
 * 数据来源为 hardware.sql 样本；价格为演示参考，不是真实报价。
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    echo 'CLI only';
    exit(1);
}
require dirname(__DIR__) . '/app/bootstrap.php';

$config = require PLATFORM_PATH . '/config/category-pages.php';
$fixture = json_decode((string) file_get_contents(PLATFORM_PATH . '/database/fixtures/category-samples.json'), true);
if (!is_array($fixture['categories'] ?? null)) {
    fwrite(STDERR, "fixture 无效\n");
    exit(1);
}
if (!Database::configured()) {
    fwrite(STDERR, "DB 未配置\n");
    exit(2);
}
$pdo = Database::pdo();
$stores = $pdo->query("SELECT id,name FROM store WHERE status='active' ORDER BY id")->fetchAll(PDO::FETCH_ASSOC) ?: [];
if ($stores === []) {
    fwrite(STDERR, "没有 active 店铺\n");
    exit(2);
}
$categoryIdMap = ['resistor' => 12, 'capacitor' => 12, 'inductor' => 12, 'connector' => 11, 'discrete' => 12, 'ic' => 13, 'cpu' => 13, 'chipset' => 13, 'memory' => 13, 'programmable' => 13, 'communication' => 14, 'system' => 14, 'mechanical' => 12];
$priceBase = ['resistor' => [0.02, 2], 'capacitor' => [0.05, 8], 'inductor' => [0.10, 12], 'connector' => [0.50, 50], 'discrete' => [0.10, 15], 'ic' => [2, 200], 'cpu' => [80, 2000], 'chipset' => [10, 300], 'memory' => [8, 500], 'programmable' => [3, 80], 'communication' => [20, 300], 'system' => [5, 100], 'mechanical' => [0.05, 5]];
$insertedM = 0;
$insertedL = 0;
$skipped = 0;
$pdo->beginTransaction();
try {
    foreach ($fixture['categories'] as $key => $cat) {
        $meta = $config[$key] ?? null;
        if ($meta === null) { continue; }
        foreach ($cat['samples'] as $i => $s) {
            $code = 'CAT-' . strtoupper($key) . '-' . str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT);
            $stmt = $pdo->prepare('SELECT id FROM material WHERE code=? LIMIT 1');
            $stmt->execute([$code]);
            $mid = $stmt->fetchColumn();
            if ($mid === false) {
                $uuid = substr(hash('md5', $code), 0, 8) . '-' . substr(hash('md5', $code), 8, 4) . '-' . substr(hash('md5', $code), 12, 4) . '-' . substr(hash('md5', $code), 16, 4) . '-' . substr(hash('md5', $code), 20, 12);
                $name = mb_substr($s['component_name'], 0, 200);
                $attrs = json_encode([
                    'legacy_type' => $meta['legacy_type'],
                    'package' => $s['package'],
                    'value' => $s['value'],
                    'parameter_hint' => $s['component_name'],
                    'subcategory' => $s['subcategory_name'],
                    'source' => 'hardware.sql / Greatwall CIS',
                    'sample' => true,
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                $ins = $pdo->prepare("INSERT INTO material (uuid,code,part_number,name,description,category_id,category_key,subcategory_key,brand_id,manufacturer_id,package_id,lifecycle_status,rohs,attributes_summary) VALUES (?,?,?,?,?,?,?,?,1,1,1,'active','yes',?)");
                $ins->execute([$uuid, $code, $s['part_number'], $name, $s['description'], $categoryIdMap[$key] ?? 12, $key, $s['subcategory_key'], $attrs]);
                $mid = (int) $pdo->lastInsertId();
                $insertedM++;
            }
            $count = $pdo->prepare('SELECT COUNT(*) FROM listing WHERE material_id=?');
            $count->execute([$mid]);
            if ((int) $count->fetchColumn() > 0) {
                $skipped++;
                continue;
            }
            [$lo, $hi] = $priceBase[$key] ?? [1, 10];
            $h = hexdec(substr(hash('sha256', $code), 0, 6)) / 0xffffff;
            $price = round($lo + ($hi - $lo) * $h, 2);
            $store = $stores[$i % count($stores)];
            $qty = 50 + (int) (hexdec(substr(hash('sha256', $code), 6, 3)) % 4950);
            $lead = 1 + (int) (hexdec(substr(hash('sha256', $code), 9, 2)) % 14);
            $title = mb_substr($name . ' · ' . $s['subcategory_name'] . ' · ' . $s['package'], 0, 200);
            $listing = $pdo->prepare("INSERT INTO listing (uuid,store_id,material_id,category_id,title,description,item_condition,grade,qc_status,status,price_mode,price,currency,quantity,moq,lead_time_days,warranty_days,certifications,published_at,created_at) VALUES (?,?,?,?,?,?, 'surplus','A','passed','listed','fixed',?,'CNY',?,?,?,?,?,CURRENT_TIMESTAMP,CURRENT_TIMESTAMP)");
            $listing->execute([
                substr(hash('md5', $code . '-l'), 0, 8) . '-' . substr(hash('md5', $code . '-l'), 8, 4) . '-' . substr(hash('md5', $code . '-l'), 12, 4) . '-' . substr(hash('md5', $code . '-l'), 16, 4) . '-' . substr(hash('md5', $code . '-l'), 20, 12),
                (int) $store['id'], $mid, $categoryIdMap[$key] ?? 12, $title,
                '品类样本报价 · ' . $s['description'],
                $price, $qty, $i % 2 === 0 ? 1 : 10, $lead, 30, json_encode(['source' => 'legacy-cis'], JSON_UNESCAPED_UNICODE),
            ]);
            $insertedL++;
        }
    }
    $pdo->commit();
} catch (Throwable $e) {
    if ($pdo->inTransaction()) { $pdo->rollBack(); }
    fwrite(STDERR, 'FAIL: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}
echo 'materials inserted: ' . $insertedM . PHP_EOL;
echo 'listings inserted:  ' . $insertedL . PHP_EOL;
echo 'listings skipped:   ' . $skipped . PHP_EOL;
echo 'stores: ' . implode(', ', array_column($stores, 'name')) . PHP_EOL;

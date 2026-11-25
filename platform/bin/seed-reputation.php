<?php
declare(strict_types=1);

/**
 * 点评/信用/商家测试数据（幂等）。只写 mats_platform_*，不碰旧库。
 * 用法：DB_USER=... DB_PASS=... DB_NAME=mats_platform_next2 php platform/bin/seed-reputation.php
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    echo 'CLI only';
    exit(1);
}

require dirname(__DIR__) . '/app/bootstrap.php';

$name = getenv('DB_NAME') ?: 'mats_platform_next2';
if (!preg_match('/^mats_platform_[a-z0-9_]{2,48}$/', $name)) {
    fwrite(STDERR, "拒绝数据库名: {$name}\n");
    exit(1);
}
$user = (string) getenv('DB_USER');
$pass = (string) getenv('DB_PASS');
if ($user === '') {
    fwrite(STDERR, "DB_USER 未设置\n");
    exit(2);
}
$host = getenv('DB_HOST') ?: '127.0.0.1';
$port = (int) (getenv('DB_PORT') ?: 3306);
try {
    $pdo = new PDO("mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    $pdo->beginTransaction();

    $org = static function (PDO $pdo, string $orgName): int {
        $pdo->prepare("INSERT INTO org (uuid,name,type,status) SELECT UUID(),?,'company','active' WHERE NOT EXISTS (SELECT 1 FROM org WHERE name=?)")
            ->execute([$orgName, $orgName]);
        $s = $pdo->prepare('SELECT id FROM org WHERE name=? LIMIT 1');
        $s->execute([$orgName]);
        return (int) $s->fetchColumn();
    };
    $store = static function (PDO $pdo, int $orgId, string $storeName, string $slug, float $rating): int {
        $pdo->prepare("INSERT INTO store (uuid,org_id,name,slug,description,status,rating,rating_count) SELECT UUID(),?,?,?,'', 'active',?,12 WHERE NOT EXISTS (SELECT 1 FROM store WHERE slug=?)")
            ->execute([$orgId, $storeName, $slug, $rating, $slug]);
        $s = $pdo->prepare('SELECT id FROM store WHERE slug=? LIMIT 1');
        $s->execute([$slug]);
        return (int) $s->fetchColumn();
    };

    $hqbOrg = $org($pdo, '华强北工控优选');
    $szOrg = $org($pdo, '苏州余料科技');
    $hqb = $store($pdo, $hqbOrg, '华强北工控优选', 'hqb-industrial', 4.8);
    $sz = $store($pdo, $szOrg, '苏州余料科技', 'suzhou-surplus', 4.6);

    $pdo->prepare("INSERT INTO merchant_profile (store_id,merchant_code,contact_name,phone,email,address,banner,description,status) SELECT ?,?,?,?,?,?,?,?,'active' WHERE NOT EXISTS (SELECT 1 FROM merchant_profile WHERE store_id=?)")
        ->execute([$hqb, 'HQB001', '陈工', '13800000001', 'hqb@demo.local', '深圳市福田区华强北', '/assets/img/merchants/hqb.svg', '连接器/主动件/工控物料，支持批次追溯与质检报告。', $hqb]);
    $pdo->prepare("INSERT INTO merchant_profile (store_id,merchant_code,contact_name,phone,email,address,banner,description,status) SELECT ?,?,?,?,?,?,?,?,'active' WHERE NOT EXISTS (SELECT 1 FROM merchant_profile WHERE store_id=?)")
        ->execute([$sz, 'SZ002', '李工', '13800000002', 'sz@demo.local', '苏州市工业园区', '/assets/img/merchants/suzhou.svg', '工程余料/被动件/小批量配单，支持寄售结算。', $sz]);

    $pdo->prepare("INSERT INTO merchant_kyc (store_id,legal_name,license_no,tax_no,legal_person,contact_name,contact_phone,documents,deposit_amount,agreement_version,agreement_signed_at,status,review_note)
        SELECT ?,?,?,?,?,?,?,?,?,?,?,?,?
        WHERE NOT EXISTS (SELECT 1 FROM merchant_kyc WHERE store_id=?)")
        ->execute([$hqb, '深圳市华强北工控优选有限公司', '91440300HQB20260001', '91440300HQB20260001', '陈工', '陈工', '13800000001', json_encode(['business_license' => '/assets/img/merchants/hqb.svg', 'quality_agreement' => true], JSON_UNESCAPED_UNICODE), 10000.00, 'v1', '2026-11-24 10:00:00', 'verified', '资料齐全，已认证', $hqb]);
    $pdo->prepare("INSERT INTO merchant_kyc (store_id,legal_name,license_no,tax_no,legal_person,contact_name,contact_phone,documents,deposit_amount,agreement_version,agreement_signed_at,status,review_note)
        SELECT ?,?,?,?,?,?,?,?,?,?,?,?,? 
        WHERE NOT EXISTS (SELECT 1 FROM merchant_kyc WHERE store_id=?)")
        ->execute([$sz, '苏州余料科技有限公司', '91320500SZ20260002', '91320500SZ20260002', '李工', '李工', '13800000002', json_encode(['business_license' => '/assets/img/merchants/suzhou.svg', 'quality_agreement' => true], JSON_UNESCAPED_UNICODE), 5000.00, 'v1', '2026-11-24 10:00:00', 'pending', null, $sz]);

    $assign = [1 => $hqb, 2 => $hqb, 3 => $sz, 4 => $hqb, 5 => $sz, 6 => $sz, 7 => $sz];
    $upd = $pdo->prepare('UPDATE listing SET store_id=? WHERE id=?');
    foreach ($assign as $listingId => $storeId) {
        $upd->execute([$storeId, $listingId]);
    }
    // 让苏州商城有可展示的测试商品；只改测试 listing，不删除。
    $pdo->exec("UPDATE listing SET status='listed', qc_status='passed' WHERE id IN (5,6) AND status='pending_review'");

    $reviewCount = (int) $pdo->query('SELECT COUNT(*) FROM material_review')->fetchColumn();
    if ($reviewCount === 0) {
        $rows = [
            [1, $hqb, 5, 5, 5, 5, 5, 'TE 原厂散新，批次一致，包装完整，质检报告随货。', '张工', 'buyer', 1],
            [1, $hqb, 4, 5, 4, 5, 5, '价格比现货市场低一档，交期 48 小时，适合小批量试产。', '李采购', 'buyer', 1],
            [1, $hqb, 5, 5, 5, 4, 5, '二次复购，丝印和电性能都稳定。', '王工', 'buyer', 1],
            [1, $hqb, 4, 4, 4, 4, 4, '整体不错，发票流程再快一点更好。', '赵经理', 'buyer', 0],
            [2, $hqb, 5, 5, 4, 5, 5, '工程余料，数量准确，外观有轻微氧化但不影响焊接。', '周工', 'buyer', 1],
            [2, $hqb, 4, 4, 4, 4, 4, '小批量采购方便，客服响应快。', '孙工', 'buyer', 0],
            [3, $sz, 2, 2, 3, 2, 2, '驳回后重新补资料，流程透明，但等待时间偏长。', '李工', 'seller', 0],
            [3, $sz, 3, 3, 3, 3, 3, '资料要求严格，能理解，后续会补齐。', '陈工', 'seller', 0],
            [4, $hqb, 5, 5, 5, 5, 5, '批次可追溯，扫码能看到来源，适合工程备料。', '吴工', 'buyer', 1],
            [4, $hqb, 4, 4, 4, 4, 4, '交期稳定，包装规范。', '郑工', 'buyer', 0],
            [5, $sz, 4, 4, 4, 4, 4, '散新件，性价比高，适合打样。', '冯工', 'buyer', 0],
            [5, $sz, 4, 4, 4, 4, 4, '客服专业，能提供替代型号建议。', '陈经理', 'buyer', 1],
            [6, $sz, 5, 5, 4, 5, 5, '余料批次清楚，数量足。', '褚工', 'buyer', 1],
            [6, $sz, 4, 4, 4, 4, 4, '发货快，包装一般。', '卫工', 'buyer', 0],
            [7, $sz, 4, 4, 4, 4, 4, '试单体验不错，会继续采购。', '蒋工', 'buyer', 0],
        ];
        $ins = $pdo->prepare("INSERT INTO material_review (uuid,material_id,listing_id,store_id,user_id,author_name,author_role,rating,quality,price,delivery,service,content,verified_purchase,status,created_at) VALUES (UUID(),?,?,?,NULL,?,?,?,?,?,?,?,?,?,'visible',?)");
        foreach ($rows as $i => $r) {
            $ins->execute([$r[0], $r[0], $r[1], $r[8], $r[9], $r[2], $r[3], $r[4], $r[5], $r[6], $r[7], $r[10], date('Y-m-d H:i:s', strtotime('2026-11-25 08:00') + $i * 600)]);
        }
        $first = (int) $pdo->query('SELECT id FROM material_review ORDER BY id ASC LIMIT 1')->fetchColumn();
        $pdo->prepare("INSERT INTO review_reply (review_id,store_id,author_name,content) VALUES (?,?,?,?)")
            ->execute([$first, $hqb, '华强北客服', '感谢复购，质检报告和批次照片已随货发出。']);
    }

    $imgMap = [1 => 'm1.svg', 2 => 'm2.svg', 3 => 'm3.svg', 4 => 'm4.svg', 5 => 'm5.svg', 6 => 'm6.svg', 7 => 'm7.svg'];
    $att = $pdo->prepare("INSERT INTO material_attachment (material_id,type,path,mime,size,sha256,is_primary,sort)
        SELECT ?, 'image', ?, 'image/svg+xml', ?, ?, 1, 0
        WHERE NOT EXISTS (SELECT 1 FROM material_attachment WHERE material_id=? AND path=?)");
    foreach ($imgMap as $mid => $file) {
        $rel = '/assets/img/materials/' . $file;
        $abs = PUBLIC_PATH . $rel;
        $att->execute([$mid, $rel, is_file($abs) ? filesize($abs) : null, is_file($abs) ? hash_file('sha256', $abs) : null, $mid, $rel]);
    }

    $prov = [
        1 => ['TRC-TE-170364', 'manufacturer', 'TE Connectivity', $hqbOrg, 'TE-2026-11-A', 'CN', '2026-10-18', 30],
        2 => ['TRC-UI-TEST-1', 'surplus', '华强北工控优选', $hqbOrg, 'HQB-2026-11-02', 'CN', '2026-11-02', 30],
        3 => ['TRC-REJECT-TEST', 'surplus', '苏州余料科技', $szOrg, 'SZ-2026-10-28', 'CN', '2026-10-28', 15],
        4 => ['TRC-REVIEWER-TEST', 'authorized', '华强北工控优选', $hqbOrg, 'HQB-2026-11-05', 'CN', '2026-11-05', 45],
        5 => ['TRC-IDEM-UI-2', 'surplus', '苏州余料科技', $szOrg, 'SZ-2026-11-06', 'CN', '2026-11-06', 30],
        6 => ['TRC-IDEM-6', 'surplus', '苏州余料科技', $szOrg, 'SZ-2026-11-07', 'CN', '2026-11-07', 30],
        7 => ['TRC-AUDIT-SUBMIT', 'broker', '苏州余料科技', $szOrg, 'SZ-2026-11-08', 'CN', '2026-11-08', 30],
    ];
    $provStmt = $pdo->prepare("INSERT INTO material_provenance (material_id,trace_code,source_type,source_name,supplier_org_id,supplier_name,batch_no,country,purchase_date,warranty_days,certifications,metadata)
        SELECT ?,?,?,?,?,?,?,?,?,?,?,?
        WHERE NOT EXISTS (SELECT 1 FROM material_provenance WHERE material_id=?)");
    foreach ($prov as $mid => $r) {
        $provStmt->execute([$mid, $r[0], $r[1], $r[2], $r[3], $r[2], $r[4], $r[5], $r[6], $r[7], json_encode(['RoHS' => 'yes', 'REACH' => 'yes'], JSON_UNESCAPED_UNICODE), json_encode(['seeded' => true], JSON_UNESCAPED_UNICODE), $mid]);
    }

    $commentCount = (int) $pdo->query('SELECT COUNT(*) FROM material_comment')->fetchColumn();
    if ($commentCount === 0) {
        $addComment = static function (PDO $pdo, int $mid, ?int $parentId, string $name, string $role, string $content, string $created) {
            $path = '';
            $depth = 0;
            if ($parentId !== null) {
                $q = $pdo->prepare('SELECT depth,path FROM material_comment WHERE id=?');
                $q->execute([$parentId]);
                $p = $q->fetch();
                $depth = (int) $p['depth'] + 1;
                $path = (string) $p['path'] . '/';
            }
            $ins = $pdo->prepare("INSERT INTO material_comment (uuid,material_id,parent_id,user_id,author_name,author_role,content,status,depth,path,created_at) VALUES (UUID(),?,?,NULL,?,?,?,'visible',?,?,?)");
            $ins->execute([$mid, $parentId, $name, $role, $content, $depth, '', $created]);
            $id = (int) $pdo->lastInsertId();
            $pdo->prepare('UPDATE material_comment SET path=? WHERE id=?')->execute([$path . $id, $id]);
            return $id;
        };
        $root1 = $addComment($pdo, 1, null, '张工', 'buyer', '包装完整，批次一致，质检报告随货。', '2026-11-25 08:10:00');
        $child1 = $addComment($pdo, 1, $root1, '李采购', 'buyer', '同批次还有 10k 吗？想一起下单。', '2026-11-25 08:20:00');
        $addComment($pdo, 1, $child1, '华强北客服', 'seller', '有，同批次可预留，今天 16:00 前下单可当天发。', '2026-11-25 08:30:00');
        $addComment($pdo, 2, null, '周工', 'buyer', '工程余料，数量准确，焊接正常。', '2026-11-25 08:15:00');
        $addComment($pdo, 3, null, '陈工', 'seller', '资料已补齐，等待复审。', '2026-11-25 08:25:00');
        $addComment($pdo, 4, null, '吴工', 'buyer', '批次可追溯，扫码能看到来源。', '2026-11-25 08:35:00');
        $addComment($pdo, 5, null, '冯工', 'buyer', '适合打样，价格合理。', '2026-11-25 08:40:00');
        $addComment($pdo, 6, null, '褚工', 'buyer', '余料批次清楚，数量足。', '2026-11-25 08:45:00');
        $addComment($pdo, 7, null, '蒋工', 'buyer', '试单体验不错。', '2026-11-25 08:50:00');
    }

    $pdo->exec("INSERT INTO material_review (uuid,material_id,listing_id,store_id,user_id,author_name,author_role,rating,quality,price,delivery,service,content,verified_purchase,status,created_at)
        SELECT UUID(),3,3,7,NULL,'待审用户','buyer',4,4,4,4,4,'待审核测试点评：批次信息待补充。',0,'pending','2026-11-25 09:00:00'
        WHERE NOT EXISTS (SELECT 1 FROM material_review WHERE status='pending')");

    $pdo->commit();
    $credit = new PdoCreditRepository($pdo);
    $result = $credit->recalculateAll();
    echo 'db: ' . $name . PHP_EOL;
    echo 'stores: ' . $hqb . ',' . $sz . PHP_EOL;
    echo 'reviews: ' . (int) $pdo->query('SELECT COUNT(*) FROM material_review')->fetchColumn() . PHP_EOL;
    echo 'merchant credits: ' . count($result) . PHP_EOL;
    exit(0);
} catch (Throwable $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    fwrite(STDERR, 'FAIL: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}

<?php
declare(strict_types=1);
$rows = [];
$dbReady = Database::configured();
$storeId = MerchantContext::storeId();
if ($dbReady) {
    if ($storeId !== null) {
        $rows = (new PdoSellerListingRepository(Database::pdo()))->all($storeId);
    }
}
$ok = Cui::flash('seller_result');
$err = Cui::flash('seller_error');

echo CuiBreadcrumb::render([['label' => '卖家工作台', 'href' => '/seller'], ['label' => '我的提报']]);
echo CuiPageHeader::render([
    'title' => '我的提报',
    'subtitle' => '待审核/已上架/已驳回全状态；上架后才进入公开目录。',
    'actions' => [CuiButton::render(['label' => '提报物料', 'variant' => 'primary', 'href' => '/seller/listings/new'])],
]);
if ($ok !== null) {
    echo '<div class="alert alert-ok">' . CuiHtml::e($ok) . '</div>';
}
if ($err !== null) {
    echo '<div class="alert alert-error">' . CuiHtml::e($err) . '</div>';
}
if (!$dbReady) {
    echo CuiCard::render('<div class="notice">' . Cui::icon('alert') . ' 演示模式未配置数据库；提报列表需要 DB_USER。</div>');
} elseif ($rows === []) {
    echo CuiEmpty::render('还没有提报', '提交后会出现在这里。', CuiButton::render(['label' => '去提报', 'href' => '/seller/listings/new']));
} else {
    $table = [];
    foreach ($rows as $r) {
        $status = (string) $r['status'];
        $kind = PdoSellerListingRepository::kind($status);
        $table[] = [
            '#' . (int) $r['id'],
            CuiHtml::e((string) $r['part_number']),
            CuiHtml::e((string) $r['title']),
            '¥' . number_format((float) $r['price'], 2),
            number_format((float) $r['quantity'], 0),
            '<span class="badge ' . ($kind !== '' ? 'badge-' . $kind : '') . '">' . CuiHtml::e(PdoSellerListingRepository::label($status)) . '</span>',
            CuiHtml::e((string) ($r['review_reason'] ?? '')),
            CuiHtml::e((string) ($r['created_at'] ?? '')),
        ];
    }
    echo CuiCard::render(CuiCard::header('共 ' . count($rows) . ' 条', '最新在前') . CuiTable::render(['ID', 'MPN', '标题', '价格', '数量', '状态', '备注', '创建时间'], $table));
}

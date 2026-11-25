<?php
declare(strict_types=1);
/** @var string $slug */
$store = null;
$listings = [];
$reviews = [];
$historySpark = '';
if (Database::configured()) {
    $repo = new PdoStoreRepository(Database::pdo());
    $store = $repo->findBySlug((string) ($slug ?? ''));
    if ($store !== null && (string) $store['status'] === 'active') {
        $listings = $repo->listings((int) $store['id']);
        $reviews = (new PdoReviewRepository(Database::pdo()))->forStore((int) $store['id'], 20);
        $history = (new PdoCreditRepository(Database::pdo()))->history('merchant', (int) $store['id'], 10);
        $historySpark = (new CreditHistory())->sparkline((new CreditHistory())->points($history), 320, 64);
    } else {
        $store = null;
    }
}
if ($store === null) {
    echo CuiBreadcrumb::render([['label' => '物料商城', 'href' => '/shops'], ['label' => '商家']]);
    echo CuiEmpty::render('商城不存在或已暂停', '换一个商家看看。', CuiButton::render(['label' => '返回商城', 'href' => '/shops']));
    return;
}
$grade = (string) ($store['grade'] ?? 'NA');
$score = (int) ($store['score'] ?? 0);
$rating = (float) ($store['rating'] ?? 0);
echo '<div class="shop-hero">';
echo '<div><div class="eyebrow-dark">商家商城 · ' . CuiHtml::e((string) ($store['merchant_code'] ?? '未认证')) . '</div><h1>' . CuiHtml::e((string) $store['name']) . '</h1><p class="muted">' . CuiHtml::e((string) ($store['profile_description'] ?? $store['description'] ?? '')) . '</p><div class="shop-stats"><span>★ ' . number_format($rating, 1) . '</span><span>' . (int) ($store['review_count'] ?? 0) . ' 条点评</span><span>' . count($listings) . ' 件在售</span></div></div>';
echo '<div class="credit-box"><div class="credit-grade credit-' . CuiHtml::e(strtolower($grade)) . '">' . CuiHtml::e($grade) . '</div><div><b>信用分 ' . $score . ' / 100</b><p class="small muted">由全部商品点评聚合，类似芝麻信用</p></div></div>';
echo '</div>';

if ($historySpark !== '') {
    echo CuiCard::render(CuiCard::header('商家信用趋势', '最近 10 次重算') . $historySpark);
}

echo CuiCard::render(CuiCard::header('在售物料', '共 ' . count($listings) . ' 件'));
if ($listings === []) {
    echo '<div class="notice">该商家暂无已上架物料。</div>';
} else {
    echo '<div class="product-grid">';
    foreach ($listings as $l) {
        $img = (string) ($l['image_path'] ?? '');
        echo CuiProductCard::render([
            'id' => 'p' . (int) $l['id'],
            'title' => (string) $l['title'],
            'spec' => (string) $l['part_number'] . ' · ' . (string) $l['grade'] . ' 级',
            'price' => '¥' . number_format((float) $l['price'], 2),
            'unit' => '个',
            'stock' => '库存 ' . number_format((float) $l['quantity'], 0),
            'location' => (string) $store['name'],
            'badge' => (string) ($l['material_grade'] ?? '待评'),
            'badgeKind' => ((string) ($l['material_grade'] ?? '') === 'AA' || (string) ($l['material_grade'] ?? '') === 'AAA') ? 'ok' : '',
            'thumb' => (string) $l['part_number'],
            'image' => $img,
        ]);
    }
    echo '</div>';
}
echo '</div>';

$reviewHtml = '<div class="review-list">';
if ($reviews === []) {
    $reviewHtml .= '<div class="notice">暂无点评。</div>';
}
foreach ($reviews as $r) {
    $stars = str_repeat('★', (int) $r['rating']) . str_repeat('☆', 5 - (int) $r['rating']);
    $reviewHtml .= '<div class="review-item"><div class="review-top"><b>' . CuiHtml::e((string) $r['author_name']) . '</b><span class="stars">' . $stars . '</span><span class="muted small">' . CuiHtml::e((string) ($r['material_name'] ?? '')) . ' · ' . CuiHtml::e(substr((string) $r['created_at'], 0, 10)) . '</span></div><p>' . CuiHtml::e((string) $r['content']) . '</p></div>';
}
$reviewHtml .= '</div>';
echo CuiCard::render(CuiCard::header('全部商品点评', count($reviews) . ' 条') . $reviewHtml);
echo '<p class="small muted">信用分只统计 visible 点评；后台可隐藏违规点评并自动重算。</p>';

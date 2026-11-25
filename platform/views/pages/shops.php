<?php
declare(strict_types=1);
$stores = [];
if (Database::configured()) {
    $stores = (new PdoStoreRepository(Database::pdo()))->all(true);
}
echo CuiBreadcrumb::render([['label' => '首页', 'href' => '/'], ['label' => '物料商城']]);
echo CuiPageHeader::render([
    'title' => '物料商城',
    'subtitle' => '按商家信用等级选料；每个商家一个独立商城，点评可追溯。',
    'actions' => [CuiButton::render(['label' => '寻料', 'href' => '/search'])],
]);
if ($stores === []) {
    echo CuiEmpty::render('暂无商家', '配置数据库后会显示商家商城。');
} else {
    echo '<div class="shop-grid">';
    foreach ($stores as $s) {
        $grade = (string) ($s['grade'] ?? 'NA');
        $score = (int) ($s['score'] ?? 0);
        $rating = (float) ($s['rating'] ?? 0);
        $logo = (string) ($s['logo'] ?? '');
        echo CuiCard::render(
            '<div class="shop-card-head"><div class="shop-logo">' . ($logo !== '' ? '<img src="' . CuiHtml::e($logo) . '" alt="">' : '店') . '</div>'
            . '<div><div class="shop-name">' . CuiHtml::e((string) $s['name']) . '</div><div class="muted small">' . CuiHtml::e((string) ($s['merchant_code'] ?? '未认证')) . ' · ' . CuiHtml::e((string) ($s['address'] ?? '')) . '</div></div>'
            . '<div class="credit-pill credit-' . CuiHtml::e(strtolower($grade)) . '">' . CuiHtml::e($grade) . '<small>' . $score . '</small></div></div>'
            . '<div class="shop-stats"><span>★ ' . number_format($rating, 1) . '</span><span>' . (int) ($s['review_count'] ?? 0) . ' 条点评</span><span>' . (int) ($s['live_listings'] ?? 0) . ' 件在售</span></div>'
            . '<p class="muted">' . CuiHtml::e((string) ($s['description'] ?? '')) . '</p>'
            . CuiButton::render(['label' => '进入商城', 'variant' => 'primary', 'href' => '/shop/' . rawurlencode((string) $s['slug'])])
        );
    }
    echo '</div>';
}

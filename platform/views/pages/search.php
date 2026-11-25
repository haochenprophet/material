<?php
declare(strict_types=1);
/** @var string $q */
$q = trim((string) ($q ?? ''));
$products = CatalogRepositoryFactory::make()->all();
if ($q !== '') {
    $products = array_values(array_filter($products, static function (array $p) use ($q): bool {
        return mb_stripos($p['title'] . ' ' . $p['spec'], $q) !== false;
    }));
}

echo CuiBreadcrumb::render([
    ['label' => '首页', 'href' => '/'],
    ['label' => '寻料 / 搜索'],
]);

echo CuiPageHeader::render([
    'title' => $q !== '' ? '搜索：' . $q : '全部物料',
    'subtitle' => '共 ' . count($products) . ' 条结果；按价格/库存/质检/交期筛选。',
    'actions' => [
        CuiButton::render(['label' => '筛选', 'icon' => 'filter', 'data' => ['action' => 'toast', 'toast' => '筛选用底部抽屉；演示版仅展示交互入口']]),
        CuiButton::render(['label' => '上传 BOM', 'variant' => 'primary', 'icon' => 'upload', 'href' => '/bom']),
    ],
]);

echo '<form class="filter-bar" action="' . Cui::url('/search') . '" method="get">';
echo '<input class="input" type="search" name="q" value="' . CuiHtml::e($q) . '" placeholder="型号 / 品牌 / 封装 / 参数" aria-label="搜索">';
echo CuiButton::render(['label' => '搜索', 'variant' => 'primary', 'type' => 'submit']);
echo '</form>';

echo CuiChips::render([
    ['label' => '全部', 'active' => true],
    ['label' => '有库存', 'href' => '/search?q=' . rawurlencode($q)],
    ['label' => 'A 级质检', 'href' => '/search?q=' . rawurlencode($q)],
    ['label' => '48h 发货', 'href' => '/search?q=' . rawurlencode($q)],
    ['label' => '价格从低到高', 'href' => '/search?q=' . rawurlencode($q)],
]);

if ($products === []) {
    echo CuiEmpty::render('没有找到匹配物料', '换个型号/品牌，或上传 BOM 让我们帮你找。', CuiButton::render(['label' => '上传 BOM', 'variant' => 'primary', 'href' => '/bom']));
} else {
    echo '<div class="product-grid">';
    foreach ($products as $p) {
        echo CuiProductCard::render($p);
    }
    echo '</div>';
}

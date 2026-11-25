<?php
declare(strict_types=1);
/** @var array<int, array<string, mixed>> $products */
$products = CatalogRepositoryFactory::make()->all();

$categories = MaterialCategory::all();
$categoryTotal = array_sum(array_map(static fn (array $c): int => (int) $c['count'], $categories));

echo '<section class="cat-hub" id="categories">';
echo '<div class="cat-hub-head"><div><span class="cat-kicker">品类寻料 · CATEGORY HUB</span><h2>按品类快速找料</h2><p>13 个物料大类，覆盖 25,270 条旧库样本；按商家、价格、交期、参数快速缩小范围。</p></div><a class="link" href="' . Cui::url('/search') . '">全站搜索 →</a></div>';
echo '<div class="cat-hub-grid">';
foreach ($categories as $key => $c) {
    echo '<a class="cat-hub-card" href="' . Cui::url('/category/' . rawurlencode((string) $key)) . '" style="--cat:' . CuiHtml::e($c['color']) . ';--cat-soft:' . CuiHtml::e($c['soft']) . '">';
    echo '<span class="cat-hub-icon">' . Cui::icon((string) $c['icon']) . '</span>';
    echo '<span class="cat-hub-name">' . CuiHtml::e((string) $c['name']) . '</span>';
    echo '<span class="cat-hub-en">' . CuiHtml::e((string) $c['en']) . '</span>';
    echo '<span class="cat-hub-count">' . number_format((int) $c['count']) . ' <i>样本</i></span>';
    echo '<span class="cat-hub-tagline">' . CuiHtml::e((string) $c['tagline']) . '</span>';
    echo '<span class="cat-hub-subs">';
    foreach (array_slice($c['subs'], 0, 3) as $sn) {
        echo '<i>' . CuiHtml::e((string) $sn) . '</i>';
    }
    echo '</span></a>';
}
echo '</div>';
echo '<form class="cat-hub-search" action="' . Cui::url('/search') . '" method="get" role="search">';
echo '<span class="hub-search-icon">' . Cui::icon('search') . '</span>';
echo '<label class="sr-only" for="hub-q">搜索物料</label>';
echo '<input id="hub-q" type="search" name="q" placeholder="输入型号 / 品牌 / 封装 / 参数，例如：STM32、0402、TVS、DDR">';
echo '<button class="btn btn-primary" type="submit">搜全站</button>';
echo '</form>';
echo '<div class="cat-hub-foot"><span><b>' . count($categories) . '</b> 个品类</span><span><b>' . number_format($categoryTotal) . '</b> 条旧库样本</span><span>价格为演示参考 · 支持 BOM 配单</span></div>';
echo '</section>';

echo CuiPageHeader::render([
    'title' => '找料，不再靠猜',
    'subtitle' => '检测过、可追溯、可寄售的电子物料；上传 BOM，30 分钟出报价。',
    'actions' => [
        CuiButton::render(['label' => '上传 BOM', 'variant' => 'primary', 'icon' => 'upload', 'href' => '/bom']),
    ],
]);

echo CuiCard::render(
    '<div class="bom-cta"><div><span class="pill">BOM 配单</span><h2>上传 BOM，30 分钟出报价</h2><p>自动匹配自有库存、卖家寄售与外部供应商；缺料给替代建议。</p></div>'
    . CuiButton::render(['label' => '开始配单', 'variant' => 'primary', 'icon' => 'upload', 'href' => '/bom'])
    . '</div>',
    ['class' => 'bom-card']
);

echo CuiChips::render([
    ['label' => '全部', 'active' => true],
    ['label' => '连接器', 'href' => '/search?q=连接器'],
    ['label' => '被动件', 'href' => '/search?q=电容'],
    ['label' => 'IC', 'href' => '/search?q=STM32'],
    ['label' => '停产物料', 'href' => '/search?q=停产'],
    ['label' => '设备/仪器', 'href' => '/search'],
]);

echo CuiCard::header('为你推荐 · 检测过、可追溯', '', '<a class="link" href="' . Cui::url('/search') . '">查看全部 →</a>');
echo '<div class="product-grid">';
foreach ($products as $p) {
    echo CuiProductCard::render($p);
}
echo '</div>';

echo CuiCard::render(
    '<div class="grid3"><div class="mini"><b>120+</b><span>源头工厂/机构</span></div><div class="mini"><b>1,200+</b><span>活跃买家</span></div><div class="mini"><b>100%</b><span>货值 ≥5,000 元批次全检</span></div></div>',
    ['class' => 'trust-card']
);

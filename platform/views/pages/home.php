<?php
declare(strict_types=1);
/** @var array<int, array<string, mixed>> $products */
$products = CatalogRepositoryFactory::make()->all();

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

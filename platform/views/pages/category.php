<?php
declare(strict_types=1);
/** @var string $slug */
$slug = (string) ($slug ?? '');
$meta = MaterialCategory::find($slug);
if ($meta === null) {
    http_response_code(404);
    echo CuiPageHeader::render(['title' => '品类不存在', 'subtitle' => '请返回品类导航重新选择。']);
    echo CuiButton::render(['label' => '返回首页', 'href' => '/']);
    return;
}
$stats = ['offers' => 0, 'merchants' => 0, 'min_price' => null, 'avg_lead' => 0, 'subs' => []];
$offers = [];
$merchants = [];
if (Database::configured()) {
    $repo = new PdoCategoryCatalogRepository(Database::pdo());
    $stats = $repo->stats($slug);
    $merchants = $repo->merchants($slug);
    $offers = $repo->offers($slug, [
        'sub' => (string) ($_GET['sub'] ?? ''),
        'merchant' => (string) ($_GET['merchant'] ?? ''),
        'min_price' => (float) ($_GET['min_price'] ?? 0),
        'max_price' => (float) ($_GET['max_price'] ?? 0),
        'lead' => (int) ($_GET['lead'] ?? 0),
        'sort' => (string) ($_GET['sort'] ?? 'recommended'),
    ]);
}
$sub = (string) ($_GET['sub'] ?? '');
$selMerchant = (string) ($_GET['merchant'] ?? '');
$min = (string) ($_GET['min_price'] ?? '');
$max = (string) ($_GET['max_price'] ?? '');
$lead = (string) ($_GET['lead'] ?? '');
$sort = (string) ($_GET['sort'] ?? 'recommended');
$base = '/category/' . rawurlencode($slug);
$query = static function (array $overrides = []) use ($sub, $selMerchant, $min, $max, $lead, $sort): string {
    $q = array_filter([
        'sub' => $sub, 'merchant' => $selMerchant, 'min_price' => $min,
        'max_price' => $max, 'lead' => $lead, 'sort' => $sort,
    ], static fn ($v): bool => $v !== '' && $v !== '0');
    foreach ($overrides as $k => $v) {
        if ($v === '' || $v === null) { unset($q[$k]); } else { $q[$k] = $v; }
    }
    return $q === [] ? '' : '?' . http_build_query($q);
};
$price = static fn ($v): string => $v === null || (float) $v <= 0 ? '询价' : '¥' . number_format((float) $v, (float) $v < 1 ? 4 : 2);

echo '<section class="cat-hero" style="--cat:' . CuiHtml::e($meta['color']) . ';--cat-soft:' . CuiHtml::e($meta['soft']) . '">';
echo '<div class="cat-hero-main">';
echo CuiBreadcrumb::render([['label' => '首页', 'href' => '/'], ['label' => '品类寻料', 'href' => '/#categories'], ['label' => $meta['name']]]);
echo '<div class="cat-kicker">' . CuiHtml::e($meta['en']) . ' · LCSC 类目：' . CuiHtml::e($meta['lcsc']) . '</div>';
echo '<h1>' . CuiHtml::e($meta['name']) . '<span>' . CuiHtml::e($meta['tagline']) . '</span></h1>';
echo '<p>' . CuiHtml::e($meta['description']) . '</p>';
echo '<div class="cat-hero-stats">';
echo '<div><b>' . number_format((int) $meta['count']) . '</b><span>旧库物料样本</span></div>';
echo '<div><b>' . (int) $stats['offers'] . '</b><span>在售报价</span></div>';
echo '<div><b>' . (int) $stats['merchants'] . '</b><span>商家</span></div>';
$minPriceHtml = $stats['min_price'] !== null ? $price($stats['min_price']) : '—';
echo '<div><b>' . $minPriceHtml . '</b><span>起价</span></div>';
echo '<div><b>' . ($stats['avg_lead'] > 0 ? $stats['avg_lead'] . ' 天' : '—') . '</b><span>平均交期</span></div>';
echo '</div></div>';
echo '<div class="cat-hero-side"><span class="cat-watermark">' . Cui::icon((string) $meta['icon'], 'cat-icon-lg') . '</span>';
echo '<div class="cat-guide"><b>选型提示</b><ul>';
foreach ($meta['guide'] as $g) { echo '<li>' . CuiHtml::e($g) . '</li>'; }
echo '</ul></div></div></section>';

echo '<div class="cat-layout">';
echo '<main class="cat-main">';
echo '<div class="cat-toolbar">';
echo '<div class="cat-chips">';
echo '<a class="cat-chip ' . ($sub === '' ? 'active' : '') . '" href="' . Cui::url($base . $query(['sub' => ''])) . '">全部 <em>' . (int) $stats['offers'] . '</em></a>';
foreach ($meta['subs'] as $sk => $sn) {
    $count = (int) ($stats['subs'][$sk] ?? 0);
    if ($count === 0 && $stats['offers'] > 0) { continue; }
    echo '<a class="cat-chip ' . ($sub === $sk ? 'active' : '') . '" href="' . Cui::url($base . $query(['sub' => $sk])) . '">' . CuiHtml::e($sn) . ($count > 0 ? ' <em>' . $count . '</em>' : '') . '</a>';
}
echo '</div></div>';

echo '<form class="cat-filter" method="get" action="' . Cui::url($base) . '">';
echo '<input type="hidden" name="sub" value="' . CuiHtml::e($sub) . '">';
echo '<div class="filter-group"><span>商家</span><select name="merchant"><option value="">全部商家</option>';
foreach ($merchants as $m) {
    echo '<option value="' . CuiHtml::e($m['slug']) . '"' . ($selMerchant === $m['slug'] ? ' selected' : '') . '>' . CuiHtml::e($m['name']) . '（' . $m['count'] . '）</option>';
}
echo '</select></div>';
echo '<div class="filter-group filter-price"><span>价格</span><div class="price-inputs"><input class="input" name="min_price" type="number" step="0.01" placeholder="最低" value="' . CuiHtml::e($min) . '" aria-label="最低价格"><em>—</em><input class="input" name="max_price" type="number" step="0.01" placeholder="最高" value="' . CuiHtml::e($max) . '" aria-label="最高价格"></div></div>';
echo '<div class="filter-group"><span>交期</span><select name="lead"><option value="">不限</option><option value="3"' . ($lead === '3' ? ' selected' : '') . '>≤3 天</option><option value="7"' . ($lead === '7' ? ' selected' : '') . '>≤7 天</option><option value="14"' . ($lead === '14' ? ' selected' : '') . '>≤14 天</option></select></div>';
echo '<div class="filter-group"><span>排序</span><select name="sort"><option value="recommended"' . ($sort === 'recommended' ? ' selected' : '') . '>推荐</option><option value="price_asc"' . ($sort === 'price_asc' ? ' selected' : '') . '>价格低→高</option><option value="price_desc"' . ($sort === 'price_desc' ? ' selected' : '') . '>价格高→低</option><option value="lead_asc"' . ($sort === 'lead_asc' ? ' selected' : '') . '>交期短→长</option><option value="newest"' . ($sort === 'newest' ? ' selected' : '') . '>最新</option></select></div>';
echo '<div class="filter-actions">' . CuiButton::render(['label' => '筛选', 'variant' => 'primary', 'type' => 'submit']) . '<a class="cat-reset" href="' . Cui::url($base) . '">重置</a></div>';
echo '</form>';

if ($offers === []) {
    echo CuiEmpty::render('该品类暂无在售报价', '可以上传 BOM 让我们帮你找货，或切换到其他子类。', CuiButton::render(['label' => '上传 BOM', 'variant' => 'primary', 'href' => '/bom']));
} else {
    echo '<div class="cat-result-head"><span>共 <b>' . count($offers) . '</b> 条报价</span><span class="tiny">价格为演示参考，实际以询价/合同为准</span></div>';
    echo '<div class="cat-offers">';
    foreach ($offers as $o) {
        $attrs = $o['attrs'];
        $param = (string) ($attrs['value'] ?? '');
        $pkg = (string) ($attrs['package'] ?? '');
        $subName = MaterialCategory::subName($meta, (string) $o['subcategory_key']);
        $moq = (float) $o['moq'] > 0 ? rtrim(rtrim(number_format((float) $o['moq'], 2, '.', ''), '0'), '.') : '1';
        $conditionLabels = ['new' => '全新', 'surplus' => '余料', 'refurb' => '翻新', 'used' => '二手', 'scrap' => '报废'];
        $condition = $conditionLabels[(string) $o['item_condition']] ?? (string) $o['item_condition'];
        echo '<article class="cat-offer">';
        echo '<div class="offer-head"><div class="offer-merchant"><span class="merchant-dot">' . CuiHtml::e(mb_substr((string) $o['store_name'], 0, 1)) . '</span><div><a href="' . Cui::url('/shop/' . rawurlencode((string) $o['store_slug'])) . '">' . CuiHtml::e((string) $o['store_name']) . '</a><span class="tiny">' . ($o['store_rating'] > 0 ? '★ ' . number_format((float) $o['store_rating'], 1) : '新商家') . ' · ' . (int) $o['store_rating_count'] . ' 评价</span></div></div>';
        echo '<span class="offer-price">' . $price($o['price']) . '<small>/ ' . CuiHtml::e($moq) . ' 起</small></span></div>';
        echo '<a class="offer-title" href="' . Cui::url('/p/' . (int) $o['listing_id']) . '">' . CuiHtml::e((string) $o['title']) . '</a>';
        echo '<div class="offer-desc">' . CuiHtml::e((string) $o['description']) . '</div>';
        echo '<div class="offer-tags"><span>' . CuiHtml::e($subName) . '</span>' . ($pkg !== '' ? '<span>封装 ' . CuiHtml::e($pkg) . '</span>' : '') . '<span>批次 ' . CuiHtml::e((string) $o['grade']) . '</span><span>' . CuiHtml::e($condition) . '</span></div>';
        echo '<div class="offer-meta"><span>库存 <b>' . number_format((float) $o['quantity'], 0) . '</b></span><span>交期 <b>' . (int) $o['lead_time_days'] . ' 天</b></span><span>质保 ' . (int) $o['warranty_days'] . ' 天</span>' . ($param !== '' ? '<span class="offer-param">' . CuiHtml::e($param) . '</span>' : '') . '</div>';
        echo '<div class="offer-foot"><span class="tiny">料号 ' . CuiHtml::e((string) $o['part_number']) . '</span><a class="link" href="' . Cui::url('/p/' . (int) $o['listing_id']) . '">查看参数与商家 →</a></div>';
        echo '</article>';
    }
    echo '</div>';
}
echo '</main>';

echo '<aside class="cat-aside">';
echo '<div class="cat-aside-card"><h3>按参数选型</h3><p>下单前核对以下字段，避免替代料风险。</p><div class="param-list">';
foreach ($meta['params'] as $p) { echo '<span>' . CuiHtml::e($p) . '</span>'; }
echo '</div></div>';
echo '<div class="cat-aside-card"><h3>商家</h3>';
if ($merchants === []) { echo '<p class="muted">暂无商家报价。</p>'; }
foreach ($merchants as $m) {
    echo '<a class="aside-merchant" href="' . Cui::url($base . $query(['merchant' => $m['slug']])) . '"><b>' . CuiHtml::e($m['name']) . '</b><span>' . $m['count'] . ' 条报价 →</span></a>';
}
echo '</div>';
echo '<div class="cat-aside-card cat-aside-note"><b>数据说明</b><p>品类样本来自旧 <code>hardware.sql</code>（Greatwall CIS）；报价/库存/交期为演示数据，不代表真实供货。</p><a href="' . Cui::url('/bom') . '">上传 BOM 获取正式报价 →</a></div>';
echo '</aside></div>';

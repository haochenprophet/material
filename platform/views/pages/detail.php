<?php
declare(strict_types=1);
/** @var string $id */
$dbProduct = null;
if (CatalogRepositoryFactory::source() === 'db') {
    $dbProduct = CatalogRepositoryFactory::make()->find((string) ($id ?? ''));
}
$d = $dbProduct !== null ? (new CatalogDetailPresenter())->present($dbProduct) : DemoData::detail();
$images = [];
$prov = null;
$comments = [];
$reviews = [];
$credit = null;
$store = null;
$reviewRepo = null;
$distribution = [];
$history = [];
$historyPoints = [];
$historySpark = '';
if ($dbProduct !== null && Database::configured()) {
    $pdo = Database::pdo();
    $commerce = new PdoCommerceRepository($pdo);
    $materialId = (int) ($dbProduct['material_id'] ?? 0);
    $storeId = (int) ($dbProduct['store_id'] ?? 0);
    $images = $commerce->images($materialId);
    $prov = $commerce->provenance($materialId);
    $comments = $commerce->commentsTree($materialId);
    $reviewRepo = new PdoReviewRepository($pdo);
    $reviews = $reviewRepo->forMaterial($materialId, 20);
    $distribution = $reviewRepo->distribution($materialId);
    $credit = (new PdoCreditRepository($pdo))->material($materialId);
    $history = (new PdoCreditRepository($pdo))->history('material', $materialId, 10);
    $historyPoints = (new CreditHistory())->points($history);
    $historySpark = (new CreditHistory())->sparkline($historyPoints, 320, 64);
    if ($storeId > 0) {
        $store = (new PdoStoreRepository($pdo))->findById($storeId);
        if ($store !== null) {
            $d['seller'] = (string) $store['name'];
            $d['badges'][] = ['商家 ' . (string) ($store['grade'] ?? 'NA'), 'blue'];
        }
    }
    if ($credit !== null) {
        $d['badges'][] = ['信用 ' . (string) $credit['grade'] . ' · ' . (int) $credit['score'], 'ok'];
    }
}
if ($dbProduct === null && (($id ?? 'p1') !== 'p1')) {
    $p = DemoData::product((string) $id);
    if ($p === null) {
        echo CuiEmpty::render('物料不存在', '可能已下架，去看同类物料。', CuiButton::render(['label' => '返回搜索', 'href' => '/search']));
        return;
    }
    $d['title'] = $p['title'];
    $d['price'] = $p['price'];
    $d['unit'] = $p['unit'] ?? '个';
    $d['badges'] = [[$p['badge'] ?? '在售', 'ok'], ['寄售', 'blue'], [$p['location'] ?? '', '']];
    $d['stock'] = $p['stock'];
}

echo CuiBreadcrumb::render($d['crumbs']);
echo CuiPageHeader::render([
    'title' => $d['title'],
    'subtitle' => '检测报告随货 · 30 天质保 · 寄售/撮合均可开票',
    'actions' => [
        CuiButton::render(['label' => '分享', 'icon' => 'share', 'data' => ['action' => 'toast', 'toast' => '已复制分享链接']]),
    ],
]);

$badges = '';
foreach ($d['badges'] as $b) {
    $badges .= CuiBadge::render((string) $b[0], (string) $b[1]);
}

echo '<div class="detail-grid">';
echo '<div class="detail-gallery">';
if ($images !== []) {
    $main = (string) $images[0]['path'];
    $gallery = '<div class="gallery"><div class="main-img"><img src="' . CuiHtml::e($main) . '" alt="' . CuiHtml::e($d['title']) . '"></div><div class="thumbs">';
    foreach ($images as $img) {
        $gallery .= '<a href="#gallery" class="thumb-link" data-src="' . CuiHtml::e((string) $img['path']) . '"><img src="' . CuiHtml::e((string) $img['path']) . '" alt=""></a>';
    }
    $gallery .= '</div></div>';
    echo CuiCard::render($gallery, ['class' => 'gallery-card']);
} else {
    echo CuiCard::render('<div class="gallery"><div class="main-img">' . CuiHtml::e($d['title']) . ' · 实拍图</div><div class="thumbs"><span></span><span></span><span></span><span></span></div></div>', ['class' => 'gallery-card']);
}
echo '</div>';

$buy = '<div class="meta-row">' . $badges . '</div>';
$buy .= '<h2 class="title-lg">' . CuiHtml::e($d['title']) . '</h2>';
$buy .= '<div class="price-lg">' . CuiHtml::e($d['price']) . ' <small>/ ' . CuiHtml::e($d['unit']) . '（' . CuiHtml::e($d['tax']) . '）</small></div>';
$buy .= CuiTable::render(['数量', '单价'], array_map(static fn ($t) => [$t[0], $t[1]], $d['tier']), ['class' => 'tier-table static-table compact']);
$buy .= '<div class="kv"><b>库存</b><span>' . CuiHtml::e($d['stock']) . '</span><b>交期</b><span>' . CuiHtml::e($d['lead']) . '</span><b>质保</b><span>' . CuiHtml::e($d['warranty']) . '</span><b>封装</b><span>' . CuiHtml::e($d['package']) . '</span><b>卖家</b><span>' . CuiHtml::e($d['seller']) . '</span></div>';
$buy .= '<div class="buy-actions">' . CuiButton::render(['label' => '加入询价', 'data' => ['action' => 'toast', 'toast' => '已加入询价']]) . CuiButton::render(['label' => '立即购买', 'variant' => 'primary', 'data' => ['action' => 'toast', 'toast' => '演示：跳转支付']]) . '</div>';
if ($store !== null) {
    $buy .= '<div class="merchant-mini"><div><b>' . CuiHtml::e((string) $store['name']) . '</b><br><small class="muted">商家信用 ' . CuiHtml::e((string) ($store['grade'] ?? 'NA')) . ' · 评分 ' . number_format((float) ($store['rating'] ?? 0), 1) . '</small></div>'
        . CuiButton::render(['label' => '进入商家商城', 'href' => '/shop/' . rawurlencode((string) $store['slug'])]) . '</div>';
}

echo '<div class="detail-buy">';
echo CuiCard::render($buy, ['class' => 'buybox']);
echo CuiCard::render('<div class="section-head"><h3>质检与信任</h3><span>报告编号 QC-20261124-018</span></div><p class="muted">外观/丝印/包装/电性能已检；X-ray 按抽检规则未做。报告随货，可扫码验真。</p><div class="trust-row">' . CuiBadge::render('A 级质检', 'ok') . CuiBadge::render('30 天质保', 'blue') . CuiBadge::render('检测报告', '') . '</div>');
echo '</div>';

echo '<div class="detail-specs">';
echo CuiCard::render(CuiCard::header('规格参数', '查看手册') . CuiTable::render(['参数', '值'], array_map(static fn ($s) => [$s[0], $s[1]], $d['specs']), ['class' => 'compact static-table']));
echo '</div>';
echo '</div>';

if ($prov !== null) {
    echo '<div class="commerce-section">' . CuiCard::render(CuiCard::header('物料溯源', '可核验') . '<div class="kv"><b>溯源码</b><span><code>' . CuiHtml::e((string) $prov['trace_code']) . '</code></span><b>来源</b><span>' . CuiHtml::e((string) $prov['source_name']) . '</span><b>批次</b><span>' . CuiHtml::e((string) ($prov['batch_no'] ?? '—')) . '</span><b>原产国</b><span>' . CuiHtml::e((string) ($prov['country'] ?? '—')) . '</span><b>采购日期</b><span>' . CuiHtml::e((string) ($prov['purchase_date'] ?? '—')) . '</span><b>质保</b><span>' . (int) $prov['warranty_days'] . ' 天</span></div><p class="small muted">来源/批次/认证由商家提报并留痕；后续接入扫码验真与检测报告。</p>') . '</div>';
}

$reviewOk = Cui::flash('review_result');
$reviewErr = Cui::flash('review_error');
if ($reviewOk !== null) { echo '<div class="alert alert-ok">' . CuiHtml::e($reviewOk) . '</div>'; }
if ($reviewErr !== null) { echo '<div class="alert alert-error">' . CuiHtml::e($reviewErr) . '</div>'; }
echo '<div class="commerce-section" id="reviews">' . CuiCard::render(CuiCard::header('物料点评', ($credit !== null ? (int) $credit['review_count'] : count($reviews)) . ' 条 · ' . ($credit !== null ? (string) $credit['grade'] : 'NA') . ' 级') . '<div class="review-summary">'
    . '<div class="review-score"><b>' . ($credit !== null ? number_format((float) $credit['avg_rating'], 1) : '—') . '</b><span>综合评分</span></div>'
    . '<div class="review-bars"><span>质量 ' . ($credit !== null ? number_format((float) $credit['quality_avg'], 1) : '—') . '</span><span>价格 ' . ($credit !== null ? number_format((float) $credit['price_avg'], 1) : '—') . '</span><span>交付 ' . ($credit !== null ? number_format((float) $credit['delivery_avg'], 1) : '—') . '</span><span>服务 ' . ($credit !== null ? number_format((float) $credit['service_avg'], 1) : '—') . '</span></div>'
    . '<div class="review-credit"><b>' . ($credit !== null ? (string) $credit['score'] : '—') . '</b><span>物料信用分</span></div></div>');
$distTotal = array_sum($distribution);
if ($distTotal > 0) {
    echo '<div class="rating-dist">';
    for ($s = 5; $s >= 1; $s--) {
        $cnt = (int) ($distribution[$s] ?? 0);
        $pct = $distTotal > 0 ? round($cnt / $distTotal * 100) : 0;
        echo '<div class="dist-row"><span>' . $s . ' 星</span><div class="dist-track"><i style="width:' . $pct . '%"></i></div><em>' . $cnt . '</em></div>';
    }
    echo '</div>';
}
if ($historySpark !== '') {
    echo '<div class="credit-history"><div class="section-head"><h3>信用历史</h3><span>最近 ' . count($history) . ' 次重算</span></div>' . $historySpark . '<div class="credit-events">';
    foreach (array_slice($history, 0, 5) as $e) {
        $delta = (int) ($e['delta'] ?? 0);
        echo '<span>' . CuiHtml::e(substr((string) $e['created_at'], 0, 16)) . ' · ' . (int) $e['score_before'] . ' → ' . (int) $e['score_after'] . '（' . ($delta >= 0 ? '+' : '') . $delta . '）</span>';
    }
    echo '</div></div>';
}
if ($reviews === []) {
    echo '<div class="notice">暂无点评，欢迎第一单后评价。</div>';
}
foreach ($reviews as $r) {
    $stars = str_repeat('★', (int) $r['rating']) . str_repeat('☆', 5 - (int) $r['rating']);
    echo '<div class="review-item"><div class="review-top"><b>' . CuiHtml::e((string) $r['author_name']) . '</b><span class="stars">' . $stars . '</span>' . ((int) $r['verified_purchase'] === 1 ? '<span class="badge badge-ok">真实交易</span>' : '') . '<span class="muted small">' . CuiHtml::e(substr((string) $r['created_at'], 0, 10)) . '</span></div><p>' . CuiHtml::e((string) $r['content']) . '</p><div class="small muted">质量 ' . (int) $r['quality'] . ' · 价格 ' . (int) $r['price'] . ' · 交付 ' . (int) $r['delivery'] . ' · 服务 ' . (int) $r['service'] . '</div>';
    if ($reviewRepo !== null) {
        $replies = $reviewRepo->replies((int) $r['id']);
        foreach ($replies as $rp) {
            echo '<div class="review-reply"><b>' . CuiHtml::e((string) $rp['author_name']) . ' 回复</b><p>' . CuiHtml::e((string) $rp['content']) . '</p></div>';
        }
        if (Auth::can('admin.view') || Auth::can('listing.create')) {
            echo '<form method="post" action="' . Cui::url('/reviews/' . (int) $r['id'] . '/reply') . '" class="reply-form">' . Cui::csrfField()
                . '<input class="input" name="content" placeholder="商家回复…" required><button class="btn btn-sm" type="submit">回复</button></form>';
        }
    }
    echo '</div>';
}
if ($dbProduct !== null) {
    $reviewed = false;
    if ($reviewRepo !== null && Auth::check()) {
        $u = Auth::user() ?? [];
        $reviewed = $reviewRepo->hasReviewed($reviewRepo->reviewerKey(Auth::id(), (string) ($u['account'] ?? '')), (int) $dbProduct['listing_id']);
    }
    echo '<div class="review-form"><h4>写点评</h4>';
    if (!Auth::check()) {
        echo '<p class="muted">登录后可点评。' . CuiButton::render(['label' => '去登录', 'href' => '/login?next=' . rawurlencode('/p/' . (int) $dbProduct['listing_id'] . '#reviews')]) . '</p>';
    } elseif ($reviewed) {
        echo '<p class="muted">你已点评过该物料，感谢反馈。</p>';
    } else {
        echo '<form method="post" action="' . Cui::url('/p/' . (int) $dbProduct['listing_id'] . '/reviews') . '">' . Cui::csrfField();
        foreach (['rating' => '综合', 'quality' => '质量', 'price' => '价格', 'delivery' => '交付', 'service' => '服务'] as $n => $label) {
            echo '<label class="review-field">' . $label . '<select class="input" name="' . $n . '">';
            for ($i = 5; $i >= 1; $i--) {
                echo '<option value="' . $i . '"' . ($i === 5 ? ' selected' : '') . '>' . $i . ' 星</option>';
            }
            echo '</select></label>';
        }
        echo '<textarea class="input" name="content" rows="3" placeholder="质量、批次、交期、服务…" required></textarea><label class="small"><input type="checkbox" name="verified_purchase" value="1"> 我确认收到货</label>';
        echo CuiButton::render(['label' => '发布点评', 'variant' => 'primary', 'type' => 'submit']) . '</form>';
    }
    echo '</div>';
}
echo '</div>';

$renderComments = static function (array $nodes, int $depth = 0) use (&$renderComments): string {
    $html = '<div class="comment-tree" style="margin-left:' . ($depth * 18) . 'px">';
    foreach ($nodes as $n) {
        $html .= '<div class="comment-item"><div class="comment-top"><b>' . CuiHtml::e((string) $n['author_name']) . '</b><span class="muted small">' . CuiHtml::e((string) $n['author_role']) . ' · ' . CuiHtml::e(substr((string) $n['created_at'], 0, 10)) . '</span></div><p>' . CuiHtml::e((string) $n['content']) . '</p><button type="button" class="link-btn" data-reply="' . (int) $n['id'] . '">回复</button>';
        if (!empty($n['replies'])) {
            $html .= $renderComments($n['replies'], $depth + 1);
        }
        $html .= '</div>';
    }
    return $html . '</div>';
};
echo '<div class="commerce-section" id="comments">' . CuiCard::render(CuiCard::header('评论树', count($comments) . ' 个根评论') . $renderComments($comments));
if ($dbProduct !== null) {
    echo '<div class="review-form"><h4>参与评论</h4>';
    if (!Auth::check()) {
        echo '<p class="muted">登录后可评论。</p>';
    } else {
        echo '<form method="post" action="' . Cui::url('/p/' . (int) $dbProduct['listing_id'] . '/comments') . '">' . Cui::csrfField()
            . '<input type="hidden" name="parent_id" id="comment-parent" value="">'
            . '<textarea class="input" name="content" rows="2" placeholder="补充批次/使用体验…" required></textarea>'
            . CuiButton::render(['label' => '发表评论', 'type' => 'submit']) . '</form>'
            . '<p class="small muted">最多 3 层；点“回复”会指定父评论。</p>';
    }
    echo '</div>';
}
echo '</div>';

echo '<div class="sticky-buy"><div class="price-lg">' . CuiHtml::e($d['price']) . ' <small>/ ' . CuiHtml::e($d['unit']) . '</small></div>' . CuiButton::render(['label' => '询价']) . CuiButton::render(['label' => '立即购买', 'variant' => 'primary']) . '</div>';

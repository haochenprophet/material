<?php
declare(strict_types=1);
$merchants = [];
$materials = [];
$pending = [];
$appeals = [];
$events = [];
if (Database::configured()) {
    $pdo = Database::pdo();
    $credit = new PdoCreditRepository($pdo);
    $merchants = $credit->merchants();
    $materials = $credit->materials();
    $reviewRepo = new PdoReviewRepository($pdo);
    $pending = $reviewRepo->pending(20);
    $appeals = $reviewRepo->appeals();
    $events = $credit->recentEvents(15);
}
$ok = Cui::flash('credit_result');
$err = Cui::flash('credit_error');
echo CuiBreadcrumb::render([['label' => '运营后台', 'href' => '/admin/orders'], ['label' => '信用与商家']]);
echo CuiPageHeader::render([
    'title' => '信用与商家',
    'subtitle' => '商家信用由全部物料点评聚合；隐藏违规点评后自动重算。',
    'actions' => [CuiButton::render(['label' => '商城', 'href' => '/shops'])],
]);
if ($ok !== null) {
    echo '<div class="alert alert-ok">' . CuiHtml::e($ok) . '</div>';
}
if ($err !== null) {
    echo '<div class="alert alert-error">' . CuiHtml::e($err) . '</div>';
}
echo '<form method="post" action="' . Cui::url('/admin/credits/recalculate') . '" style="margin:12px 0">' . Cui::csrfField() . CuiButton::render(['label' => '全量重算信用', 'type' => 'submit']) . '</form>';

$rows = [];
foreach ($merchants as $m) {
    $status = (string) $m['status'];
    $form = '<form method="post" action="' . Cui::url('/admin/merchants/' . (int) $m['id'] . '/status') . '" style="display:inline-flex;gap:4px">'
        . Cui::csrfField()
        . '<select class="input" name="status" style="min-height:30px;font-size:10px"><option value="active"' . ($status === 'active' ? ' selected' : '') . '>active</option><option value="paused"' . ($status === 'paused' ? ' selected' : '') . '>paused</option></select>'
        . CuiButton::render(['label' => '保存', 'size' => 'sm', 'type' => 'submit']) . '</form>';
    $rows[] = [
        '<b>' . CuiHtml::e((string) $m['name']) . '</b><br><small class="muted">' . CuiHtml::e((string) ($m['slug'] ?? '')) . '</small>',
        CuiHtml::e((string) ($m['merchant_code'] ?? '—')),
        '<span class="badge badge-' . (($m['grade'] ?? '') === 'AAA' || ($m['grade'] ?? '') === 'AA' ? 'ok' : '') . '">' . CuiHtml::e((string) ($m['grade'] ?? 'NA')) . '</span>',
        (int) ($m['score'] ?? 0),
        (int) ($m['review_count'] ?? 0),
        (int) ($m['listed_count'] ?? 0),
        CuiHtml::e($status),
        $form,
    ];
}
echo CuiCard::render(CuiCard::header('商家', count($merchants) . ' 家') . CuiTable::render(['商家', '编码', '等级', '信用分', '点评', '在售', '状态', '操作'], $rows));

$materialRows = [];
foreach ($materials as $m) {
    $materialRows[] = [
        '#' . (int) $m['material_id'],
        CuiHtml::e((string) $m['part_number']),
        CuiHtml::e((string) $m['name']),
        (int) $m['review_count'],
        number_format((float) $m['avg_rating'], 2),
        '<b>' . (int) $m['score'] . '</b>',
        CuiHtml::e((string) $m['grade']),
        number_format((float) $m['positive_rate'], 1) . '%',
    ];
}
echo CuiCard::render(CuiCard::header('物料信用', '按点评聚合') . CuiTable::render(['ID', 'MPN', '名称', '点评数', '均分', '信用分', '等级', '好评率'], $materialRows));

$reviewRows = [];
foreach ($pending as $r) {
    $action = '<form method="post" action="' . Cui::url('/admin/reviews/' . (int) $r['id'] . '/approve') . '" style="display:inline-flex;gap:4px">' . Cui::csrfField()
        . '<input class="input" name="note" placeholder="审核备注" style="min-height:30px;width:100px;font-size:10px">'
        . CuiButton::render(['label' => '通过', 'size' => 'sm', 'type' => 'submit']) . '</form> '
        . '<form method="post" action="' . Cui::url('/admin/reviews/' . (int) $r['id'] . '/reject') . '" style="display:inline-flex;gap:4px">' . Cui::csrfField()
        . '<input class="input" name="note" placeholder="驳回原因" style="min-height:30px;width:100px;font-size:10px">'
        . CuiButton::render(['label' => '驳回', 'size' => 'sm', 'type' => 'submit']) . '</form>';
    $reviewRows[] = [
        '#' . (int) $r['id'],
        CuiHtml::e((string) $r['author_name']),
        str_repeat('★', (int) $r['rating']),
        CuiHtml::e(mb_substr((string) $r['content'], 0, 50)),
        $action,
    ];
}
echo CuiCard::render(CuiCard::header('待审点评', count($pending) . ' 条') . ($pending === [] ? '<div class="notice">暂无待审点评。</div>' : CuiTable::render(['ID', '作者', '评分', '内容', '操作'], $reviewRows)));

$appealRows = [];
foreach ($appeals as $a) {
    $acts = '<form method="post" action="' . Cui::url('/admin/appeals/' . (int) $a['id'] . '/resolve') . '" style="display:inline">' . Cui::csrfField()
        . '<input type="hidden" name="status" value="accepted"><button class="btn btn-sm" type="submit">接受</button></form> '
        . '<form method="post" action="' . Cui::url('/admin/appeals/' . (int) $a['id'] . '/resolve') . '" style="display:inline">' . Cui::csrfField()
        . '<input type="hidden" name="status" value="rejected"><button class="btn btn-sm" type="submit">驳回</button></form>';
    $appealRows[] = [
        '#' . (int) $a['id'],
        '#' . (int) $a['review_id'],
        CuiHtml::e((string) ($a['store_name'] ?? '—')),
        CuiHtml::e(mb_substr((string) $a['reason'], 0, 60)),
        CuiHtml::e((string) $a['status']),
        $a['status'] === 'pending' ? $acts : '—',
    ];
}
echo CuiCard::render(CuiCard::header('商家申诉', count($appeals) . ' 条') . ($appeals === [] ? '<div class="notice">暂无申诉。</div>' : CuiTable::render(['ID', '点评', '商家', '理由', '状态', '操作'], $appealRows)));
echo '<p class="small muted">商家状态只允许 active/paused；不删除商家。审核/申诉写 audit_log，信用重算写 credit_event。</p>';

$eventRows = [];
foreach ($events as $e) {
    $eventRows[] = [
        '#' . (int) $e['id'],
        CuiHtml::e((string) $e['subject_type']) . ' #' . (int) $e['subject_id'],
        CuiHtml::e((string) $e['event_type']),
        (int) $e['score_before'] . ' → ' . (int) $e['score_after'],
        ((int) $e['delta'] >= 0 ? '+' : '') . (int) $e['delta'],
        CuiHtml::e((string) $e['created_at']),
    ];
}
echo CuiCard::render(CuiCard::header('最近信用事件', count($events) . ' 条') . ($events === [] ? '<div class="notice">暂无事件。</div>' : CuiTable::render(['ID', '主体', '事件', '分数', 'Δ', '时间'], $eventRows)));

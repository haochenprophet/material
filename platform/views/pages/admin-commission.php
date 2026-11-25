<?php
declare(strict_types=1);
$rules = [];
$resolved = null;
$calc = null;
$gross = (float) ($_GET['gross'] ?? 1000);
$category = (int) ($_GET['category_id'] ?? 11);
$sellerType = (string) ($_GET['seller_type'] ?? 'company');
$date = (string) ($_GET['date'] ?? date('Y-m-d'));
if (Database::configured()) {
    $pdo = Database::pdo();
    $repo = new PdoCommissionRuleRepository($pdo);
    $rules = $repo->rules();
    $resolved = $repo->resolve($category, $sellerType, $date);
    $calc = (new CommissionCalculator())->calculate($gross, (float) $resolved['rate'], (float) $resolved['min_fee'], $resolved['max_fee'] !== null ? (float) $resolved['max_fee'] : null);
}
$ok = Cui::flash('commission_result');
$err = Cui::flash('commission_error');
echo CuiBreadcrumb::render([['label' => '运营后台', 'href' => '/admin/orders'], ['label' => '佣金规则']]);
echo CuiPageHeader::render(['title' => '结算 / 佣金规则', 'subtitle' => '版本化规则、类目/卖家类型/生效日期；最低/最高佣金。']);
if ($ok !== null) { echo '<div class="alert alert-ok">' . CuiHtml::e($ok) . '</div>'; }
if ($err !== null) { echo '<div class="alert alert-error">' . CuiHtml::e($err) . '</div>'; }

echo '<form method="get" action="' . Cui::url('/admin/commission') . '">';
echo CuiCard::render(CuiCard::header('佣金试算', '按规则解析') . '<div class="form2">'
    . CuiField::render(['label' => '商品金额', 'name' => 'gross', 'value' => (string) $gross, 'type' => 'number'])
    . CuiField::render(['label' => '类目 ID', 'name' => 'category_id', 'value' => (string) $category])
    . '</div><div class="form2">'
    . CuiField::render(['label' => '卖家类型', 'name' => 'seller_type', 'type' => 'select', 'value' => $sellerType, 'options' => ['company' => '企业', 'individual' => '个人', 'school' => '学校', 'any' => '任意']])
    . CuiField::render(['label' => '生效日期', 'name' => 'date', 'value' => $date])
    . '</div>' . CuiButton::render(['label' => '试算', 'type' => 'submit']));
echo '</form>';
if ($calc !== null && $resolved !== null) {
    echo '<div class="kpis"><div class="kpi"><b>¥' . number_format($calc['gross'], 2) . '</b><span>商品金额</span></div><div class="kpi"><b>' . ((float) $resolved['rate'] * 100) . '%</b><span>命中费率 / ' . CuiHtml::e((string) $resolved['rule_key']) . '</span></div><div class="kpi"><b>¥' . number_format($calc['commission'], 2) . '</b><span>平台佣金</span></div><div class="kpi"><b>¥' . number_format($calc['seller_net'], 2) . '</b><span>卖家应结</span></div></div>';
}

echo '<form method="post" action="' . Cui::url('/admin/commission/create') . '">' . Cui::csrfField();
echo CuiCard::render(CuiCard::header('新增规则版本', '同 key 自动递增 version') . '<div class="form2">'
    . CuiField::render(['label' => '规则 key', 'name' => 'rule_key', 'value' => 'connector', 'required' => true])
    . CuiField::render(['label' => '类目 ID', 'name' => 'category_id', 'value' => '11'])
    . '</div><div class="form2">'
    . CuiField::render(['label' => '卖家类型', 'name' => 'seller_type', 'type' => 'select', 'value' => 'company', 'options' => ['company' => '企业', 'individual' => '个人', 'school' => '学校', 'any' => '任意']])
    . CuiField::render(['label' => '费率（0–1）', 'name' => 'rate', 'value' => '0.08'])
    . '</div><div class="form2">'
    . CuiField::render(['label' => '最低佣金', 'name' => 'min_fee', 'value' => '2'])
    . CuiField::render(['label' => '最高佣金', 'name' => 'max_fee', 'value' => '50'])
    . '</div><div class="form2">'
    . CuiField::render(['label' => '生效日', 'name' => 'effective_from', 'value' => date('Y-m-d')])
    . CuiField::render(['label' => '失效日', 'name' => 'effective_to', 'value' => ''])
    . '</div>' . CuiButton::render(['label' => '创建版本', 'variant' => 'primary', 'type' => 'submit']));
echo '</form>';

$rows = [];
foreach ($rules as $r) {
    $rows[] = [
        '#' . (int) $r['id'],
        CuiHtml::e((string) $r['rule_key']),
        'v' . (int) $r['version'],
        $r['category_id'] !== null ? '#' . (int) $r['category_id'] : '全部',
        CuiHtml::e((string) $r['seller_type']),
        ((float) $r['rate'] * 100) . '%',
        '¥' . number_format((float) $r['min_fee'], 2) . ' / ' . ($r['max_fee'] !== null ? '¥' . number_format((float) $r['max_fee'], 2) : '—'),
        CuiHtml::e((string) $r['effective_from']) . ' ~ ' . CuiHtml::e((string) ($r['effective_to'] ?? '—')),
        '<span class="badge badge-' . ($r['status'] === 'active' ? 'ok' : '') . '">' . CuiHtml::e((string) $r['status']) . '</span>',
        $r['status'] === 'active' ? '<form method="post" action="' . Cui::url('/admin/commission/' . (int) $r['id'] . '/disable') . '" style="display:inline">' . Cui::csrfField() . '<button class="btn btn-sm" type="submit">停用</button></form>' : '—',
    ];
}
echo CuiCard::render(CuiCard::header('规则版本', count($rules) . ' 条') . CuiTable::render(['ID', 'key', '版本', '类目', '卖家', '费率', '最低/最高', '有效期', '状态', '操作'], $rows));
echo '<p class="small muted">规则版本只增不改；停用后回退到较低优先级规则或 20% 默认。结算/发票落库在 Q10 后续接入真实订单。</p>';

<?php
declare(strict_types=1);
$payments = [];
$recon = ['payments' => 0, 'splits' => 0, 'diff' => 0];
if (Database::configured()) {
    $repo = new PdoSandboxPaymentRepository(Database::pdo());
    $payments = $repo->payments(50);
    $recon = $repo->reconcile();
}
$ok = Cui::flash('pay_result');
$err = Cui::flash('pay_error');
echo CuiBreadcrumb::render([['label' => '运营后台', 'href' => '/admin/orders'], ['label' => '沙箱支付']]);
echo CuiPageHeader::render([
    'title' => '沙箱支付 / 分账',
    'subtitle' => '仅沙箱，不接真实资金；用于支付→分账→退款→对账演练。',
    'actions' => [CuiButton::render(['label' => '结算', 'href' => '/seller/settlements'])],
]);
if ($ok !== null) { echo '<div class="alert alert-ok">' . CuiHtml::e($ok) . '</div>'; }
if ($err !== null) { echo '<div class="alert alert-error">' . CuiHtml::e($err) . '</div>'; }
echo '<div class="callout warn"><b>生产边界：</b>沙箱支付不产生真实扣款；上线需持牌支付机构、回调验签、清算对账与退款审批。</div>';
echo '<div class="kpis"><div class="kpi"><b>¥' . number_format($recon['payments'], 2) . '</b><span>成功支付</span></div><div class="kpi"><b>¥' . number_format($recon['splits'], 2) . '</b><span>已分账</span></div><div class="kpi ' . (abs($recon['diff']) < 0.01 ? '' : 'amber') . '"><b>' . number_format($recon['diff'], 2) . '</b><span>对账差</span></div><div class="kpi"><b>' . count($payments) . '</b><span>支付单</span></div></div>';

echo '<form method="post" action="' . Cui::url('/admin/payments/create') . '">' . Cui::csrfField();
echo CuiCard::render(CuiCard::header('创建沙箱支付', '订单号 + 金额') . '<div class="form2">'
    . CuiField::render(['label' => '订单号', 'name' => 'order_no', 'value' => 'SO-20261125-SANDBOX'])
    . CuiField::render(['label' => '金额', 'name' => 'amount', 'value' => '225.55', 'type' => 'number'])
    . '</div><div class="form2">'
    . CuiField::render(['label' => '渠道', 'name' => 'channel', 'type' => 'select', 'value' => 'wechat', 'options' => ['wechat' => '微信', 'alipay' => '支付宝', 'bank' => '银行', 'offline' => '线下']])
    . CuiField::render(['label' => '幂等键', 'name' => 'idempotency_key', 'value' => 'sandbox-' . bin2hex(random_bytes(4))])
    . '</div>' . CuiButton::render(['label' => '创建', 'variant' => 'primary', 'type' => 'submit']));
echo '</form>';

$rows = [];
foreach ($payments as $p) {
    $refunded = 0.0;
    if (Database::configured()) {
        $refunded = (new PdoSandboxPaymentRepository(Database::pdo()))->refundedAmount((int) $p['id']);
    }
    $actions = '<form method="post" action="' . Cui::url('/admin/payments/' . rawurlencode((string) $p['payment_no']) . '/capture') . '" style="display:inline">' . Cui::csrfField() . '<button class="btn btn-sm" type="submit">捕获</button></form> '
        . '<form method="post" action="' . Cui::url('/admin/payments/' . rawurlencode((string) $p['payment_no']) . '/refund') . '" style="display:inline-flex;gap:4px">' . Cui::csrfField()
        . '<input class="input" name="amount" value="' . number_format(max(0, (float) $p['amount'] - $refunded), 2, '.', '') . '" style="min-height:30px;width:74px;font-size:10px">'
        . '<button class="btn btn-sm" type="submit">退款</button></form>';
    $rows[] = [
        '<code>' . CuiHtml::e((string) $p['payment_no']) . '</code>',
        CuiHtml::e((string) $p['order_no']),
        '¥' . number_format((float) $p['amount'], 2),
        CuiHtml::e((string) $p['channel']),
        '<span class="badge badge-' . ($p['status'] === 'succeeded' ? 'ok' : ($p['status'] === 'created' ? 'warn' : '')) . '">' . CuiHtml::e((string) $p['status']) . '</span>',
        '¥' . number_format($refunded, 2),
        $actions,
    ];
}
echo CuiCard::render(CuiCard::header('支付单', count($payments) . ' 条') . ($payments === [] ? '<div class="notice">暂无沙箱支付。</div>' : CuiTable::render(['支付号', '订单', '金额', '渠道', '状态', '已退', '操作'], $rows)));
echo '<p class="small muted">分账比例：卖家 90% / 平台佣金 10%；退款不删除支付单。</p>';

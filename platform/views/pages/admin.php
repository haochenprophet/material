<?php
declare(strict_types=1);
$kpis = DemoData::kpis();
$orders = DemoData::orders();

echo CuiPageHeader::render([
    'title' => '运营工作台',
    'subtitle' => '今天需要处理的审核、异常、结算；移动端只保留查看/审批。',
    'actions' => [CuiButton::render(['label' => '导出', 'icon' => 'file']), CuiButton::render(['label' => '批量审核', 'variant' => 'primary', 'data' => ['action' => 'toast', 'toast' => '演示：批量审核']])],
]);

echo '<div class="kpis">';
foreach ($kpis as $k) {
    echo CuiKpi::render($k['value'], $k['label'], $k['trend'] ?? '');
}
echo '</div>';

echo '<div class="filterbar">';
echo '<select class="input" aria-label="状态"><option>全部状态</option><option>待发货</option><option>待结算</option><option>争议</option></select>';
echo '<select class="input" aria-label="卖家"><option>全部卖家</option><option>华强北-01</option><option>苏州-02</option></select>';
echo CuiButton::render(['label' => '筛选', 'icon' => 'filter']);
echo '</div>';

$rows = array_map(static fn (array $o): array => [
    '<a href="' . Cui::url('/orders/' . $o['no']) . '"><b>' . CuiHtml::e($o['no']) . '</b></a>',
    CuiHtml::e($o['buyer']),
    CuiHtml::e($o['amount']),
    CuiBadge::render($o['status'], $o['kind']),
    CuiHtml::e($o['seller']),
    CuiHtml::e($o['time']),
    '<span class="link">处理</span>',
], $orders);

echo CuiCard::render(CuiCard::header('最近订单', '按时间倒序') . CuiTable::render(['订单号', '买家', '金额', '状态', '卖家', '时间', '操作'], $rows, ['class' => 'orders-table']));
echo CuiCard::render('<div class="notice">' . Cui::icon('alert') . ' 8 个异常订单需要介入；2 个结算差异待复核。</div>');

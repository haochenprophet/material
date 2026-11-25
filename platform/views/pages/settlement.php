<?php
declare(strict_types=1);
$rows = DemoData::settlements();

echo CuiBreadcrumb::render([['label' => '卖家工作台', 'href' => '/seller'], ['label' => '结算/发票']]);
echo CuiPageHeader::render([
    'title' => '结算与发票',
    'subtitle' => 'T+7 结算；佣金/服务费/退款自动抵扣；差异挂起复核。',
    'actions' => [CuiButton::render(['label' => '申请开票', 'icon' => 'file', 'variant' => 'primary']), CuiButton::render(['label' => '导出对账单'])],
]);

echo '<div class="stats">' . CuiStat::render('¥36,200', '本期待结算') . CuiStat::render('¥28,960', '预计到账') . CuiStat::render('2', '差异待复核') . '</div>';

$tableRows = array_map(static fn (array $r): array => [
    CuiHtml::e($r['no']), CuiHtml::e($r['period']), CuiHtml::e($r['gross']), CuiHtml::e($r['commission']), '<b>' . CuiHtml::e($r['net']) . '</b>', CuiBadge::render($r['status'], $r['status'] === '已放款' ? 'ok' : 'warn'),
], $rows);
echo CuiCard::render(CuiCard::header('结算单', '按周期') . CuiTable::render(['结算单号', '周期', '成交额', '佣金/服务费', '应结', '状态'], $tableRows));
echo CuiCard::render('<div class="notice">' . Cui::icon('alert') . ' 一张结算单存在 2 笔订单退款差异，已挂起；复核后 T+1 放款。</div>');

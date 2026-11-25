<?php
declare(strict_types=1);
/** @var string $id */
$live = (new OrderStore())->get((string) ($id ?? ''));
$o = $live !== null ? (new OrderPresenter())->present($live) : DemoData::order((string) ($id ?? ''));
if ($o === null) {
    echo CuiEmpty::render('订单不存在', '请从订单列表进入。', CuiButton::render(['label' => '返回订单', 'href' => '/admin/orders']));
    return;
}

echo CuiBreadcrumb::render([['label' => '首页', 'href' => '/'], ['label' => '订单', 'href' => '/orders/SO-20261124-0081'], ['label' => $o['no']]]);
echo CuiPageHeader::render([
    'title' => '订单 ' . $o['no'],
    'subtitle' => $o['buyer'] . ' · ' . $o['seller'] . ' · ' . $o['time'],
    'meta' => CuiBadge::render($o['status'], $o['kind']),
    'actions' => [CuiButton::render(['label' => '联系客服', 'icon' => 'alert']), CuiButton::render(['label' => '申请售后', 'variant' => 'ghost'])],
]);

echo '<div class="order-grid">';
echo CuiCard::render(CuiCard::header('订单商品', '共 2 行') . CuiTable::render(['物料', '数量', '单价', '小计'], array_map(static fn ($l) => [CuiHtml::e($l[0]), CuiHtml::e($l[1]), CuiHtml::e($l[2]), CuiHtml::e($l[3])], $o['lines'])));
echo CuiCard::render(CuiCard::header('履约时间线', '') . '<ol class="timeline">' . implode('', array_map(static function (array $t): string {
    return '<li class="' . $t[2] . '"><b>' . CuiHtml::e($t[0]) . '</b><span>' . CuiHtml::e($t[1]) . '</span></li>';
}, $o['timeline'])) . '</ol>');
echo '</div>';

echo CuiCard::render('<div class="pay-summary"><div class="total"><span>应付</span><b>' . CuiHtml::e($o['amount']) . '</b></div><div class="actions">' . CuiButton::render(['label' => '查看发票', 'icon' => 'file']) . CuiButton::render(['label' => '确认收货', 'variant' => 'primary', 'data' => ['action' => 'toast', 'toast' => '演示：确认收货']]) . '</div></div>');

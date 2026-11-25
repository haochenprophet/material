<?php
declare(strict_types=1);
/** @var string $id */
$q = DemoData::quote();

echo CuiBreadcrumb::render([['label' => '首页', 'href' => '/'], ['label' => 'BOM 配单', 'href' => '/bom'], ['label' => '报价单']]);
echo CuiPageHeader::render([
    'title' => '报价单 ' . $q['id'],
    'subtitle' => $q['bom'] . ' · 匹配 ' . $q['matched'] . ' · 有效期 ' . $q['valid'],
    'actions' => [
        CuiButton::render(['label' => '导出 PDF', 'icon' => 'file']),
        CuiButton::render(['label' => '去下单', 'variant' => 'primary', 'href' => '/orders/SO-20261124-0081']),
    ],
]);

echo CuiCard::render('<div class="stats">' . CuiStat::render($q['matched'], '可报价行') . CuiStat::render($q['lead'], '预计交期') . CuiStat::render($q['total'], '预计总价') . '</div>');
echo CuiCard::render(CuiCard::header('报价明细', '含检测/履约/运费') . CuiTable::render(
    ['物料', '数量', '单价', '小计', '状态'],
    array_map(static fn (array $l): array => [
        CuiHtml::e($l[0]), CuiHtml::e($l[1]), CuiHtml::e($l[2]), CuiHtml::e($l[3]),
        CuiBadge::render((string) $l[4], $l[4] === '缺料' ? 'fail' : ($l[4] === '替代' ? 'warn' : 'ok')),
    ], $q['lines'])
));
echo CuiCard::render('<div class="quote-summary"><div><span>商品</span><b>¥5,476</b></div><div><span>检测/履约</span><b>¥860</b></div><div><span>运费</span><b>¥120</b></div><div><span>服务费</span><b>¥218</b></div><div class="total"><span>合计</span><b>¥6,674</b></div></div><div class="notice">' . CuiBadge::render('含税', 'blue') . ' 报价含 13% 增值税；实际以结算单为准。缺料行可选择替代或预售。</div>');

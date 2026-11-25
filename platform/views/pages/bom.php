<?php
declare(strict_types=1);
$lines = DemoData::bomLines();
$matches = DemoData::matches();
$uploadOk = Cui::flash('bom_result');
$uploadError = Cui::flash('bom_error');

echo CuiBreadcrumb::render([['label' => '首页', 'href' => '/'], ['label' => 'BOM 配单']]);
if ($uploadOk !== null) {
    echo '<div class="alert alert-ok">' . CuiHtml::e($uploadOk) . '</div>';
}
if ($uploadError !== null) {
    echo '<div class="alert alert-error">' . CuiHtml::e($uploadError) . '</div>';
}
echo CuiPageHeader::render([
    'title' => 'BOM 配单',
    'subtitle' => '上传 BOM → 自动匹配 → 人工复核 → 生成报价；缺料给替代。',
    'actions' => [
        CuiButton::render(['label' => '下载模板', 'icon' => 'file']),
        CuiButton::render(['label' => '帮助', 'variant' => 'ghost']),
    ],
]);

echo '<div class="bom-layout">';
echo '<div class="bom-left">';
echo CuiCard::render(CuiFileDrop::render(['action' => '/bom/upload', 'label' => '拖入 BOM 文件，或点击上传', 'hint' => '支持 .csv / .txt，≤1MB、≤500 行；上传后自动解析'])
    . '<div class="file"><span>' . Cui::icon('file') . '智能网关-v3.xlsx</span>' . CuiBadge::render('示例文件', 'ok') . '</div>');
echo CuiCard::render('<div class="stats">' . CuiStat::render('112/128', '已匹配') . CuiStat::render('7 天', '预计交期') . CuiStat::render('¥86,400', '预计总价') . '</div>');
echo CuiCard::render(CuiCard::header('BOM 行', '', '<span class="link">仅看缺料</span>') . implode('', array_map(static function (array $l): string {
    $cls = 'bom-line' . (!empty($l['active']) ? ' active' : '');
    return '<div class="' . $cls . '"><span class="no">' . (int) $l['no'] . '</span><div class="grow"><b>' . CuiHtml::e($l['mpn']) . '</b><small>' . CuiHtml::e($l['desc']) . '</small></div>' . CuiBadge::render($l['status'], $l['kind']) . '</div>';
}, $lines)));
echo '</div>';

echo '<div class="bom-right">';
echo CuiCard::render(CuiCard::header('第 1 行匹配结果', '按总成本排序') . implode('', array_map(static fn (array $m): string => CuiMatchCard::render($m), $matches)) . CuiButton::render(['label' => '生成报价单', 'variant' => 'primary', 'block' => true, 'data' => ['action' => 'toast', 'toast' => '已生成报价单草稿']]) . '<p class="hint">报价含检测/履约/运费；有效期 24 小时。</p>', ['class' => 'sticky-panel']);
echo '</div></div>';

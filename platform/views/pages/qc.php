<?php
declare(strict_types=1);
/** @var string $id */
$qc = DemoData::qc();

echo CuiBreadcrumb::render([['label' => '质检工作台', 'href' => '/qc/QC-20261124-018'], ['label' => $qc['id']]]);
echo CuiPageHeader::render([
    'title' => $qc['id'],
    'subtitle' => $qc['material'] . ' · 批号 ' . $qc['batch'] . ' · 数量 ' . $qc['qty'],
    'meta' => CuiBadge::render($qc['due'], 'warn'),
    'actions' => [CuiButton::render(['label' => '查看 SOP', 'icon' => 'file']), CuiButton::render(['label' => '转外协', 'variant' => 'ghost'])],
]);

echo '<div class="qc-grid">';
echo CuiCard::render(CuiCard::header('检测项', '按 SOP 逐项') . implode('', array_map(static fn (array $i): string => CuiQcItem::render($i[0], $i[1], $i[2]), $qc['items'])) . CuiUploader::render(['label' => '检测照片', 'capture' => true, 'multiple' => true, 'count' => 3]) . '<div class="field"><label>综合分级</label>' . CuiChips::render([['label' => 'A 级'], ['label' => 'B 级', 'active' => true], ['label' => 'C 级'], ['label' => 'D 级']]) . '</div>' . CuiField::render(['label' => '备注（买家可见）', 'name' => 'remark', 'type' => 'textarea', 'value' => '丝印不一致，建议降级为 B；已拍照留证。', 'rows' => 2]));
echo CuiCard::render(CuiCard::header('报告预览', '自动生成') . '<div class="report-preview"><b>质检报告 · ' . CuiHtml::e($qc['id']) . '</b><p>物料：' . CuiHtml::e($qc['material']) . '<br>批次：' . CuiHtml::e($qc['batch']) . ' · 数量 ' . CuiHtml::e($qc['qty']) . '<br>结果：<strong>B 级 / 条件通过</strong><br>外观：通过 · 丝印：不通过<br>建议：降级销售，详情页标注“丝印模糊”<br>检测人：王工 · 2026-11-24 18:20</p></div>' . CuiButton::render(['label' => '提交报告', 'variant' => 'primary', 'block' => true, 'data' => ['action' => 'toast', 'toast' => '报告已提交，等待复核']]), ['class' => 'sticky-panel']);
echo '</div>';

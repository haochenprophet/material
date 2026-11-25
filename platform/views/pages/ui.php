<?php
declare(strict_types=1);
echo CuiPageHeader::render([
    'title' => 'UI 组件库',
    'subtitle' => '所有页面由这些 PHP UI class 输出；改组件即改全站。',
    'actions' => [CuiButton::render(['label' => '查看版本/回溯', 'href' => '/trace'])],
]);

echo '<div class="ui-section">';
echo CuiCard::render(CuiCard::header('按钮 Button', '主/次/幽灵/危险/小/块级') .
    '<div class="row wrap">' . CuiButton::render(['label' => '主按钮', 'variant' => 'primary']) . CuiButton::render(['label' => '次按钮']) . CuiButton::render(['label' => '幽灵', 'variant' => 'ghost']) . CuiButton::render(['label' => '危险', 'variant' => 'danger']) . CuiButton::render(['label' => '小按钮', 'size' => 'sm']) . CuiButton::render(['label' => '带图标', 'icon' => 'upload']) . '</div>');
echo CuiCard::render(CuiCard::header('徽章 / 标签 / 状态', '') . '<div class="row wrap">' . CuiBadge::render('A 级质检', 'ok') . CuiBadge::render('待审核', 'warn') . CuiBadge::render('缺料', 'fail') . CuiBadge::render('寄售', 'blue') . CuiBadge::render('普通', '') . '</div>');
echo CuiCard::render(CuiCard::header('表单字段', '输入/下拉/文本域/错误') . '<div class="form2">' . CuiField::render(['label' => '型号', 'name' => 'demo_mpn', 'value' => 'TE 170364-1', 'required' => true]) . CuiField::render(['label' => '类目', 'name' => 'demo_cat', 'type' => 'select', 'value' => 'connector', 'options' => ['connector' => '连接器', 'passive' => '被动件']]) . '</div>' . CuiField::render(['label' => '错误示例', 'name' => 'demo_err', 'value' => '无效值', 'error' => '该料号已存在，请合并或改码。']) . CuiField::render(['label' => '备注', 'name' => 'demo_note', 'type' => 'textarea', 'value' => '可多行输入。']));
echo CuiCard::render(CuiCard::header('表格', '窄屏自动变卡片') . CuiTable::render(['物料', '数量', '状态'], [['TE 170364-1', '200', CuiBadge::render('可报价', 'ok')], ['STM32F103C8T6', '120', CuiBadge::render('替代', 'warn')], ['ADUM1201ARZ', '80', CuiBadge::render('缺料', 'fail')]]));
echo CuiCard::render(CuiCard::header('空状态 / 上传 / 步骤条', '') . CuiEmpty::render('没有找到匹配物料', '换个型号，或上传 BOM。', CuiButton::render(['label' => '上传 BOM', 'href' => '/bom'])) . CuiUploader::render(['label' => '图片上传', 'count' => 2, 'multiple' => true]) . CuiStepper::render([['label' => '基本信息', 'done' => true], ['label' => '规格', 'current' => true], ['label' => '价格']]));
echo CuiCard::render(CuiCard::header('商品卡 / 匹配卡 / 质检项 / KPI', '') . '<div class="grid3"><div class="product-grid" style="grid-template-columns:1fr">' . CuiProductCard::render(DemoData::products()[0]) . '</div><div>' . CuiMatchCard::render(DemoData::matches()[0]) . CuiQcItem::render('外观 / 引脚', 'pass') . '</div><div class="kpis" style="grid-template-columns:1fr 1fr">' . CuiKpi::render('¥128,640', '今日 GMV', '+12.4%') . CuiKpi::render('42', '待审核', '8 超时') . '</div></div>');
echo '</div>';

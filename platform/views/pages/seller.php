<?php
declare(strict_types=1);
$steps = [
    ['label' => '基本信息', 'done' => true],
    ['label' => '规格', 'done' => true],
    ['label' => '物理', 'current' => true],
    ['label' => '媒体'],
    ['label' => '价格'],
];
$fields = '<div class="form2">'
    . CuiField::render(['label' => '类目', 'name' => 'category', 'type' => 'select', 'value' => 'connector', 'options' => ['connector' => '连接器', 'passive' => '被动件', 'ic' => '集成电路']])
    . CuiField::render(['label' => '品牌', 'name' => 'brand', 'type' => 'select', 'value' => 'te', 'options' => ['te' => 'TE Connectivity', 'molex' => 'Molex', 'other' => '其他']])
    . CuiField::render(['label' => '型号 / MPN', 'name' => 'part_number', 'value' => '170364-1', 'required' => true])
    . CuiField::render(['label' => '成色', 'name' => 'condition', 'type' => 'select', 'value' => 'surplus', 'options' => ['new' => '全新', 'surplus' => '散新/工程余料', 'refurb' => '翻新', 'used' => '二手']])
    . '</div>'
    . CuiField::render(['label' => '标题', 'name' => 'title', 'value' => 'TE 170364-1 连接器 4Pin 2.54mm 直插', 'required' => true])
    . '<div class="form2">'
    . CuiField::render(['label' => '数量', 'name' => 'qty', 'value' => '1240'])
    . CuiField::render(['label' => '位置', 'name' => 'location', 'type' => 'select', 'value' => 'sz', 'options' => ['sz' => '深圳 · 华强北仓', 'su' => '苏州 · 园区仓']])
    . '</div>'
    . CuiField::render(['label' => '规格 / 电气', 'name' => 'spec', 'value' => '4Pin · 2.54mm · 3A · 250V · -40~105°C', 'help' => '自动带入类目属性模板；可修改。'])
    . CuiField::render(['label' => '尺寸 / 重量', 'name' => 'size', 'value' => '12 × 5 × 8 mm · 2.1g / 个'])
    . CuiField::render(['label' => '售价（元/个）', 'name' => 'price', 'value' => '18.50', 'required' => true]);

$sellerOk = Cui::flash('seller_result');
$sellerErr = Cui::flash('seller_error');

echo CuiBreadcrumb::render([['label' => '卖家工作台', 'href' => '/seller'], ['label' => '提报物料']]);
echo CuiPageHeader::render([
    'title' => '提报物料',
    'subtitle' => '草稿自动保存 · 提交后 4 小时内审核 · 质检 72 小时',
    'actions' => [CuiButton::render(['label' => '预览', 'icon' => 'search'])],
]);

echo CuiCard::render(CuiStepper::render($steps));
if ($sellerOk !== null) {
    echo '<div class="alert alert-ok">' . CuiHtml::e($sellerOk) . '</div>';
}
if ($sellerErr !== null) {
    echo '<div class="alert alert-error">' . CuiHtml::e($sellerErr) . '</div>';
}
echo '<form method="post" action="' . Cui::url('/seller/listings') . '" enctype="multipart/form-data">';
echo Cui::csrfField();
echo '<input type="hidden" name="idempotency_key" value="' . CuiHtml::e(bin2hex(random_bytes(16))) . '">';
echo '<div class="wizard-grid">';
echo CuiCard::render($fields . CuiField::render(['label' => '实物图片 / 视频 / 手册', 'name' => 'media_note', 'type' => 'textarea', 'value' => '已传 1 张主图；建议 4–8 张。', 'rows' => 2]) . CuiUploader::render(['label' => '上传媒体', 'capture' => true, 'multiple' => true, 'count' => 1]) . CuiCard::header('阶梯价', '按数量自动生效') . '<div class="tier-editor"><div class="tier-row"><span>1–9</span><input class="input" value="18.50"><span>元/个</span></div><div class="tier-row"><span>10–99</span><input class="input" value="17.20"><span>元/个</span></div><div class="tier-row"><span>100+</span><input class="input" value="15.80"><span>元/个</span></div></div>');
echo '<div class="wizard-preview">' . CuiCard::render(CuiCard::header('买家看到的样子', '实时预览') . CuiProductCard::render(['id' => 'p1', 'title' => 'TE 170364-1 连接器', 'spec' => '4Pin · 2.54mm · 直插', 'price' => '¥18.50', 'unit' => '个', 'stock' => '库存 1,240', 'location' => '深圳', 'badge' => 'A 级质检', 'badgeKind' => 'ok', 'thumb' => 'TE 170364-1']));
echo '</div></div>';
echo '<div class="actionbar">' . CuiButton::render(['label' => '保存草稿']) . CuiButton::render(['label' => '提交审核', 'variant' => 'primary', 'type' => 'submit']) . '</div>';
echo '</form>';

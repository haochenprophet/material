<?php
declare(strict_types=1);
$materials = [];
$images = [];
$selected = 0;
$ok = Cui::flash('image_result');
$err = Cui::flash('image_error');
if (Database::configured()) {
    $pdo = Database::pdo();
    $repo = new PdoMaterialImageRepository($pdo);
    $storeId = MerchantContext::storeId();
    $materials = $repo->materials(MerchantContext::isAdmin() ? null : $storeId);
    $selected = (int) ($_GET['material_id'] ?? ($materials[0]['id'] ?? 0));
    if ($selected > 0) {
        $images = $repo->forMaterial($selected);
    }
}
echo CuiBreadcrumb::render([['label' => '卖家工作台', 'href' => '/seller'], ['label' => '物料图片']]);
echo CuiPageHeader::render(['title' => '物料图片', 'subtitle' => '主图/排序/软删；上传仅支持 JPG/PNG/WebP ≤5MB。']);
if ($ok !== null) { echo '<div class="alert alert-ok">' . CuiHtml::e($ok) . '</div>'; }
if ($err !== null) { echo '<div class="alert alert-error">' . CuiHtml::e($err) . '</div>'; }
if ($materials === []) {
    echo CuiEmpty::render('暂无物料', '先提报物料。');
    return;
}
$opts = [];
foreach ($materials as $m) {
    $opts[(int) $m['id']] = $m['part_number'] . ' · ' . $m['name'];
}
echo '<form method="get" action="' . Cui::url('/seller/images') . '" class="row" style="margin-bottom:12px">' . CuiField::render(['label' => '选择物料', 'name' => 'material_id', 'type' => 'select', 'value' => (string) $selected, 'options' => $opts]) . CuiButton::render(['label' => '查看', 'type' => 'submit']) . '</form>';
echo '<form method="post" action="' . Cui::url('/seller/images/' . $selected . '/upload') . '" enctype="multipart/form-data">' . Cui::csrfField();
echo CuiCard::render(CuiCard::header('上传图片', '当前物料 #' . $selected) . '<input class="input" type="file" name="image" accept="image/jpeg,image/png,image/webp" required><label class="small"><input type="checkbox" name="is_primary" value="1"> 设为主图</label>' . CuiButton::render(['label' => '上传', 'variant' => 'primary', 'type' => 'submit']));
echo '</form>';

$rows = [];
foreach ($images as $img) {
    $isPrimary = (int) $img['is_primary'] === 1;
    $rows[] = [
        '<img src="' . CuiHtml::e((string) $img['path']) . '" alt="" style="width:88px;height:64px;object-fit:cover;border-radius:8px">',
        CuiHtml::e((string) $img['path']),
        CuiHtml::e((string) ($img['mime'] ?? '')),
        number_format((int) $img['size'] / 1024, 1) . ' KB',
        $isPrimary ? '<span class="badge badge-ok">主图</span>' : '—',
        '<form method="post" action="' . Cui::url('/seller/images/' . $selected . '/' . (int) $img['id'] . '/primary') . '" style="display:inline">' . Cui::csrfField() . '<button class="btn btn-sm" type="submit">设主图</button></form> '
        . '<form method="post" action="' . Cui::url('/seller/images/' . $selected . '/' . (int) $img['id'] . '/delete') . '" style="display:inline">' . Cui::csrfField() . '<button class="btn btn-sm" type="submit">软删</button></form>',
    ];
}
echo CuiCard::render(CuiCard::header('图片列表', count($images) . ' 张') . ($images === [] ? '<div class="notice">暂无图片。</div>' : CuiTable::render(['预览', '路径', '类型', '大小', '主图', '操作'], $rows)));
echo '<p class="small muted">软删只标记 deleted_at，不删除文件；主图被删后自动选择剩余图片。</p>';

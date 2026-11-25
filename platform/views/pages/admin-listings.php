<?php
declare(strict_types=1);
$pending = [];
$dbReady = Database::configured();
if ($dbReady) {
    $pending = (new PdoListingReview(Database::pdo()))->pending();
}
$ok = Cui::flash('review_result');
$err = Cui::flash('review_error');

echo CuiBreadcrumb::render([['label' => '运营后台', 'href' => '/admin/orders'], ['label' => '提报审核']]);
echo CuiPageHeader::render([
    'title' => '提报审核',
    'subtitle' => 'pending_review → listed；上架后才会出现在公开目录。',
    'actions' => [CuiButton::render(['label' => '订单看板', 'href' => '/admin/orders'])],
]);
if ($ok !== null) {
    echo '<div class="alert alert-ok">' . CuiHtml::e($ok) . '</div>';
}
if ($err !== null) {
    echo '<div class="alert alert-error">' . CuiHtml::e($err) . '</div>';
}
if (!$dbReady) {
    echo CuiCard::render('<div class="notice">' . Cui::icon('alert') . ' 演示模式未配置数据库；审核需要 DB_USER。提报页仍可填写，但不会落库。</div>');
} elseif ($pending === []) {
    echo CuiEmpty::render('没有待审提报', '卖家提交后会出现在这里。', CuiButton::render(['label' => '去卖家提报', 'href' => '/seller/listings/new']));
} else {
    $rows = [];
    foreach ($pending as $p) {
        $form = '<form method="post" action="' . Cui::url('/admin/listings/' . (int) $p['id'] . '/approve') . '" style="display:inline">'
            . Cui::csrfField()
            . CuiButton::render(['label' => '通过上架', 'variant' => 'primary', 'size' => 'sm', 'type' => 'submit'])
            . '</form> '
            . '<form method="post" action="' . Cui::url('/admin/listings/' . (int) $p['id'] . '/reject') . '" style="display:inline-flex;gap:4px">'
            . Cui::csrfField()
            . '<input class="input" name="reason" placeholder="驳回原因" style="min-height:30px;width:110px;font-size:10px">'
            . CuiButton::render(['label' => '驳回', 'size' => 'sm', 'type' => 'submit'])
            . '</form>';
        $rows[] = [
            '#' . (int) $p['id'],
            CuiHtml::e((string) $p['title']),
            CuiHtml::e((string) $p['part_number']),
            '¥' . number_format((float) $p['price'], 2),
            number_format((float) $p['quantity'], 0),
            $form,
        ];
    }
    echo CuiCard::render(CuiCard::header('待审 ' . count($pending) . ' 条', '上架前不会进入首页') . CuiTable::render(['ID', '标题', 'MPN', '价格', '数量', '操作'], $rows));
}

echo CuiCard::render('<div class="notice">' . Cui::icon('alert') . ' 审核只改 listing.status；不删除、不覆盖原始提报。上架后公开目录只读 status=listed。</div>');

<?php
declare(strict_types=1);
$rows = Database::configured() ? (new PdoMerchantKycRepository(Database::pdo()))->all() : [];
$ok = Cui::flash('kyc_result');
$err = Cui::flash('kyc_error');
echo CuiBreadcrumb::render([['label' => '运营后台', 'href' => '/admin/orders'], ['label' => '商家认证']]);
echo CuiPageHeader::render(['title' => '商家认证 / KYC', 'subtitle' => '审核资质、保证金与协议；不删除商家资料。']);
if ($ok !== null) { echo '<div class="alert alert-ok">' . CuiHtml::e($ok) . '</div>'; }
if ($err !== null) { echo '<div class="alert alert-error">' . CuiHtml::e($err) . '</div>'; }
if ($rows === []) {
    echo CuiEmpty::render('暂无认证资料', '商家提交后会出现在这里。');
} else {
    $table = [];
    foreach ($rows as $r) {
        $form = '';
        if (in_array((string) $r['status'], ['pending', 'rejected'], true)) {
            $form = '<form method="post" action="' . Cui::url('/admin/kyc/' . (int) $r['id'] . '/review') . '" style="display:inline-flex;gap:4px">' . Cui::csrfField()
                . '<input class="input" name="note" placeholder="审核备注" style="min-height:30px;width:110px;font-size:10px">'
                . '<button class="btn btn-sm" name="status" value="verified" type="submit">认证</button>'
                . '<button class="btn btn-sm" name="status" value="rejected" type="submit">驳回</button></form>';
        }
        $table[] = [
            '#' . (int) $r['id'],
            CuiHtml::e((string) $r['store_name']),
            CuiHtml::e((string) $r['legal_name']),
            CuiHtml::e((string) $r['license_no']),
            '¥' . number_format((float) $r['deposit_amount'], 2),
            CuiHtml::e((string) $r['agreement_version']),
            '<span class="badge badge-' . ($r['status'] === 'verified' ? 'ok' : ($r['status'] === 'rejected' ? 'danger' : 'warn')) . '">' . CuiHtml::e((string) $r['status']) . '</span>',
            $form,
        ];
    }
    echo CuiCard::render(CuiCard::header('认证申请', count($rows) . ' 条') . CuiTable::render(['ID', '店铺', '企业', '证件号', '保证金', '协议', '状态', '操作'], $table));
}
echo '<p class="small muted">KYC 数据只用于审核；通过后可作为商家信用/入驻依据。</p>';

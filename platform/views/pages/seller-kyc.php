<?php
declare(strict_types=1);
$store = null;
$kyc = null;
if (Database::configured()) {
    $storeId = MerchantContext::storeId();
    if ($storeId !== null) {
        $store = (new PdoStoreRepository(Database::pdo()))->findById($storeId);
        if ($store !== null) {
            $kyc = (new PdoMerchantKycRepository(Database::pdo()))->find($storeId);
        }
    }
}
$ok = Cui::flash('kyc_result');
$err = Cui::flash('kyc_error');
echo CuiBreadcrumb::render([['label' => '卖家工作台', 'href' => '/seller'], ['label' => '商家认证']]);
echo CuiPageHeader::render(['title' => '商家认证 / KYC', 'subtitle' => '资质、保证金、协议与认证状态；资料仅供运营审核。']);
if ($ok !== null) {
    echo '<div class="alert alert-ok">' . CuiHtml::e($ok) . '</div>';
}
if ($err !== null) {
    echo '<div class="alert alert-error">' . CuiHtml::e($err) . '</div>';
}
if ($store === null) {
    echo CuiEmpty::render('暂无商家', '需要先生成商家店铺。');
    return;
}
$status = (string) ($kyc['status'] ?? 'unsubmitted');
$kind = $status === 'verified' ? 'ok' : ($status === 'rejected' ? 'danger' : 'warn');
echo CuiCard::render(CuiCard::header('认证状态', '') . '<div class="kyc-status"><span class="badge badge-' . $kind . '">' . CuiHtml::e($status) . '</span><span>' . CuiHtml::e((string) $store['name']) . '</span></div>'
    . ($kyc !== null ? '<div class="kv"><b>企业名称</b><span>' . CuiHtml::e((string) $kyc['legal_name']) . '</span><b>证件号</b><span>' . CuiHtml::e((string) $kyc['license_no']) . '</span><b>保证金</b><span>¥' . number_format((float) $kyc['deposit_amount'], 2) . '</span><b>协议</b><span>' . CuiHtml::e((string) $kyc['agreement_version']) . '</span><b>备注</b><span>' . CuiHtml::e((string) ($kyc['review_note'] ?? '—')) . '</span></div>' : '<p class="muted">尚未提交认证资料。</p>'));

echo '<form method="post" action="' . Cui::url('/seller/kyc') . '">' . Cui::csrfField() . '<input type="hidden" name="store_id" value="' . (int) $store['id'] . '">';
echo CuiCard::render(CuiCard::header('提交 / 更新资料', '运营审核') . '<div class="form2">'
    . CuiField::render(['label' => '企业名称', 'name' => 'legal_name', 'value' => (string) ($kyc['legal_name'] ?? $store['name']), 'required' => true])
    . CuiField::render(['label' => '统一社会信用代码', 'name' => 'license_no', 'value' => (string) ($kyc['license_no'] ?? ''), 'required' => true])
    . '</div><div class="form2">'
    . CuiField::render(['label' => '税号', 'name' => 'tax_no', 'value' => (string) ($kyc['tax_no'] ?? '')])
    . CuiField::render(['label' => '法人', 'name' => 'legal_person', 'value' => (string) ($kyc['legal_person'] ?? '')])
    . '</div><div class="form2">'
    . CuiField::render(['label' => '联系人', 'name' => 'contact_name', 'value' => (string) ($kyc['contact_name'] ?? '')])
    . CuiField::render(['label' => '联系电话', 'name' => 'contact_phone', 'value' => (string) ($kyc['contact_phone'] ?? '')])
    . '</div><div class="form2">'
    . CuiField::render(['label' => '保证金（元）', 'name' => 'deposit_amount', 'value' => (string) ($kyc['deposit_amount'] ?? '0'), 'type' => 'number'])
    . CuiField::render(['label' => '协议版本', 'name' => 'agreement_version', 'value' => (string) ($kyc['agreement_version'] ?? 'v1')])
    . '</div><label class="small"><input type="checkbox" name="agreement" value="1" checked> 已阅读并同意《商家服务协议》《质量与售后协议》</label>'
    . CuiButton::render(['label' => '提交认证', 'variant' => 'primary', 'type' => 'submit'])) . '</form>';
echo '<p class="small muted">证件影像/资质文件的真实上传在 Q06 图片上传任务接入；当前为占位资料。演示卖家默认展示第一个 active 店铺。</p>';

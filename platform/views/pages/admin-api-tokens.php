<?php
declare(strict_types=1);
$tokens = Database::configured() ? (new ApiTokenRepository(Database::pdo()))->all() : [];
$plain = Cui::flash('api_token_plain');
$ok = Cui::flash('api_result');
$err = Cui::flash('api_error');
echo CuiBreadcrumb::render([['label' => '运营后台', 'href' => '/admin/orders'], ['label' => 'API Token']]);
echo CuiPageHeader::render(['title' => 'API Token / 限流', 'subtitle' => 'scope、每分钟额度、吊销；明文仅创建时显示一次。']);
if ($plain !== null) { echo '<div class="alert alert-ok"><b>新 token（只显示一次）：</b><code>' . CuiHtml::e($plain) . '</code></div>'; }
if ($ok !== null) { echo '<div class="alert alert-ok">' . CuiHtml::e($ok) . '</div>'; }
if ($err !== null) { echo '<div class="alert alert-error">' . CuiHtml::e($err) . '</div>'; }
echo '<div class="callout warn"><b>默认关闭：</b>本地演示不强制 token；设置 <code>API_REQUIRE_TOKEN=1</code> 后除 <code>/api/v1/health</code> 外均要求 Bearer token。</div>';

echo '<form method="post" action="' . Cui::url('/admin/api-tokens/create') . '">' . Cui::csrfField();
echo CuiCard::render(CuiCard::header('创建 token', 'scope + 额度') . '<div class="form2">'
    . CuiField::render(['label' => '客户端名称', 'name' => 'client_name', 'value' => 'demo-client'])
    . CuiField::render(['label' => '每分钟额度', 'name' => 'rate_limit', 'value' => '60'])
    . '</div><div class="row"><label><input type="checkbox" name="scopes[]" value="read" checked> read</label><label><input type="checkbox" name="scopes[]" value="write"> write</label><label><input type="checkbox" name="scopes[]" value="admin"> admin</label></div>'
    . CuiButton::render(['label' => '创建', 'variant' => 'primary', 'type' => 'submit']));
echo '</form>';

$rows = [];
foreach ($tokens as $t) {
    $rows[] = [
        '#' . (int) $t['id'],
        CuiHtml::e((string) $t['client_name']),
        CuiHtml::e(implode(',', (array) (json_decode((string) $t['scopes_json'], true) ?: []))),
        (int) $t['rate_limit'] . ' / 分',
        '<span class="badge badge-' . ($t['status'] === 'active' ? 'ok' : '') . '">' . CuiHtml::e((string) $t['status']) . '</span>',
        CuiHtml::e((string) ($t['last_used_at'] ?? '—')),
        $t['status'] === 'active' ? '<form method="post" action="' . Cui::url('/admin/api-tokens/' . (int) $t['id'] . '/revoke') . '" style="display:inline">' . Cui::csrfField() . '<button class="btn btn-sm" type="submit">吊销</button></form>' : '—',
    ];
}
echo CuiCard::render(CuiCard::header('Token', count($tokens) . ' 个') . ($tokens === [] ? '<div class="notice">暂无 token。</div>' : CuiTable::render(['ID', '客户端', 'scope', '额度', '状态', '最后使用', '操作'], $rows)));
echo '<p class="small muted">token 只存 SHA-256；吊销后立即失效。计数固定窗口 60 秒，生产建议 Redis 滑动窗口。</p>';

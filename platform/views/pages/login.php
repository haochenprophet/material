<?php
declare(strict_types=1);
$flash = Cui::flash('login');
$error = Cui::flash('login_error');
$user = Auth::user();
$authSource = Auth::source();
$next = (string) ($_GET['next'] ?? '/');
if (!str_starts_with($next, '/') || str_starts_with($next, '//')) {
    $next = '/';
}

echo '<div class="auth-card">';
echo '<div class="auth-brand"><em></em>' . CuiHtml::e(APP_NAME) . '</div>';
echo '<h1>登录</h1>';
if ($user !== null) {
    echo '<p class="muted">已登录：' . CuiHtml::e($user['name']) . ' · ' . CuiHtml::e(Rbac::label($user['role'])) . '</p>';
    echo '<div class="row" style="margin:12px 0">' . CuiButton::render(['label' => '进入平台', 'variant' => 'primary', 'href' => '/']) . CuiButton::render(['label' => '退出', 'href' => '/logout']) . '</div>';
    echo '<p class="auth-links"><a href="' . Cui::url('/trace') . '">版本/回溯</a></p>';
    echo '</div>';
    return;
}

echo '<p class="muted">' . ($authSource === 'db' ? 'DB 账号模式：使用 user/org_member/role 表；密码 demo123。' : '演示账号：buyer@demo / seller@demo 等，密码 demo123；账号库/短信登录未接入。') . '</p>';
if ($flash !== null) {
    echo '<div class="alert alert-ok">' . CuiHtml::e($flash) . '</div>';
}
if ($error !== null) {
    echo '<div class="alert alert-error">' . CuiHtml::e($error) . '</div>';
}
echo '<form method="post" action="' . Cui::url('/login') . '">';
echo Cui::csrfField();
echo '<input type="hidden" name="next" value="' . CuiHtml::e($next) . '">';
echo CuiField::render(['label' => '邮箱 / 手机号', 'name' => 'account', 'type' => 'text', 'placeholder' => 'admin@demo', 'required' => true]);
echo CuiField::render(['label' => '密码', 'name' => 'password', 'type' => 'password', 'placeholder' => 'demo123', 'required' => true]);
echo CuiButton::render(['label' => '登录', 'variant' => 'primary', 'block' => true, 'type' => 'submit']);
echo '</form>';

echo '<div class="auth-demo"><b>' . ($authSource === 'db' ? 'DB 测试账号' : '演示角色') . '</b>';
if ($authSource === 'db') {
    echo '<div class="auth-demo-row"><span>dbadmin@demo</span><em>管理员</em></div>';
    echo '<div class="auth-demo-hint">由 bin/seed-auth.php 写入 mats_platform_next2；仅本地测试。</div>';
} else {
    foreach (Auth::demoAccounts() as $account => $u) {
        echo '<div class="auth-demo-row"><span>' . CuiHtml::e($account) . '</span><em>' . CuiHtml::e(Rbac::label($u['role'])) . '</em></div>';
    }
    echo '<div class="auth-demo-hint">密码统一 demo123；仅本地演示，生产必须接账号库 + 限流 + MFA。</div>';
}
echo '</div>';
echo '<p class="auth-links"><a href="' . Cui::url('/') . '">先逛逛</a> · <a href="' . Cui::url('/ui') . '">UI 组件库</a></p>';
echo '</div>';

<?php
declare(strict_types=1);

/**
 * 前端安全基线测试（XSS/CSRF/CSP/路由保护）。
 * 用法：php platform/tests/security.php
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    echo 'CLI only';
    exit(1);
}

require dirname(__DIR__) . '/app/bootstrap.php';

ob_start();
$pass = 0;
$fail = 0;
function check(string $name, bool $cond, string $detail = ''): void
{
    global $pass, $fail;
    if ($cond) {
        $pass++;
        echo "  PASS  {$name}" . PHP_EOL;
    } else {
        $fail++;
        echo "  FAIL  {$name}" . ($detail !== '' ? "  ({$detail})" : '') . PHP_EOL;
    }
}

echo '物格云 security tests · v' . APP_VERSION . PHP_EOL . PHP_EOL;
putenv('AUTH_SOURCE=demo');
check('XSS 转义', !str_contains(CuiHtml::e('<script>alert(1)</script>'), '<script>'));
check('CSRF 长度', strlen(Cui::csrf()) >= 32);
$bootstrap = (string) file_get_contents(PLATFORM_PATH . '/app/bootstrap.php');
check('CSP default-src self', str_contains($bootstrap, "default-src 'self'"));
check('CSP script-src self', str_contains($bootstrap, "script-src 'self'"));
check('CSP frame-ancestors', str_contains($bootstrap, "frame-ancestors 'self'"));
check('X-Frame-Options', str_contains($bootstrap, 'X-Frame-Options: SAMEORIGIN'));
check('Permissions-Policy', str_contains($bootstrap, 'Permissions-Policy:'));
check('HSTS 仅 HTTPS', str_contains($bootstrap, 'Strict-Transport-Security') && str_contains($bootstrap, "HTTPS"));
check('数据库密码不硬编码', !str_contains((string) file_get_contents(PLATFORM_PATH . '/config/database.php'), 'patent'));

Auth::logout();
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['REQUEST_URI'] = '/admin/orders';
$_GET = [];
http_response_code(200);
ob_start();
CuiRouter::dispatch();
$out = (string) ob_get_clean();
check('未登录访问后台 302', http_response_code() === 302, (string) http_response_code());

Auth::attempt('seller@demo', 'demo123');
http_response_code(200);
ob_start();
CuiRouter::dispatch();
$out = (string) ob_get_clean();
check('seller 访问后台 403', http_response_code() === 403, (string) http_response_code());

$login = '';
Auth::logout();
$_SERVER['REQUEST_URI'] = '/login';
http_response_code(200);
ob_start();
CuiRouter::dispatch();
$login = (string) ob_get_clean();
check('登录页有 CSRF', str_contains($login, 'name="_csrf"'));

echo PHP_EOL . '结果：PASS ' . $pass . ' / FAIL ' . $fail . PHP_EOL;
$out = (string) ob_get_clean();
echo $out;
exit($fail > 0 ? 1 : 0);

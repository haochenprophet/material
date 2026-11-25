<?php
declare(strict_types=1);

/**
 * 基础无障碍检查（服务端渲染 HTML，不依赖 axe）。
 * 用法：php platform/tests/a11y.php
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    echo 'CLI only';
    exit(1);
}

require dirname(__DIR__) . '/app/bootstrap.php';

$pass = 0;
$fail = 0;
function check(string $name, bool $cond, string $detail = ''): void
{
    global $pass, $fail;
    if ($cond) { $pass++; echo "  PASS  {$name}" . PHP_EOL; }
    else { $fail++; echo "  FAIL  {$name}" . ($detail !== '' ? "  ({$detail})" : '') . PHP_EOL; }
}
function render(string $path): string
{
    Auth::logout();
    $_SERVER['REQUEST_METHOD'] = 'GET';
    $_SERVER['REQUEST_URI'] = $path;
    $_GET = [];
    ob_start();
    CuiRouter::dispatch();
    return (string) ob_get_clean();
}

echo '物格云 a11y tests · v' . APP_VERSION . PHP_EOL . PHP_EOL;
$home = render('/');
check('html lang', str_contains($home, '<html lang="zh-CN">'));
check('viewport', str_contains($home, 'name="viewport"'));
check('skip link', str_contains($home, 'skip-link'));
check('单一 h1', substr_count($home, '<h1') === 1);
check('按钮有 type', preg_match_all('/<button(?![^>]*type=)/', $home) === 0);
check('无正 tabindex', preg_match('/tabindex="[1-9]/', $home) === 0);
check('图片有 alt', preg_match('/<img(?![^>]*alt=)/', $home) === 0);
$login = render('/login');
check('登录 label', str_contains($login, '<label'));
check('登录 input 有 name', str_contains($login, 'name="account"') && str_contains($login, 'name="password"'));
check('登录无正 tabindex', preg_match('/tabindex="[1-9]/', $login) === 0);

echo PHP_EOL . '结果：PASS ' . $pass . ' / FAIL ' . $fail . PHP_EOL;
exit($fail > 0 ? 1 : 0);

<?php
declare(strict_types=1);

/**
 * 性能预算测试（无外部依赖）。
 * 用法：php platform/tests/perf.php
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

echo '物格云 performance tests · v' . APP_VERSION . PHP_EOL . PHP_EOL;
$css = (int) filesize(PUBLIC_PATH . '/assets/css/app.css');
$js = (int) filesize(PUBLIC_PATH . '/assets/js/app.js');
check('CSS ≤60KB', $css <= 60 * 1024, $css . ' bytes');
check('JS ≤120KB', $js <= 120 * 1024, $js . ' bytes');

$external = 0;
foreach (['css/app.css', 'js/app.js'] as $asset) {
    $content = (string) file_get_contents(PUBLIC_PATH . '/assets/' . $asset);
    $external += preg_match_all('#https?://#i', $content);
}
check('无外部资源 URL', $external === 0, (string) $external . ' urls');

$home = '';
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['REQUEST_URI'] = '/';
$_GET = [];
ob_start();
CuiRouter::dispatch();
$home = (string) ob_get_clean();
check('首页 HTML <120KB', strlen($home) < 120 * 1024, strlen($home) . ' bytes');
check('首页无内联 script', !str_contains($home, '<script>'));
check('首页有 viewport', str_contains($home, 'name="viewport"'));

echo PHP_EOL . '结果：PASS ' . $pass . ' / FAIL ' . $fail . PHP_EOL;
$out = (string) ob_get_clean();
echo $out;
exit($fail > 0 ? 1 : 0);

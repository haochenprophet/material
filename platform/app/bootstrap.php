<?php
declare(strict_types=1);

/**
 * 物格云平台启动文件（v0.1.0）
 *
 * 设计原则：
 * 1. 只新增，不删除/不覆盖 material 旧代码；
 * 2. 所有页面由 PHP UI class 输出 HTML；
 * 3. 每个请求带 X-App-Version / X-Release-Id / X-Request-Id，便于回溯；
 * 4. 无 Composer 依赖，PHP 8.2+ 可直接跑。
 */

define('PLATFORM_PATH', dirname(__DIR__));
define('MATERIAL_PATH', dirname(PLATFORM_PATH));
define('APP_PATH', PLATFORM_PATH . '/app');
define('FRAMEWORK_PATH', PLATFORM_PATH . '/framework');
define('VIEW_PATH', PLATFORM_PATH . '/views');
define('PUBLIC_PATH', PLATFORM_PATH . '/public');
define('STORAGE_PATH', PLATFORM_PATH . '/storage');

$config = require PLATFORM_PATH . '/config/app.php';

define('APP_NAME', $config['name']);
define('APP_VERSION', $config['version']);
define('APP_DEBUG', (bool) $config['debug']);
define('APP_BASE_URL', rtrim((string) $config['base_url'], '/'));

$releaseFile = PLATFORM_PATH . '/RELEASE';
$releaseId = is_file($releaseFile) ? trim((string) file_get_contents($releaseFile)) : 'dev-' . date('YmdHis');
define('APP_RELEASE_ID', $releaseId);

date_default_timezone_set('Asia/Shanghai');
mb_internal_encoding('UTF-8');

spl_autoload_register(static function (string $class): void {
    $candidates = [
        FRAMEWORK_PATH . '/' . $class . '.php',
        FRAMEWORK_PATH . '/components/' . $class . '.php',
        APP_PATH . '/' . $class . '.php',
        APP_PATH . '/Data/' . $class . '.php',
        APP_PATH . '/Domain/' . $class . '.php',
        APP_PATH . '/Infra/' . $class . '.php',
        APP_PATH . '/Repository/' . $class . '.php',
        APP_PATH . '/Security/' . $class . '.php',
        APP_PATH . '/Storage/' . $class . '.php',
    ];
    foreach ($candidates as $file) {
        if (is_file($file)) {
            require_once $file;
            return;
        }
    }
});

require_once FRAMEWORK_PATH . '/CuiHtml.php';
require_once FRAMEWORK_PATH . '/Cui.php';
require_once FRAMEWORK_PATH . '/CuiTheme.php';
require_once FRAMEWORK_PATH . '/CuiIcon.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_name($config['session']['name']);
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'httponly' => (bool) $config['session']['httponly'],
        'samesite' => (string) $config['session']['samesite'],
        'secure'   => (bool) $config['session']['secure'],
    ]);
    session_start();
}

$requestId = 'req_' . bin2hex(random_bytes(8));
define('APP_REQUEST_ID', $requestId);
header('X-Request-Id: ' . $requestId);
header('X-App-Version: ' . APP_VERSION);
header('X-Release-Id: ' . APP_RELEASE_ID);
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('X-Permitted-Cross-Domain-Policies: none');
header('Permissions-Policy: camera=(self), microphone=(), geolocation=()');
header('Referrer-Policy: strict-origin-when-cross-origin');
header("Content-Security-Policy: default-src 'self'; img-src 'self' data:; style-src 'self' 'unsafe-inline'; script-src 'self'; base-uri 'self'; form-action 'self'; frame-ancestors 'self'");
if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
    header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
}

set_exception_handler(static function (Throwable $e): void {
    $log = STORAGE_PATH . '/logs/error.log';
    @file_put_contents($log, sprintf(
        "[%s] %s %s in %s:%d\n%s\n\n",
        date('c'),
        APP_REQUEST_ID,
        $e->getMessage(),
        $e->getFile(),
        $e->getLine(),
        $e->getTraceAsString()
    ), FILE_APPEND);

    if (APP_DEBUG) {
        http_response_code(500);
        header('Content-Type: text/html; charset=UTF-8');
        echo '<!doctype html><html lang="zh-CN"><meta charset="utf-8"><title>500</title>';
        echo '<body style="font:14px/1.7 Microsoft YaHei,sans-serif;padding:40px">';
        echo '<h1>500 · 页面出错</h1><p>request_id: <code>' . CuiHtml::e(APP_REQUEST_ID) . '</code></p>';
        echo '<pre style="white-space:pre-wrap;background:#f1f5f9;padding:16px;border-radius:10px">';
        echo CuiHtml::e($e->getMessage() . "\n" . $e->getFile() . ':' . $e->getLine());
        echo '</pre></body></html>';
    } else {
        http_response_code(500);
        echo 'Internal Server Error';
    }
});

require_once APP_PATH . '/Router.php';

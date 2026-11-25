<?php
declare(strict_types=1);

/**
 * PHP 内置服务器路由脚本：
 *   php -S 127.0.0.1:8091 -t platform/public platform/public/router.php
 * 静态文件直接返回；其余交给 index.php。
 */
$path = (string) (parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/');
$file = __DIR__ . $path;
if ($path !== '/' && is_file($file)) {
    return false;
}
require __DIR__ . '/index.php';

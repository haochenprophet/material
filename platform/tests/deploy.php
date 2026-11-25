<?php
declare(strict_types=1);

/**
 * 部署配置静态检查（不连服务器）。
 * 用法：php platform/tests/deploy.php
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
    if ($cond) {
        $pass++;
        echo "  PASS  {$name}" . PHP_EOL;
    } else {
        $fail++;
        echo "  FAIL  {$name}" . ($detail !== '' ? "  ({$detail})" : '') . PHP_EOL;
    }
}

echo '物格云 deployment tests · v' . APP_VERSION . PHP_EOL . PHP_EOL;
$nginx = (string) file_get_contents(PLATFORM_PATH . '/deploy/nginx.conf.example');
$fpm = (string) file_get_contents(PLATFORM_PATH . '/deploy/php-fpm-pool.conf.example');
$readme = (string) file_get_contents(PLATFORM_PATH . '/deploy/README.md');

check('root 指向 public', str_contains($nginx, 'platform/public'));
check('try_files 到 index.php', str_contains($nginx, '/index.php?$query_string'));
check('PHP-FPM 而非 mod_php', str_contains($nginx, 'fastcgi_pass') && !str_contains($nginx, 'php_module'));
check('隐藏文件拒绝', str_contains($nginx, 'deny all'));
check('storage deny', str_contains($nginx, 'location ^~ /storage/'));
check('FPM 非 root', str_contains($fpm, 'user = www-data') && !str_contains($fpm, 'user = root'));
check('部署文档含回滚', str_contains($readme, '回滚') && str_contains($readme, '不删除'));
check('healthcheck 存在', is_file(PLATFORM_PATH . '/bin/healthcheck.php') && str_contains((string) file_get_contents(PLATFORM_PATH . '/bin/healthcheck.php'), 'HTTP health'));
check('备份脚本存在', is_file(PLATFORM_PATH . '/bin/db-backup.php') && str_contains((string) file_get_contents(PLATFORM_PATH . '/bin/db-backup.php'), '--execute'));
check('备份仅 mats_platform', str_contains((string) file_get_contents(PLATFORM_PATH . '/bin/db-backup.php'), 'mats_platform_'));
check('备份目录可写', is_dir(STORAGE_PATH) && is_writable(STORAGE_PATH));

echo PHP_EOL . '结果：PASS ' . $pass . ' / FAIL ' . $fail . PHP_EOL;
exit($fail > 0 ? 1 : 0);

<?php
declare(strict_types=1);
/** 创建测试 API token 并打印一次。 */
if (PHP_SAPI !== 'cli') { http_response_code(403); exit(1); }
require dirname(__DIR__) . '/app/bootstrap.php';
$user = (string) getenv('DB_USER');
if ($user === '') { fwrite(STDERR, "DB_USER 未设置\n"); exit(2); }
$repo = new ApiTokenRepository(Database::pdo());
$t = $repo->create('demo-cli', ['read', 'write'], 60);
echo 'token=' . $t['token'] . PHP_EOL;
echo 'id=' . $t['id'] . PHP_EOL;

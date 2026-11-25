<?php
declare(strict_types=1);

/**
 * 只读健康检查：manifest/版本/存储/数据库/可选 HTTP。
 * 用法：php platform/bin/healthcheck.php [--url=http://127.0.0.1:8091/health]
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    echo 'CLI only';
    exit(1);
}
require dirname(__DIR__) . '/app/bootstrap.php';

$url = '';
foreach ($argv as $arg) {
    if (str_starts_with($arg, '--url=')) {
        $url = substr($arg, 6);
    }
}
$fail = [];
$manifest = PLATFORM_PATH . '/manifest.json';
if (!is_file($manifest)) {
    $fail[] = 'manifest 缺失';
}
if (!is_dir(STORAGE_PATH) || !is_writable(STORAGE_PATH)) {
    $fail[] = 'storage 不可写';
}
if (is_file($manifest)) {
    $m = json_decode((string) file_get_contents($manifest), true);
    if (!is_array($m) || !is_array($m['files'] ?? null) || $m['files'] === []) {
        $fail[] = 'manifest 无效';
    }
}
if (Database::configured()) {
    try {
        Database::pdo()->query('SELECT 1')->fetchColumn();
    } catch (Throwable $e) {
        $fail[] = 'DB: ' . $e->getMessage();
    }
}
if ($url !== '') {
    $ok = false;
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 5, CURLOPT_CONNECTTIMEOUT => 3]);
        $body = (string) curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        curl_close($ch);
        $ok = $code === 200 && preg_match('/"status"\s*:\s*"ok"/', $body) === 1;
    } else {
        $ctx = stream_context_create(['http' => ['timeout' => 5, 'ignore_errors' => true]]);
        $body = @file_get_contents($url, false, $ctx);
        $ok = $body !== false && preg_match('/"status"\s*:\s*"ok"/', (string) $body) === 1;
    }
    if (!$ok) {
        $fail[] = 'HTTP health 失败: ' . $url;
    }
}
if ($fail !== []) {
    echo 'FAIL: ' . implode(' | ', $fail) . PHP_EOL;
    exit(1);
}
echo 'OK ' . APP_VERSION . ' ' . APP_RELEASE_ID . ' storage=writable' . (Database::configured() ? ' db=ok' : ' db=unconfigured') . ($url !== '' ? ' http=ok' : '') . PHP_EOL;
exit(0);

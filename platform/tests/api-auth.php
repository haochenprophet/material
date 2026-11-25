<?php
declare(strict_types=1);

/**
 * API token/scope/限流测试（SQLite）。用法：php platform/tests/api-auth.php
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
function authError(int $status, callable $fn): bool
{
    try {
        $fn();
        return false;
    } catch (ApiAuthException $e) {
        return $e->status === $status;
    }
}

echo '物格云 API auth tests · v' . APP_VERSION . PHP_EOL . PHP_EOL;
putenv('API_REQUIRE_TOKEN=1');
$pdo = new PDO('sqlite::memory:');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->exec('CREATE TABLE api_token (id INTEGER PRIMARY KEY AUTOINCREMENT,token_hash TEXT UNIQUE,client_name TEXT,scopes_json TEXT,rate_limit INT,status TEXT,created_at TEXT,last_used_at TEXT)');
$pdo->exec('CREATE TABLE api_rate_limit (token_id INT,window_start INT,count INT,PRIMARY KEY (token_id,window_start))');
$repo = new ApiTokenRepository($pdo);
$one = $repo->create('test', ['read'], 3);
check('创建 token', str_starts_with($one['token'], 'sk_'));
$found = $repo->findByToken($one['token']);
check('token 可查', is_array($found) && $found['scopes'] === ['read']);
check('错误 token 拒绝', $repo->findByToken('bad') === null);
$auth = new ApiAuth($pdo);
$_SERVER['HTTP_AUTHORIZATION'] = 'Bearer ' . $one['token'];
check('read 通过', is_array($auth->guard('read')));
check('write 403', authError(403, static fn () => $auth->guard('write')));
$w = $repo->create('writer', ['write'], 2);
$_SERVER['HTTP_AUTHORIZATION'] = 'Bearer ' . $w['token'];
check('write 通过', is_array($auth->guard('write')));
check('write 可读', is_array($auth->guard('read')));
$_SERVER['HTTP_AUTHORIZATION'] = '';
check('无 token 401', authError(401, static fn () => $auth->guard('read')));
$_SERVER['HTTP_AUTHORIZATION'] = 'Bearer ' . $one['token'];
$auth->guard('read');
$auth->guard('read');
check('第 4 次 429', authError(429, static fn () => $auth->guard('read')));
check('吊销 token', $repo->revoke((int) $one['id']) === true);
check('吊销后拒绝', authError(401, static fn () => $auth->guard('read')));

echo PHP_EOL . '结果：PASS ' . $pass . ' / FAIL ' . $fail . PHP_EOL;
exit($fail > 0 ? 1 : 0);

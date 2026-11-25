<?php
declare(strict_types=1);

/**
 * 真实 listing 仓储测试（只读）。未设置 DB_USER 时 SKIP。
 * 用法：DB_USER=... DB_PASS=... DB_NAME=mats_platform_next2 php platform/tests/listing-repo-live.php
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

echo '物格云 live listing repo tests · v' . APP_VERSION . PHP_EOL . PHP_EOL;
if ((string) getenv('DB_USER') === '') {
    echo 'SKIP: DB_USER 未设置' . PHP_EOL;
    exit(0);
}
$name = getenv('DB_NAME') ?: 'mats_platform_next2';
if (!preg_match('/^mats[a-z0-9_]*$/', $name) || $name === 'materiel') {
    echo 'FAIL: 数据库名必须是 mats*' . PHP_EOL;
    exit(1);
}
$host = getenv('DB_HOST') ?: '127.0.0.1';
$port = (int) (getenv('DB_PORT') ?: 3306);
try {
    $pdo = new PDO("mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4", (string) getenv('DB_USER'), (string) getenv('DB_PASS'), [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    $repo = new PdoListingRepository($pdo);
    $all = $repo->all();
    check('至少 1 条 listing', count($all) >= 1, (string) count($all));
    $first = $all[0] ?? [];
    check('标题非空', ($first['title'] ?? '') !== '');
    check('价格格式', str_starts_with((string) ($first['price'] ?? ''), '¥'));
    $found = $repo->find((string) ($first['id'] ?? ''));
    check('find 可回读', ($found['id'] ?? '') === ($first['id'] ?? '___'));
    check('badge 存在', ($first['badge'] ?? '') !== '');
} catch (Throwable $e) {
    echo 'FAIL: ' . $e->getMessage() . PHP_EOL;
    exit(1);
}

echo PHP_EOL . '结果：PASS ' . $pass . ' / FAIL ' . $fail . PHP_EOL;
exit($fail > 0 ? 1 : 0);

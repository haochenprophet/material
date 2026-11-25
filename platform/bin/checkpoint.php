<?php
declare(strict_types=1);

/**
 * 10 小时开发检查点：记录版本/Release/manifest/Git/测试结果。
 * 用法：php platform/bin/checkpoint.php [--with-tests]
 * 只新增 storage/checkpoints/*.json，不删除任何文件。
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    echo 'CLI only';
    exit(1);
}

require dirname(__DIR__) . '/app/bootstrap.php';

$withTests = in_array('--with-tests', $argv, true);
$run = static function (string $cmd): array {
    $output = [];
    $code = 0;
    @exec($cmd . ' 2>&1', $output, $code);
    return ['exit' => $code, 'tail' => array_slice($output, -8)];
};

$verify = $withTests ? $run(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(PLATFORM_PATH . '/bin/verify.php')) : null;
$domain = $withTests ? $run(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(PLATFORM_PATH . '/tests/run.php')) : null;
$api = $withTests ? $run(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(PLATFORM_PATH . '/tests/api.php')) : null;
$db = $withTests ? $run(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(PLATFORM_PATH . '/tests/db.php')) : null;
$auth = $withTests ? $run(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(PLATFORM_PATH . '/tests/auth.php')) : null;
$bom = $withTests ? $run(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(PLATFORM_PATH . '/tests/bom.php')) : null;
$upload = $withTests ? $run(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(PLATFORM_PATH . '/tests/upload.php')) : null;
$pdo = $withTests ? $run(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(PLATFORM_PATH . '/tests/pdo.php')) : null;
$trade = $withTests ? $run(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(PLATFORM_PATH . '/tests/trade.php')) : null;
$finance = $withTests ? $run(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(PLATFORM_PATH . '/tests/finance.php')) : null;
$orderRepo = $withTests ? $run(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(PLATFORM_PATH . '/tests/order-repo.php')) : null;
$orderPresenter = $withTests ? $run(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(PLATFORM_PATH . '/tests/order-presenter.php')) : null;
$listingRepo = $withTests ? $run(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(PLATFORM_PATH . '/tests/listing-repo.php')) : null;
$listingWriter = $withTests ? $run(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(PLATFORM_PATH . '/tests/listing-writer.php')) : null;
$listingReview = $withTests ? $run(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(PLATFORM_PATH . '/tests/listing-review.php')) : null;
$catalog = $withTests ? $run(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(PLATFORM_PATH . '/tests/catalog-source.php')) : null;
$catalogDetail = $withTests ? $run(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(PLATFORM_PATH . '/tests/catalog-detail.php')) : null;
$listingLive = $withTests ? $run(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(PLATFORM_PATH . '/tests/listing-repo-live.php')) : null;
$perf = $withTests ? $run(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(PLATFORM_PATH . '/tests/perf.php')) : null;
$security = $withTests ? $run(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(PLATFORM_PATH . '/tests/security.php')) : null;
$deploy = $withTests ? $run(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(PLATFORM_PATH . '/tests/deploy.php')) : null;
$authDb = $withTests ? $run(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(PLATFORM_PATH . '/tests/auth-db.php')) : null;
$authDbLive = $withTests ? $run(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(PLATFORM_PATH . '/tests/auth-db-live.php')) : null;
$throttle = $withTests ? $run(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(PLATFORM_PATH . '/tests/throttle.php')) : null;
$callback = $withTests ? $run(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(PLATFORM_PATH . '/tests/payment-callback.php')) : null;
$migration = $withTests ? $run(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(PLATFORM_PATH . '/tests/migration.php')) : null;
$migrationRunner = $withTests ? $run(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(PLATFORM_PATH . '/tests/migration-runner.php')) : null;
$dbLive = $withTests ? $run(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(PLATFORM_PATH . '/tests/db-live.php')) : null;

$manifestFile = PLATFORM_PATH . '/manifest.json';
$manifest = is_file($manifestFile) ? json_decode((string) file_get_contents($manifestFile), true) : null;
$git = null;
if (is_dir(MATERIAL_PATH . '/.git')) {
    $lines = [];
    $code = 0;
    @exec('git -C ' . escapeshellarg(MATERIAL_PATH) . ' status --short 2>&1', $lines, $code);
    $git = ['exit' => $code, 'lines' => array_slice($lines, 0, 40)];
}

$record = [
    'time' => date('c'),
    'version' => APP_VERSION,
    'release' => APP_RELEASE_ID,
    'request_id' => APP_REQUEST_ID,
    'php' => PHP_VERSION,
    'manifest' => [
        'exists' => is_file($manifestFile),
        'files' => is_array($manifest['files'] ?? null) ? count($manifest['files']) : 0,
        'sha256' => is_file($manifestFile) ? hash_file('sha256', $manifestFile) : null,
        'generated_at' => $manifest['generated_at'] ?? null,
    ],
    'tests' => [
        'verify' => $verify,
        'domain' => $domain,
        'api' => $api,
        'db' => $db,
        'auth' => $auth,
        'bom' => $bom,
        'upload' => $upload,
        'pdo' => $pdo,
        'trade' => $trade,
        'finance' => $finance,
        'order_repo' => $orderRepo,
        'order_presenter' => $orderPresenter,
        'listing_repo' => $listingRepo,
        'listing_writer' => $listingWriter,
        'listing_review' => $listingReview,
        'catalog' => $catalog,
        'catalog_detail' => $catalogDetail,
        'listing_live' => $listingLive,
        'perf' => $perf,
        'security' => $security,
        'deploy' => $deploy,
        'auth_db' => $authDb,
        'auth_db_live' => $authDbLive,
        'throttle' => $throttle,
        'callback' => $callback,
        'migration' => $migration,
        'migration_runner' => $migrationRunner,
        'db_live' => $dbLive,
    ],
    'git' => $git,
    'legacy' => [
        'mysql' => is_file(MATERIAL_PATH . '/class/mysql.php'),
        'material_php' => is_file(MATERIAL_PATH . '/material.php'),
        'git' => is_dir(MATERIAL_PATH . '/.git'),
    ],
];

$dir = STORAGE_PATH . '/checkpoints';
if (!is_dir($dir) && !mkdir($dir, 0777, true) && !is_dir($dir)) {
    fwrite(STDERR, "无法创建 {$dir}\n");
    exit(1);
}
$file = $dir . '/checkpoint-' . date('Ymd-His') . '.json';
$json = json_encode($record, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
file_put_contents($file, $json);
file_put_contents($dir . '/latest.json', $json);

echo 'checkpoint: ' . $file . PHP_EOL;
echo 'version: ' . APP_VERSION . ' · release: ' . APP_RELEASE_ID . PHP_EOL;
echo 'manifest: ' . $record['manifest']['files'] . ' files · ' . substr((string) $record['manifest']['sha256'], 0, 16) . '…' . PHP_EOL;
if ($withTests) {
    echo 'verify: ' . ($verify['exit'] ?? '?') . ' · domain: ' . ($domain['exit'] ?? '?') . ' · api: ' . ($api['exit'] ?? '?') . ' · db: ' . ($db['exit'] ?? '?') . ' · auth: ' . ($auth['exit'] ?? '?') . ' · bom: ' . ($bom['exit'] ?? '?') . ' · upload: ' . ($upload['exit'] ?? '?') . ' · pdo: ' . ($pdo['exit'] ?? '?') . ' · trade: ' . ($trade['exit'] ?? '?') . ' · finance: ' . ($finance['exit'] ?? '?') . ' · order_repo: ' . ($orderRepo['exit'] ?? '?') . ' · order_presenter: ' . ($orderPresenter['exit'] ?? '?') . ' · listing_repo: ' . ($listingRepo['exit'] ?? '?') . ' · listing_writer: ' . ($listingWriter['exit'] ?? '?') . ' · listing_review: ' . ($listingReview['exit'] ?? '?') . ' · catalog: ' . ($catalog['exit'] ?? '?') . ' · catalog_detail: ' . ($catalogDetail['exit'] ?? '?') . ' · listing_live: ' . ($listingLive['exit'] ?? '?') . ' · perf: ' . ($perf['exit'] ?? '?') . ' · security: ' . ($security['exit'] ?? '?') . ' · deploy: ' . ($deploy['exit'] ?? '?') . ' · auth_db: ' . ($authDb['exit'] ?? '?') . ' · auth_db_live: ' . ($authDbLive['exit'] ?? '?') . ' · throttle: ' . ($throttle['exit'] ?? '?') . ' · callback: ' . ($callback['exit'] ?? '?') . ' · migration: ' . ($migration['exit'] ?? '?') . ' · migration_runner: ' . ($migrationRunner['exit'] ?? '?') . ' · db_live: ' . ($dbLive['exit'] ?? '?') . PHP_EOL;
}

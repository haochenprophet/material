<?php
declare(strict_types=1);

/**
 * 只读状态汇总：版本/Release/manifest/旧代码/测试/数据库/检查点。
 * 用法：php platform/bin/status.php
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    echo 'CLI only';
    exit(1);
}

require dirname(__DIR__) . '/app/bootstrap.php';

$manifestFile = PLATFORM_PATH . '/manifest.json';
$manifest = is_file($manifestFile) ? json_decode((string) file_get_contents($manifestFile), true) : null;
$checkpoints = glob(STORAGE_PATH . '/checkpoints/checkpoint-*.json') ?: [];
usort($checkpoints, static fn ($a, $b) => filemtime($b) <=> filemtime($a));
$legacy = [];
foreach (['class/mysql.php', 'class/page.php', 'material.php', 'header.php', '.git'] as $rel) {
    $legacy[$rel] = file_exists(MATERIAL_PATH . '/' . $rel);
}
$db = null;
if (Database::configured()) {
    try {
        $pdo = Database::pdo();
        $name = (string) (getenv('DB_NAME') ?: 'mats_platform_dev');
        $db = [
            'database' => $name,
            'tables' => (int) $pdo->query("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema=" . $pdo->quote($name))->fetchColumn(),
        ];
        foreach (['role', 'permission', 'warehouse', 'listing', 'material'] as $t) {
            $db[$t] = (int) $pdo->query("SELECT COUNT(*) FROM `{$t}`")->fetchColumn();
        }
    } catch (Throwable $e) {
        $db = ['error' => $e->getMessage()];
    }
}

$out = [
    'version' => APP_VERSION,
    'release' => APP_RELEASE_ID,
    'manifest' => [
        'files' => is_array($manifest['files'] ?? null) ? count($manifest['files']) : 0,
        'sha256' => is_file($manifestFile) ? hash_file('sha256', $manifestFile) : null,
    ],
    'releases' => count(glob(PLATFORM_PATH . '/releases/*', GLOB_ONLYDIR) ?: []),
    'tests' => count(glob(PLATFORM_PATH . '/tests/*.php') ?: []),
    'legacy' => $legacy,
    'checkpoints' => count($checkpoints),
    'latest_checkpoint' => $checkpoints[0] ?? null,
    'database' => $db,
    'read_only' => true,
];
echo json_encode($out, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
exit(0);

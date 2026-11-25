<?php
declare(strict_types=1);

/**
 * Git 版本管控入口：只更新 manifest/RELEASE，不复制代码、不创建 release 目录。
 * 用法：php platform/bin/snapshot.php
 *
 * 变更为：
 * - 文件清单来自 git 已跟踪文件（ai-test 才有 tests/seed 资产）
 * - 发布标识来自当前分支 + VERSION
 * - 真正的版本回滚使用 git commit/tag，不使用目录副本
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    echo 'CLI only';
    exit(1);
}

$root = dirname(__DIR__);
$version = trim((string) file_get_contents($root . '/VERSION'));
$branch = trim((string) @shell_exec('git -C ' . escapeshellarg($root) . ' rev-parse --abbrev-ref HEAD 2>NUL'));
if ($branch === '') {
    $branch = 'unknown';
}
$release = 'git-' . preg_replace('/[^a-z0-9_-]/i', '-', $branch) . '-v' . $version;

$raw = @shell_exec('git -C ' . escapeshellarg($root) . ' ls-files -z 2>NUL');
$files = [];
if (is_string($raw) && $raw !== '') {
    foreach (explode("\0", $raw) as $rel) {
        $rel = str_replace('\\', '/', trim($rel));
        if ($rel === '' || $rel === 'manifest.json' || $rel === 'RELEASE') {
            continue;
        }
        if (str_starts_with($rel, 'releases/') || str_starts_with($rel, 'storage/')) {
            continue;
        }
        if (!is_file($root . '/' . $rel)) {
            continue;
        }
        $files[] = $rel;
    }
}
sort($files);
if ($files === []) {
    fwrite(STDERR, "没有 git 跟踪文件；请在 material 仓库内运行，且先 git add 代码\n");
    exit(1);
}

$manifest = [
    'app' => '物格云 MaterialGrid',
    'version' => $version,
    'release' => $release,
    'branch' => $branch,
    'generated_at' => date('c'),
    'php' => PHP_VERSION,
    'files' => [],
];
foreach ($files as $rel) {
    $path = $root . '/' . $rel;
    $manifest['files'][$rel] = [
        'size' => filesize($path),
        'sha256' => hash_file('sha256', $path),
        'mtime' => date('c', (int) filemtime($path)),
    ];
}
$json = json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
if (file_put_contents($root . '/manifest.json', $json) === false) {
    fwrite(STDERR, "无法写入 manifest.json\n");
    exit(1);
}
if (file_put_contents($root . '/RELEASE', $release . PHP_EOL) === false) {
    fwrite(STDERR, "无法写入 RELEASE\n");
    exit(1);
}
echo 'branch:  ' . $branch . PHP_EOL;
echo 'release: ' . $release . PHP_EOL;
echo 'files:   ' . count($files) . ' (git tracked only)' . PHP_EOL;
echo 'copy:    0 (no release directory)' . PHP_EOL;

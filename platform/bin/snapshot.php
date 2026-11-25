<?php
declare(strict_types=1);

/**
 * 生成不可变发布快照（目录副本，不删除旧 release）。
 * 用法：php platform/bin/snapshot.php
 *
 * 结构：
 *   platform/manifest.json
 *   platform/RELEASE
 *   platform/releases/v0.1.0-YYYYmmdd-HHMMSS/
 *     app/ framework/ views/ public/ config/ docs/ bin/ tests/
 *     VERSION RELEASE manifest.json
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    echo 'CLI only';
    exit(1);
}

$root = dirname(__DIR__);
$version = trim((string) file_get_contents($root . '/VERSION'));
$release = 'v' . $version . '-' . date('Ymd-His');
$releaseDir = $root . '/releases/' . $release;
$suffix = 1;
while (is_dir($releaseDir)) {
    $release = 'v' . $version . '-' . date('Ymd-His') . '-' . (++$suffix);
    $releaseDir = $root . '/releases/' . $release;
}

$skipDirs = ['releases', 'storage', '.git'];
$skipFiles = ['manifest.json', 'RELEASE'];

/** @return array<int, string> 相对路径（/ 分隔） */
function collect_files(string $root, array $skipDirs, array $skipFiles): array
{
    $out = [];
    $it = new RecursiveIteratorIterator(
        new RecursiveCallbackFilterIterator(
            new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS),
            static function (SplFileInfo $file) use ($skipDirs): bool {
                if ($file->isDir() && in_array($file->getFilename(), $skipDirs, true)) {
                    return false;
                }
                return true;
            }
        )
    );
    foreach ($it as $file) {
        if (!$file instanceof SplFileInfo || !$file->isFile()) {
            continue;
        }
        $rel = str_replace('\\', '/', substr($file->getPathname(), strlen($root) + 1));
        if (in_array($rel, $skipFiles, true)) {
            continue;
        }
        $out[] = $rel;
    }
    sort($out);
    return $out;
}

$files = collect_files($root, $skipDirs, $skipFiles);
$manifest = [
    'app' => '物格云 MaterialGrid',
    'version' => $version,
    'release' => $release,
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

$manifestJson = json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
if (file_put_contents($root . '/manifest.json', $manifestJson) === false) {
    fwrite(STDERR, "无法写入 manifest.json\n");
    exit(1);
}
if (file_put_contents($root . '/RELEASE', $release . PHP_EOL) === false) {
    fwrite(STDERR, "无法写入 RELEASE\n");
    exit(1);
}

if (!is_dir($releaseDir) && !mkdir($releaseDir, 0777, true) && !is_dir($releaseDir)) {
    fwrite(STDERR, "无法创建 release 目录: {$releaseDir}\n");
    exit(1);
}

$copied = 0;
foreach ($files as $rel) {
    $src = $root . '/' . $rel;
    $dst = $releaseDir . '/' . $rel;
    $dir = dirname($dst);
    if (!is_dir($dir) && !mkdir($dir, 0777, true) && !is_dir($dir)) {
        fwrite(STDERR, "无法创建目录: {$dir}\n");
        exit(1);
    }
    if (!copy($src, $dst)) {
        fwrite(STDERR, "复制失败: {$rel}\n");
        exit(1);
    }
    $copied++;
}
file_put_contents($releaseDir . '/manifest.json', $manifestJson);
file_put_contents($releaseDir . '/RELEASE', $release . PHP_EOL);

echo 'release: ' . $release . PHP_EOL;
echo 'files:   ' . $copied . PHP_EOL;
echo 'dir:     ' . $releaseDir . PHP_EOL;
echo 'manifest: ' . $root . '/manifest.json' . PHP_EOL;

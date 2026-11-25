<?php
declare(strict_types=1);

/**
 * 用 git tag 发布版本：不复制代码、不创建 release 目录。
 * 用法：php platform/bin/git-release.php
 * 前置：platform 路径已提交，manifest/RELEASE 已更新并提交。
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    echo 'CLI only';
    exit(1);
}
$root = dirname(__DIR__);
$git = 'git -C ' . escapeshellarg($root) . ' ';
$branch = trim((string) @shell_exec($git . 'rev-parse --abbrev-ref HEAD 2>NUL'));
$version = trim((string) file_get_contents($root . '/VERSION'));
if (!in_array($branch, ['ai', 'ai-test'], true)) {
    fwrite(STDERR, "只允许在 ai / ai-test 分支发布，当前: {$branch}\n");
    exit(1);
}
$status = (string) @shell_exec($git . 'status --porcelain -- . 2>NUL');
if (trim($status) !== '') {
    fwrite(STDERR, "platform 路径未提交，先 commit：\n{$status}\n");
    exit(1);
}
$tag = $branch . '-v' . $version;
$exists = trim((string) @shell_exec($git . 'tag -l ' . escapeshellarg($tag) . ' 2>NUL'));
if ($exists !== '') {
    fwrite(STDERR, "tag 已存在: {$tag}；请提升 VERSION 或使用新 tag\n");
    exit(1);
}
$hash = trim((string) @shell_exec($git . 'rev-parse --short HEAD 2>NUL'));
$msg = 'release ' . $tag . ' commit ' . $hash;
exec($git . 'tag -a ' . escapeshellarg($tag) . ' -m ' . escapeshellarg($msg) . ' 2>&1', $out, $code);
if ($code !== 0) {
    fwrite(STDERR, 'tag 失败: ' . implode(' | ', $out) . PHP_EOL);
    exit(1);
}
echo 'branch: ' . $branch . PHP_EOL;
echo 'tag:    ' . $tag . PHP_EOL;
echo 'commit: ' . $hash . PHP_EOL;
echo 'copy:   0' . PHP_EOL;
exit(0);

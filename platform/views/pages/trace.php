<?php
declare(strict_types=1);
$manifestFile = PLATFORM_PATH . '/manifest.json';
$manifest = is_file($manifestFile) ? json_decode((string) file_get_contents($manifestFile), true) : null;
$files = is_array($manifest['files'] ?? null) ? $manifest['files'] : [];

echo CuiPageHeader::render([
    'title' => '版本与回溯',
    'subtitle' => '每个请求带 X-Release-Id；每个文件有 SHA-256；每次发布有清单和快照。',
    'actions' => [CuiButton::render(['label' => '健康检查 JSON', 'href' => '/health'])],
]);

echo '<div class="stats">' . CuiStat::render('v' . APP_VERSION, '应用版本') . CuiStat::render(APP_RELEASE_ID, 'Release ID') . CuiStat::render(APP_REQUEST_ID, '本次 Request ID') . CuiStat::render(count($files) . ' 个', '清单文件') . '</div>';

$rows = [];
foreach (array_slice($files, 0, 80) as $path => $meta) {
    $rows[] = [
        CuiHtml::e((string) $path),
        CuiHtml::e((string) ($meta['size'] ?? '')),
        '<code>' . CuiHtml::e(substr((string) ($meta['sha256'] ?? ''), 0, 16)) . '…</code>',
    ];
}
echo CuiCard::render(CuiCard::header('文件清单（前 80 个）', $manifest['generated_at'] ?? '尚未生成') . CuiTable::render(['文件', '字节', 'SHA-256 前缀'], $rows));
echo CuiCard::render('<div class="kv"><b>PHP</b><span>' . CuiHtml::e(PHP_VERSION) . '</span><b>应用目录</b><span>' . CuiHtml::e(PLATFORM_PATH) . '</span><b>旧代码</b><span>未修改、未删除；旧 material/ 与 .git 原样保留</span><b>回溯文档</b><span>platform/docs/ROLLBACK.md</span></div>');
echo CuiCard::render('<div class="notice">' . Cui::icon('alert') . ' 回滚前先运行 <code>php bin/verify.php</code>；发布快照在 <code>platform/releases/</code>。不要删除旧目录，用“新增版本目录 + 切换入口”回滚。</div>');

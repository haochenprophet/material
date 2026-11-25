<?php
declare(strict_types=1);

/**
 * 创建/重建开发用 SQLite（storage/dev.sqlite），只写新文件，不碰旧库。
 * 用法：php platform/bin/dev-db.php
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    echo 'CLI only';
    exit(1);
}

require dirname(__DIR__) . '/app/bootstrap.php';

if (!extension_loaded('pdo_sqlite')) {
    fwrite(STDERR, "pdo_sqlite 未安装\n");
    exit(1);
}

$file = STORAGE_PATH . '/dev.sqlite';
$pdo = new PDO('sqlite:' . $file);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->exec((string) file_get_contents(PLATFORM_PATH . '/database/dev-schema.sql'));

$stmt = $pdo->prepare('INSERT OR REPLACE INTO materials (id,title,spec,price,unit,stock,location,badge,badge_kind,thumb,thumb_class) VALUES (:id,:title,:spec,:price,:unit,:stock,:location,:badge,:badge_kind,:thumb,:thumb_class)');
foreach (DemoData::products() as $p) {
    $stmt->execute([
        'id' => $p['id'],
        'title' => $p['title'],
        'spec' => $p['spec'],
        'price' => $p['price'],
        'unit' => $p['unit'] ?? '个',
        'stock' => $p['stock'],
        'location' => $p['location'] ?? '',
        'badge' => $p['badge'] ?? '',
        'badge_kind' => $p['badgeKind'] ?? '',
        'thumb' => $p['thumb'] ?? $p['title'],
        'thumb_class' => $p['thumbClass'] ?? '',
    ]);
}

$repo = new PdoMaterialRepository($pdo);
echo 'sqlite: ' . $file . PHP_EOL;
echo 'materials: ' . count($repo->all()) . PHP_EOL;

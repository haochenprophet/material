<?php
declare(strict_types=1);

/**
 * 物料图片上传/主图/软删测试（SQLite + 临时目录）。
 * 用法：php platform/tests/image-upload.php
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

echo '物格云 image upload tests · v' . APP_VERSION . PHP_EOL . PHP_EOL;
$pdo = new PDO('sqlite::memory:');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->exec('CREATE TABLE material (id INTEGER PRIMARY KEY, part_number TEXT, code TEXT, name TEXT, deleted_at TEXT)');
$pdo->exec('CREATE TABLE material_attachment (id INTEGER PRIMARY KEY AUTOINCREMENT, material_id INT, type TEXT, path TEXT, mime TEXT, size INT, sha256 TEXT, is_primary INT, sort INT, created_at TEXT, deleted_at TEXT)');
$pdo->exec("INSERT INTO material VALUES (1,'TE-1','C1','TE',NULL)");
$root = sys_get_temp_dir() . '/dsh-image-' . bin2hex(random_bytes(4));
mkdir($root, 0777, true);
$storage = new LocalImageStorage($root);
$repo = new PdoMaterialImageRepository($pdo);
$service = new ImageUploadService($pdo, $storage, $repo);
$tmp = $root . '/src.png';
$im = imagecreatetruecolor(32, 32);
imagepng($im, $tmp);
$file = ['name' => 'x.png', 'tmp_name' => $tmp, 'error' => UPLOAD_ERR_OK, 'size' => filesize($tmp)];
$one = $service->handle(1, $file, true);
check('上传返回 id', ($one['id'] ?? 0) > 0);
check('文件已写入', is_file($root . str_replace('/uploads/materials', '', (string) $one['path'])));
check('主图标记', $repo->forMaterial(1)[0]['is_primary'] === 1);
$two = $service->handle(1, $file, false);
check('第二张非主图', $repo->forMaterial(1)[1]['is_primary'] === 0);
check('切换主图', $repo->setPrimary(1, (int) $two['id']) === true && $repo->forMaterial(1)[0]['id'] === $two['id']);
check('软删不删文件', $repo->softDelete(1, (int) $two['id']) === true && is_file($root . str_replace('/uploads/materials', '', (string) $two['path'])));
check('软删后剩余主图', count($repo->forMaterial(1)) === 1 && $repo->forMaterial(1)[0]['id'] === $one['id']);
check('非图片拒绝', (static function () use ($service, $tmp) { $bad = $tmp . '.txt'; file_put_contents($bad, 'not image'); try { $service->handle(1, ['name' => 'x.txt', 'tmp_name' => $bad, 'error' => 0, 'size' => 9]); return false; } catch (Throwable) { return true; } })());

echo PHP_EOL . '结果：PASS ' . $pass . ' / FAIL ' . $fail . PHP_EOL;
exit($fail > 0 ? 1 : 0);

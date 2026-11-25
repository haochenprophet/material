<?php
declare(strict_types=1);

/**
 * 本地图片存储：public/uploads/materials/YYYY/MM/<random>.<ext>
 * 返回 /uploads/... 公共路径；不覆盖。
 */
final class LocalImageStorage implements ImageStorage
{
    public function __construct(private readonly string $root = PUBLIC_PATH . '/uploads/materials')
    {
    }

    public function put(string $contents, string $extension): string
    {
        $extension = strtolower(preg_replace('/[^a-z0-9]/i', '', $extension) ?: 'bin');
        if (!in_array($extension, ['jpg', 'png', 'webp'], true)) {
            throw new RuntimeException('图片扩展名不允许');
        }
        $key = date('Y/m') . '/' . bin2hex(random_bytes(10)) . '.' . $extension;
        $path = $this->root . '/' . $key;
        $dir = dirname($path);
        if (!is_dir($dir) && !mkdir($dir, 0777, true) && !is_dir($dir)) {
            throw new RuntimeException('无法创建图片目录');
        }
        if (file_put_contents($path, $contents, LOCK_EX) === false) {
            throw new RuntimeException('图片写入失败');
        }
        return '/uploads/materials/' . $key;
    }
}

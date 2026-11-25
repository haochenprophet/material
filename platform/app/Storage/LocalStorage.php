<?php
declare(strict_types=1);

/**
 * 本地存储：storage/uploads/YYYY/MM/<random>.<ext>。
 * 只新增文件；不删除、不覆盖。
 */
final class LocalStorage implements Storage
{
    public function __construct(private readonly string $root = STORAGE_PATH . '/uploads')
    {
    }

    public function put(string $contents, string $extension = 'csv'): string
    {
        $extension = strtolower(preg_replace('/[^a-z0-9]/i', '', $extension) ?: 'bin');
        if ($extension === '') {
            $extension = 'bin';
        }
        $key = date('Y/m') . '/' . bin2hex(random_bytes(8)) . '.' . $extension;
        $path = $this->root . '/' . $key;
        $dir = dirname($path);
        if (!is_dir($dir) && !mkdir($dir, 0777, true) && !is_dir($dir)) {
            throw new RuntimeException('无法创建上传目录');
        }
        if (file_put_contents($path, $contents, LOCK_EX) === false) {
            throw new RuntimeException('无法写入上传文件');
        }
        return $key;
    }
}

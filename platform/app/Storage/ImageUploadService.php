<?php
declare(strict_types=1);

/**
 * 物料图片上传：类型/大小/图片头校验 → 存储 → material_attachment。
 */
final class ImageUploadService
{
    public const MAX_BYTES = 5242880;

    public function __construct(
        private readonly PDO $pdo,
        private readonly ImageStorage $storage,
        private readonly PdoMaterialImageRepository $repo,
    ) {
    }

    /**
     * @param array{name?:string,type?:string,tmp_name?:string,error?:int,size?:int} $file
     * @return array<string, mixed>
     */
    public function handle(int $materialId, array $file, bool $primary = false): array
    {
        $stmt = $this->pdo->prepare('SELECT id FROM material WHERE id=? AND deleted_at IS NULL');
        $stmt->execute([$materialId]);
        if ($stmt->fetchColumn() === false) {
            throw new InvalidArgumentException('物料不存在');
        }
        if ((int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new RuntimeException('上传失败');
        }
        $size = (int) ($file['size'] ?? 0);
        if ($size <= 0 || $size > self::MAX_BYTES) {
            throw new RuntimeException('图片不得超过 5MB');
        }
        $tmp = (string) ($file['tmp_name'] ?? '');
        if ($tmp === '' || !is_file($tmp) || !is_readable($tmp)) {
            throw new RuntimeException('临时文件不可读');
        }
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = (string) $finfo->file($tmp);
        $map = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
        if (!isset($map[$mime])) {
            throw new RuntimeException('只支持 JPG/PNG/WebP');
        }
        $info = @getimagesize($tmp);
        if ($info === false || (int) $info[0] < 16 || (int) $info[1] < 16) {
            throw new RuntimeException('图片尺寸过小或无效');
        }
        $contents = (string) file_get_contents($tmp);
        $path = $this->storage->put($contents, $map[$mime]);
        $id = $this->repo->add($materialId, $path, $mime, $size, hash('sha256', $contents), $primary);
        return ['id' => $id, 'path' => $path, 'mime' => $mime, 'size' => $size, 'is_primary' => $primary ? 1 : 0];
    }
}

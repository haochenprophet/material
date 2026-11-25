<?php
declare(strict_types=1);

/**
 * BOM 文件上传：校验大小/扩展名 → 读取 → 解析 → 存储原件。
 * 只接受 CSV/TXT；xlsx 必须走后续 PhpSpreadsheet/队列，不在请求内解析。
 */
final class BomUploadService
{
    public const MAX_BYTES = 1048576;

    public function __construct(private readonly Storage $storage)
    {
    }

    /**
     * @param array{name?:string,type?:string,tmp_name?:string,error?:int,size?:int} $file
     * @return array{ok:bool, code?:string, message?:string, count?:int, key?:string, errors?:array<int,array{line:int,message:string}>}
     */
    public function handle(array $file): array
    {
        $error = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($error !== UPLOAD_ERR_OK) {
            return ['ok' => false, 'code' => 'UPLOAD_ERROR', 'message' => '上传失败（错误码 ' . $error . '）'];
        }
        $size = (int) ($file['size'] ?? 0);
        if ($size <= 0 || $size > self::MAX_BYTES) {
            return ['ok' => false, 'code' => 'FILE_TOO_LARGE', 'message' => '文件必须 ≤1MB'];
        }
        $name = (string) ($file['name'] ?? '');
        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        if (!in_array($ext, ['csv', 'txt'], true)) {
            return ['ok' => false, 'code' => 'BAD_EXTENSION', 'message' => '只支持 .csv / .txt（.xlsx 后续支持）'];
        }
        $tmp = (string) ($file['tmp_name'] ?? '');
        if ($tmp === '' || !is_file($tmp) || !is_readable($tmp)) {
            return ['ok' => false, 'code' => 'NO_FILE', 'message' => '临时文件不可读'];
        }
        $contents = (string) file_get_contents($tmp);
        if ($contents === '') {
            return ['ok' => false, 'code' => 'EMPTY', 'message' => '文件为空'];
        }
        $parsed = BomCsvParser::parse($contents);
        if ($parsed['errors'] !== []) {
            return [
                'ok' => false,
                'code' => 'PARSE_FAILED',
                'message' => 'BOM 解析有错误',
                'errors' => $parsed['errors'],
            ];
        }
        $key = $this->storage->put($contents, 'csv');
        return ['ok' => true, 'count' => $parsed['count'], 'key' => $key, 'errors' => []];
    }
}

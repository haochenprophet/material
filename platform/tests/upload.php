<?php
declare(strict_types=1);

/**
 * 上传/存储测试。用法：php platform/tests/upload.php
 * 会在 storage/uploads-test 下新增文件，不删除。
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
    if ($cond) {
        $pass++;
        echo "  PASS  {$name}" . PHP_EOL;
    } else {
        $fail++;
        echo "  FAIL  {$name}" . ($detail !== '' ? "  ({$detail})" : '') . PHP_EOL;
    }
}

$root = STORAGE_PATH . '/uploads-test';
$storage = new LocalStorage($root);
$service = new BomUploadService($storage);

$tmp = tempnam(sys_get_temp_dir(), 'bom_');
$contents = "mpn,qty,desc\nTE,200,conn\nGRM,5000,cap\n";
file_put_contents($tmp, $contents);

$good = $service->handle(['name' => 'bom.csv', 'tmp_name' => $tmp, 'size' => strlen($contents), 'error' => UPLOAD_ERR_OK]);
check('有效 CSV 通过', ($good['ok'] ?? false) === true && ($good['count'] ?? 0) === 2);
check('返回存储 key', is_string($good['key'] ?? null) && str_ends_with((string) $good['key'], '.csv'));
check('原件落盘', is_file($root . '/' . ($good['key'] ?? '')));

$second = $service->handle(['name' => 'bom2.csv', 'tmp_name' => $tmp, 'size' => strlen($contents), 'error' => UPLOAD_ERR_OK]);
check('key 不重复', ($second['key'] ?? '') !== ($good['key'] ?? ''));

$badExt = $service->handle(['name' => 'bom.xlsx', 'tmp_name' => $tmp, 'size' => strlen($contents), 'error' => UPLOAD_ERR_OK]);
check('拒绝 xlsx', ($badExt['ok'] ?? true) === false && ($badExt['code'] ?? '') === 'BAD_EXTENSION');

$tooBig = $service->handle(['name' => 'bom.csv', 'tmp_name' => $tmp, 'size' => BomUploadService::MAX_BYTES + 1, 'error' => UPLOAD_ERR_OK]);
check('拒绝超 1MB', ($tooBig['ok'] ?? true) === false && ($tooBig['code'] ?? '') === 'FILE_TOO_LARGE');

$badTmp = $service->handle(['name' => 'bom.csv', 'tmp_name' => '/no/such/file', 'size' => 10, 'error' => UPLOAD_ERR_OK]);
check('拒绝不可读临时文件', ($badTmp['ok'] ?? true) === false && ($badTmp['code'] ?? '') === 'NO_FILE');

$parseBad = $service->handle(['name' => 'bom.csv', 'tmp_name' => $tmp, 'size' => strlen($contents), 'error' => UPLOAD_ERR_OK]);
check('解析错误可返回', ($parseBad['ok'] ?? false) === true);

$badContent = tempnam(sys_get_temp_dir(), 'bom_');
file_put_contents($badContent, "mpn,qty\nTE,0\n");
$badParse = $service->handle(['name' => 'bad.csv', 'tmp_name' => $badContent, 'size' => filesize($badContent), 'error' => UPLOAD_ERR_OK]);
check('数量 0 拒绝存储', ($badParse['ok'] ?? true) === false && ($badParse['code'] ?? '') === 'PARSE_FAILED');

$uploadError = $service->handle(['name' => 'bom.csv', 'tmp_name' => $tmp, 'size' => 10, 'error' => UPLOAD_ERR_INI_SIZE]);
check('上传错误码处理', ($uploadError['ok'] ?? true) === false && ($uploadError['code'] ?? '') === 'UPLOAD_ERROR');

echo PHP_EOL . '结果：PASS ' . $pass . ' / FAIL ' . $fail . PHP_EOL;
exit($fail > 0 ? 1 : 0);

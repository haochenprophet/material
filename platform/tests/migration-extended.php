<?php
declare(strict_types=1);

/**
 * 迁移增量静态测试。用法：php platform/tests/migration-extended.php
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

echo '物格云 migration extended tests · v' . APP_VERSION . PHP_EOL . PHP_EOL;
$files = ['007_commerce.sql', '008_reputation.sql', '009_merchant_kyc.sql', '010_material_image.sql', '011_sandbox_payment.sql', '012_commission_version.sql', '013_api_auth.sql'];
check('7 个增量文件', count($files) === 7);
foreach ($files as $f) {
    check('文件 ' . $f, is_file(PLATFORM_PATH . '/database/' . $f));
}
$all = '';
foreach ($files as $f) {
    $all .= (string) file_get_contents(PLATFORM_PATH . '/database/' . $f);
}
check('无 DROP TABLE', !preg_match('/\bDROP\s+TABLE\b/i', $all));
check('无 DELETE FROM', !preg_match('/\bDELETE\s+FROM\b/i', $all));
check('rebuild 脚本存在', is_file(PLATFORM_PATH . '/bin/schema-rebuild.php') && str_contains((string) file_get_contents(PLATFORM_PATH . '/bin/schema-rebuild.php'), '007_commerce.sql'));
check('增量含信用/支付/API', str_contains($all, 'material_review') && str_contains($all, 'sandbox_payment') && str_contains($all, 'api_token'));

echo PHP_EOL . '结果：PASS ' . $pass . ' / FAIL ' . $fail . PHP_EOL;
exit($fail > 0 ? 1 : 0);

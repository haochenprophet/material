<?php
declare(strict_types=1);

/**
 * 全链路回归（HTTP）。服务未启动时 SKIP，不阻塞离线。
 * 用法：php platform/tests/regression.php
 * 可选：API_TEST_TOKEN=sk_... 验证 8097 API 鉴权。
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    echo 'CLI only';
    exit(1);
}
require dirname(__DIR__) . '/app/bootstrap.php';

$pass = 0;
$fail = 0;
$skip = 0;
function check(string $name, bool $cond, string $detail = ''): void
{
    global $pass, $fail;
    if ($cond) { $pass++; echo "  PASS  {$name}" . PHP_EOL; }
    else { $fail++; echo "  FAIL  {$name}" . ($detail !== '' ? "  ({$detail})" : '') . PHP_EOL; }
}
function fetch(string $url, array $headers = []): array
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 5, CURLOPT_HTTPHEADER => $headers]);
    $body = curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    curl_close($ch);
    return ['code' => $code, 'body' => (string) $body];
}

echo '物格云 regression tests · v' . APP_VERSION . PHP_EOL . PHP_EOL;
$demo = fetch('http://127.0.0.1:8091/health');
if ($demo['code'] !== 200) {
    echo 'SKIP: 8091 未启动' . PHP_EOL;
    exit(0);
}
check('8091 health', $demo['code'] === 200 && str_contains($demo['body'], '"status": "ok"'));
check('8091 首页', fetch('http://127.0.0.1:8091/')['code'] === 200);
check('8091 详情', fetch('http://127.0.0.1:8091/p/p1')['code'] === 200);
check('8091 BOM', fetch('http://127.0.0.1:8091/bom')['code'] === 200);
check('8091 登录', fetch('http://127.0.0.1:8091/login')['code'] === 200);
check('8091 PDA 未登录 302', fetch('http://127.0.0.1:8091/pda')['code'] === 302);
check('8091 后台未登录 302', fetch('http://127.0.0.1:8091/admin/orders')['code'] === 302);

$db = fetch('http://127.0.0.1:8095/health');
if ($db['code'] === 200) {
    check('8095 health', true);
    check('8095 DB 首页', str_contains(fetch('http://127.0.0.1:8095/')['body'], 'TE 170364-1 connector'));
    check('8095 DB 详情', fetch('http://127.0.0.1:8095/p/1')['code'] === 200);
    check('8095 商城', fetch('http://127.0.0.1:8095/shops')['code'] === 200);
    check('8095 商家商城', fetch('http://127.0.0.1:8095/shop/hqb-industrial')['code'] === 200);
    check('8095 商城点评', str_contains(fetch('http://127.0.0.1:8095/shop/hqb-industrial')['body'], '全部商品点评'));
    check('8095 品类页电阻', fetch('http://127.0.0.1:8095/category/resistor')['code'] === 200);
    check('8095 品类页连接器', str_contains(fetch('http://127.0.0.1:8095/category/connector')['body'], '连接器'));
    check('8095 品类页非法 404', fetch('http://127.0.0.1:8095/category/no-such')['code'] === 404);
    $catAll = true;
    foreach (array_keys(MaterialCategory::all()) as $slug) {
        if (fetch('http://127.0.0.1:8095/category/' . rawurlencode($slug))['code'] !== 200) { $catAll = false; break; }
    }
    check('8095 13 品类页全部 200', $catAll);
} else {
    echo 'SKIP: 8095 未启动' . PHP_EOL;
    $skip++;
}

$auth = fetch('http://127.0.0.1:8096/login');
if ($auth['code'] === 200) {
    check('8096 DB 登录页', str_contains($auth['body'], '登录'));
    check('8096 后台未登录 302', fetch('http://127.0.0.1:8096/admin/orders')['code'] === 302);
} else {
    echo 'SKIP: 8096 未启动' . PHP_EOL;
    $skip++;
}

$api = fetch('http://127.0.0.1:8097/api/v1/health');
if ($api['code'] === 200) {
    check('8097 health 公开', true);
    check('8097 API 无 token 401', fetch('http://127.0.0.1:8097/api/v1/materials')['code'] === 401);
    $token = (string) getenv('API_TEST_TOKEN');
    if ($token !== '') {
        check('8097 API 有 token 200', fetch('http://127.0.0.1:8097/api/v1/materials', ['Authorization: Bearer ' . $token])['code'] === 200);
    } else {
        echo 'SKIP: API_TEST_TOKEN 未设置' . PHP_EOL;
        $skip++;
    }
} else {
    echo 'SKIP: 8097 未启动' . PHP_EOL;
    $skip++;
}

echo PHP_EOL . '结果：PASS ' . $pass . ' / FAIL ' . $fail . ' / SKIP ' . $skip . PHP_EOL;
exit($fail > 0 ? 1 : 0);

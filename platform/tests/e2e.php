<?php
declare(strict_types=1);

/**
 * HTTP E2E：登录 → /bom → 上传 CSV → 解析 → API 报价 → health/trace。
 * 用法：php platform/tests/e2e.php
 * 依赖本地已启动：php -S 127.0.0.1:8091 ...
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    echo 'CLI only';
    exit(1);
}

$base = rtrim((string) (getenv('E2E_BASE_URL') ?: 'http://127.0.0.1:8091'), '/');
$dir = dirname(__DIR__) . '/storage/e2e';
if (!is_dir($dir) && !mkdir($dir, 0777, true) && !is_dir($dir)) {
    fwrite(STDERR, "无法创建 {$dir}\n");
    exit(1);
}
$cookie = $dir . '/cookies-' . getmypid() . '-' . bin2hex(random_bytes(4)) . '.txt';

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

/** @return array{status:int, body:string} */
function http(string $method, string $url, mixed $body = null, bool $multipart = false): array
{
    global $cookie;
    $ch = curl_init($url);
    $isJson = is_string($body) && str_starts_with(ltrim($body), '{');
    $headers = $multipart ? [] : ($isJson ? ['Content-Type: application/json'] : ['Content-Type: application/x-www-form-urlencoded']);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS => 5,
        CURLOPT_COOKIEJAR => $cookie,
        CURLOPT_COOKIEFILE => $cookie,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_HTTPHEADER => $headers,
    ]);
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
    }
    if ($body !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
    }
    $response = (string) curl_exec($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['status' => $status, 'body' => $response];
}

function csrf(string $html): string
{
    return preg_match('/name="_csrf" value="([^"]+)"/', $html, $m) === 1 ? $m[1] : '';
}

echo '物格云 HTTP E2E · ' . $base . PHP_EOL . PHP_EOL;
$health = @file_get_contents($base . '/health');
if ($health === false) {
    echo 'SKIP：服务未启动（先启动 8091）' . PHP_EOL;
    exit(0);
}

$healthJson = json_decode($health, true);
check('health 200/json', is_array($healthJson) && ($healthJson['status'] ?? '') === 'ok');
check('health 有版本/release', isset($healthJson['version'], $healthJson['release']));

$loginPage = http('GET', $base . '/login');
$token = csrf($loginPage['body']);
check('登录页 CSRF', $token !== '');
$login = http('POST', $base . '/login', http_build_query([
    '_csrf' => $token,
    'account' => 'seller@demo',
    'password' => 'demo123',
    'next' => '/seller',
]));
check('seller 登录后到卖家页', $login['status'] === 200 && (str_contains($login['body'], '提报物料') || str_contains($login['body'], '卖家工作台')), 'status=' . $login['status'] . ' body=' . substr(strip_tags($login['body']), 0, 200));

$bomPage = http('GET', $base . '/bom');
$bomToken = csrf($bomPage['body']);
check('BOM 页有上传表单', $bomToken !== '' && str_contains($bomPage['body'], '/bom/upload'));

$csvFile = $dir . '/bom.csv';
file_put_contents($csvFile, "mpn,qty,desc\nTE 170364-1,200,4Pin\nGRM188R71H104KA93D,5000,0402\n");
$upload = http('POST', $base . '/bom/upload', [
    '_csrf' => $bomToken,
    'file' => new CURLFile($csvFile, 'text/csv', 'bom.csv'),
], true);
check('UI 上传解析 2 行', str_contains($upload['body'], '已解析 2 行'), 'status=' . $upload['status'] . ' body=' . substr(strip_tags($upload['body']), 0, 200));

$quote = http('POST', $base . '/api/v1/quotes', json_encode([
    'lines' => [['qty' => 10, 'unit' => 18.50], ['qty' => 100, 'unit' => 0.12]],
    'options' => ['qc_fee' => 20, 'fulfillment_fee' => 10, 'shipping' => 5, 'service_rate' => 0.03, 'tax_rate' => 0.13],
], JSON_UNESCAPED_UNICODE));
$quoteJson = json_decode($quote['body'], true);
check('API 报价 200', $quote['status'] === 200);
check('API 报价小计 237.91', abs((float) ($quoteJson['data']['subtotal'] ?? 0) - 237.91) < 0.001);

$order = http('POST', $base . '/api/v1/orders', json_encode([
    'lines' => [['mpn' => 'TE 170364-1', 'qty' => 10, 'unit' => 18.50]],
    'options' => ['qc_fee' => 20, 'fulfillment_fee' => 10, 'shipping' => 5, 'service_rate' => 0.03, 'tax_rate' => 0.13],
], JSON_UNESCAPED_UNICODE));
$orderJson = json_decode($order['body'], true);
$orderData = is_array($orderJson['data'] ?? null) ? $orderJson['data'] : [];
check('API 下单 200', $order['status'] === 200);
check('API 订单待支付', ($orderData['status'] ?? '') === 'pending_payment' && str_starts_with((string) ($orderData['id'] ?? ''), 'SO-'));
check('API 订单小计 225.55', abs((float) ($orderData['pricing']['subtotal'] ?? 0) - 225.55) < 0.001);

$orderPage = http('GET', $base . '/orders/' . rawurlencode((string) ($orderData['id'] ?? '')));
check('订单页显示订单号', $orderPage['status'] === 200 && str_contains($orderPage['body'], (string) ($orderData['id'] ?? '___')));
check('订单页显示待支付', str_contains($orderPage['body'], '待支付'));

$settle = http('POST', $base . '/api/v1/settlements/preview', json_encode([
    'order' => $orderData,
    'options' => ['commission_rate' => 0.20, 'qc' => 20, 'logistics' => 5],
], JSON_UNESCAPED_UNICODE));
$settleJson = json_decode($settle['body'], true);
check('API 结算 200', $settle['status'] === 200);
check('API 卖家应结 123', abs((float) ($settleJson['data']['seller_net'] ?? 0) - 123.0) < 0.001);

$parse = http('POST', $base . '/api/v1/bom/parse', json_encode(['csv' => "mpn,qty\nTE,200\n"]));
$parseJson = json_decode($parse['body'], true);
check('API BOM 解析 1 行', $parse['status'] === 200 && ($parseJson['data']['count'] ?? 0) === 1);

$trace = http('GET', $base . '/trace');
check('trace 含版本', str_contains($trace['body'], (string) ($healthJson['version'] ?? '')));

echo PHP_EOL . '结果：PASS ' . $pass . ' / FAIL ' . $fail . PHP_EOL;
exit($fail > 0 ? 1 : 0);

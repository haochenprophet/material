<?php
declare(strict_types=1);

/**
 * API v1 契约测试（无 HTTP 依赖；通过 withBody 注入 POST 体）。
 * 用法：php platform/tests/api.php
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    echo 'CLI only';
    exit(1);
}

require dirname(__DIR__) . '/app/bootstrap.php';

ob_start();

$pass = 0;
$fail = 0;
$failures = [];

function ok(string $name): void
{
    global $pass;
    $pass++;
    echo "  PASS  {$name}" . PHP_EOL;
}

function bad(string $name, string $detail = ''): void
{
    global $fail, $failures;
    $fail++;
    $failures[] = $name . ($detail !== '' ? ': ' . $detail : '');
    echo "  FAIL  {$name}" . ($detail !== '' ? "  ({$detail})" : '') . PHP_EOL;
}

function check(string $name, bool $condition, string $detail = ''): void
{
    $condition ? ok($name) : bad($name, $detail);
}

/**
 * @param array<string, mixed>|null $body
 * @return array{status:int, json:array<string,mixed>|null, raw:string}
 */
function callApi(string $method, string $path, ?array $body = null): array
{
    Api::withBody($body);
    ob_start();
    Api::dispatch($method, $path);
    $raw = (string) ob_get_clean();
    return [
        'status' => http_response_code() ?: 200,
        'json' => json_decode($raw, true),
        'raw' => $raw,
    ];
}

echo '物格云 API tests · v' . APP_VERSION . PHP_EOL . PHP_EOL;

$health = callApi('GET', '/api/v1/health');
check('health 200', $health['status'] === 200);
check('health envelope', is_array($health['json']) && ($health['json']['error'] ?? null) === null && isset($health['json']['meta']['request_id']));
check('health version', ($health['json']['data']['version'] ?? '') === APP_VERSION);

$list = callApi('GET', '/api/v1/materials');
check('materials 200', $list['status'] === 200);
check('materials 4 条', is_array($list['json']['data'] ?? null) && count($list['json']['data']) === 4);

$detail = callApi('GET', '/api/v1/materials/p1');
check('material p1 200', $detail['status'] === 200);
check('material p1 有阶梯价', isset($detail['json']['data']['tier']) && count($detail['json']['data']['tier']) === 4);

$missing = callApi('GET', '/api/v1/materials/nope');
check('material 404', $missing['status'] === 404 && ($missing['json']['error']['code'] ?? '') === 'NOT_FOUND');

$order = callApi('GET', '/api/v1/orders/SO-20261124-0081');
check('order 200', $order['status'] === 200 && ($order['json']['data']['no'] ?? '') === 'SO-20261124-0081');

$state = callApi('GET', '/api/v1/state');
check('state 6 实体', is_array($state['json']['data'] ?? null) && count($state['json']['data']) === 6);

$quote = callApi('POST', '/api/v1/quotes', [
    'lines' => [['qty' => 10, 'unit' => 18.50], ['qty' => 100, 'unit' => 0.12]],
    'options' => ['qc_fee' => 20, 'fulfillment_fee' => 10, 'shipping' => 5, 'service_rate' => 0.03, 'tax_rate' => 0.13],
]);
check('quote 200', $quote['status'] === 200);
check('quote subtotal 237.91', abs((float) ($quote['json']['data']['subtotal'] ?? 0) - 237.91) < 0.001);

$match = callApi('POST', '/api/v1/bom/match', [
    'lines' => [
        ['mpn' => 'TE', 'qty' => 200, 'offers' => [['supplier' => 'SZ', 'unit' => 18.50, 'qc' => 20]]],
        ['mpn' => 'ADUM', 'qty' => 80, 'offers' => []],
    ],
]);
check('match 200', $match['status'] === 200);
check('match matched/shortage', ($match['json']['data']['matched'] ?? -1) === 1 && ($match['json']['data']['shortage'] ?? -1) === 1);

$parse = callApi('POST', '/api/v1/bom/parse', ['csv' => "mpn,qty\nTE 170364-1,200\n"]);
check('bom parse 200', $parse['status'] === 200);
check('bom parse count 1', ($parse['json']['data']['count'] ?? 0) === 1);

$badParse = callApi('POST', '/api/v1/bom/parse', ['csv' => "mpn,qty\nTE,0\n"]);
check('bom parse 422', $badParse['status'] === 422 && ($badParse['json']['error']['code'] ?? '') === 'BOM_PARSE_FAILED');

$order = callApi('POST', '/api/v1/orders', [
    'lines' => [['qty' => 10, 'unit' => 18.50]],
    'options' => ['qc_fee' => 20, 'fulfillment_fee' => 10, 'shipping' => 5, 'service_rate' => 0.03, 'tax_rate' => 0.13],
]);
check('order 200', $order['status'] === 200 && str_starts_with((string) ($order['json']['data']['id'] ?? ''), 'SO-'));
check('order total 225.55', abs((float) ($order['json']['data']['pricing']['subtotal'] ?? 0) - 225.55) < 0.001);

$settlePreview = callApi('POST', '/api/v1/settlements/preview', [
    'order' => ['id' => 'SO-TEST', 'pricing' => ['goods' => 185]],
    'options' => ['commission_rate' => 0.20, 'qc' => 20, 'logistics' => 5],
]);
check('settlement 200', $settlePreview['status'] === 200);
check('settlement seller net 123', abs((float) ($settlePreview['json']['data']['seller_net'] ?? 0) - 123.0) < 0.001);

$badQuote = callApi('POST', '/api/v1/quotes', ['lines' => []]);
check('quote empty 422', $badQuote['status'] === 422 && ($badQuote['json']['error']['code'] ?? '') === 'VALIDATION_ERROR');

$unknown = callApi('GET', '/api/v1/nope');
check('unknown 404', $unknown['status'] === 404);

echo PHP_EOL . '结果：PASS ' . $pass . ' / FAIL ' . $fail . PHP_EOL;
if ($fail > 0) {
    foreach ($failures as $f) {
        echo ' - ' . $f . PHP_EOL;
    }
    $out = (string) ob_get_clean();
    echo $out;
    exit(1);
}
$out = (string) ob_get_clean();
echo $out;
exit(0);

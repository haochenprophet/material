<?php
declare(strict_types=1);

/**
 * 只读/计算型 API v1。首版不接数据库，返回 DemoData / Domain 计算结果。
 * 统一响应：{data, meta:{request_id}, error}
 */
final class Api
{
    /** @var array<string, mixed>|null 测试注入，生产恒为 null */
    private static ?array $testBody = null;

    /** @param array<string, mixed>|null $body */
    public static function withBody(?array $body): void
    {
        self::$testBody = $body;
    }

    public static function dispatch(string $method, string $path): void
    {
        try {
            if ($path !== '/api/v1/health') {
                try {
                    (new ApiAuth(Database::configured() ? Database::pdo() : null))->guard($method === 'GET' ? 'read' : 'write');
                } catch (ApiAuthException $e) {
                    foreach ($e->headers as $k => $v) {
                        header($k . ': ' . $v);
                    }
                    self::fail('AUTH_ERROR', $e->getMessage(), $e->status);
                    return;
                }
            }
            if ($method === 'GET' && $path === '/api/v1/health') {
                self::ok([
                    'status' => 'ok',
                    'version' => APP_VERSION,
                    'release' => APP_RELEASE_ID,
                    'php' => PHP_VERSION,
                ]);
                return;
            }
            if ($method === 'GET' && $path === '/api/v1/materials') {
                self::ok(DemoData::products());
                return;
            }
            if ($method === 'GET' && preg_match('#^/api/v1/materials/([^/]+)$#', $path, $m)) {
                $id = rawurldecode($m[1]);
                $product = DemoData::product($id);
                if ($product === null) {
                    self::fail('NOT_FOUND', '物料不存在', 404);
                    return;
                }
                self::ok($id === 'p1' ? DemoData::detail() : $product);
                return;
            }
            if ($method === 'GET' && preg_match('#^/api/v1/orders/([^/]+)$#', $path, $m)) {
                $order = DemoData::order(rawurldecode($m[1]));
                if ($order === null) {
                    self::fail('NOT_FOUND', '订单不存在', 404);
                    return;
                }
                self::ok($order);
                return;
            }
            if ($method === 'GET' && $path === '/api/v1/state') {
                self::ok(StateMachine::all());
                return;
            }
            if ($method === 'POST' && $path === '/api/v1/orders') {
                $body = self::body();
                $lines = $body['lines'] ?? null;
                if (!is_array($lines) || $lines === []) {
                    self::fail('VALIDATION_ERROR', 'lines 必须是非空数组', 422);
                    return;
                }
                try {
                    $order = (new OrderService())->create($lines, is_array($body['options'] ?? null) ? $body['options'] : []);
                    (new OrderStore())->put($order);
                } catch (InvalidArgumentException $e) {
                    self::fail('VALIDATION_ERROR', $e->getMessage(), 422);
                    return;
                }
                self::ok($order);
                return;
            }
            if ($method === 'POST' && $path === '/api/v1/settlements/preview') {
                $body = self::body();
                $order = $body['order'] ?? null;
                if (!is_array($order) || !isset($order['pricing']['goods'])) {
                    self::fail('VALIDATION_ERROR', 'order.pricing.goods 必填', 422);
                    return;
                }
                $settlement = (new SettlementService())->fromOrder($order, is_array($body['options'] ?? null) ? $body['options'] : []);
                self::ok($settlement);
                return;
            }
            if ($method === 'POST' && $path === '/api/v1/bom/upload') {
                if (!isset($_FILES['file']) || !is_array($_FILES['file'])) {
                    self::fail('VALIDATION_ERROR', '缺少 file 上传字段', 422);
                    return;
                }
                $service = new BomUploadService(new LocalStorage());
                $result = $service->handle($_FILES['file']);
                if (!$result['ok']) {
                    self::fail((string) ($result['code'] ?? 'UPLOAD_FAILED'), (string) ($result['message'] ?? '上传失败'), 422, ['errors' => $result['errors'] ?? []]);
                    return;
                }
                self::ok($result);
                return;
            }
            if ($method === 'POST' && $path === '/api/v1/bom/parse') {
                $body = self::body();
                $csv = $body['csv'] ?? null;
                if (!is_string($csv) || trim($csv) === '') {
                    self::fail('VALIDATION_ERROR', 'csv 必须是非空字符串', 422);
                    return;
                }
                $result = BomCsvParser::parse($csv);
                if ($result['errors'] !== []) {
                    self::fail('BOM_PARSE_FAILED', 'BOM 解析有错误', 422, ['errors' => $result['errors']]);
                    return;
                }
                self::ok($result);
                return;
            }
            if ($method === 'POST' && $path === '/api/v1/bom/match') {
                $body = self::body();
                $lines = $body['lines'] ?? null;
                if (!is_array($lines) || $lines === []) {
                    self::fail('VALIDATION_ERROR', 'lines 必须是非空数组', 422);
                    return;
                }
                self::ok(BomMatcher::match($lines));
                return;
            }
            if ($method === 'POST' && $path === '/api/v1/quotes') {
                $body = self::body();
                $lines = $body['lines'] ?? null;
                if (!is_array($lines) || $lines === []) {
                    self::fail('VALIDATION_ERROR', 'lines 必须是非空数组', 422);
                    return;
                }
                $options = is_array($body['options'] ?? null) ? $body['options'] : [];
                self::ok(Pricing::quote($lines, $options));
                return;
            }
            self::fail('NOT_FOUND', '接口不存在', 404);
        } catch (Throwable $e) {
            self::fail('INTERNAL', APP_DEBUG ? $e->getMessage() : '服务异常', 500);
        }
    }

    /** @return array<string, mixed> */
    private static function body(): array
    {
        if (self::$testBody !== null) {
            return self::$testBody;
        }
        $raw = (string) file_get_contents('php://input');
        if ($raw === '') {
            return $_POST;
        }
        $data = json_decode($raw, true);
        if (!is_array($data)) {
            throw new InvalidArgumentException('请求体必须是 JSON 对象');
        }
        return $data;
    }

    private static function ok(mixed $data, int $status = 200): void
    {
        Cui::json([
            'data' => $data,
            'meta' => ['request_id' => APP_REQUEST_ID, 'version' => APP_VERSION, 'release' => APP_RELEASE_ID],
            'error' => null,
        ], $status);
    }

    private static function fail(string $code, string $message, int $status, array $details = []): void
    {
        Cui::json([
            'data' => null,
            'meta' => ['request_id' => APP_REQUEST_ID, 'version' => APP_VERSION, 'release' => APP_RELEASE_ID],
            'error' => ['code' => $code, 'message' => $message, 'details' => $details],
        ], $status);
    }
}

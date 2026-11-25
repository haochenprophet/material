<?php
declare(strict_types=1);

/**
 * API 鉴权/限流。默认关闭（本地演示）；API_REQUIRE_TOKEN=1 时启用。
 */
final class ApiAuth
{
    public function __construct(private readonly ?PDO $pdo = null)
    {
    }

    public static function enabled(): bool
    {
        return in_array(strtolower((string) getenv('API_REQUIRE_TOKEN')), ['1', 'true', 'yes', 'on'], true);
    }

    /** @return array<string, mixed>|null */
    public function guard(string $scope = 'read'): ?array
    {
        if (!self::enabled()) {
            return null;
        }
        if ($this->pdo === null) {
            throw new ApiAuthException(503, 'API 鉴权未配置数据库');
        }
        $raw = $this->bearer();
        if ($raw === '') {
            throw new ApiAuthException(401, '缺少 Bearer token', ['WWW-Authenticate' => 'Bearer']);
        }
        $repo = new ApiTokenRepository($this->pdo);
        $token = $repo->findByToken($raw);
        if ($token === null) {
            throw new ApiAuthException(401, 'token 无效或已吊销', ['WWW-Authenticate' => 'Bearer']);
        }
        $scopes = (array) ($token['scopes'] ?? []);
        $allowed = in_array('admin', $scopes, true)
            || in_array($scope, $scopes, true)
            || ($scope === 'read' && in_array('write', $scopes, true));
        if (!$allowed) {
            throw new ApiAuthException(403, 'token 无此 scope');
        }
        $limiter = new ApiRateLimiter($this->pdo);
        if (!$limiter->hit((int) $token['id'], (int) $token['rate_limit'])) {
            throw new ApiAuthException(429, '请求过于频繁', ['Retry-After' => '60']);
        }
        return $token;
    }

    private function bearer(): string
    {
        $header = (string) ($_SERVER['HTTP_AUTHORIZATION'] ?? ($_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? ''));
        if ($header === '' && function_exists('getallheaders')) {
            $headers = getallheaders();
            $header = (string) ($headers['Authorization'] ?? '');
        }
        if (preg_match('/^Bearer\s+(.+)$/i', trim($header), $m) === 1) {
            return trim($m[1]);
        }
        return '';
    }
}

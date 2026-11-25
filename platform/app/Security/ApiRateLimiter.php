<?php
declare(strict_types=1);

/**
 * 固定窗口限流：每 token 每分钟 token.rate_limit 次。
 */
final class ApiRateLimiter
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function hit(int $tokenId, int $limit, ?int $now = null): bool
    {
        $now ??= time();
        $window = intdiv($now, 60) * 60;
        $limit = max(1, $limit);
        $stmt = $this->pdo->prepare('SELECT count FROM api_rate_limit WHERE token_id=? AND window_start=?');
        $stmt->execute([$tokenId, $window]);
        $count = $stmt->fetchColumn();
        if ($count === false) {
            $this->pdo->prepare('INSERT INTO api_rate_limit (token_id,window_start,count) VALUES (?,?,1)')->execute([$tokenId, $window]);
            return true;
        }
        $count = (int) $count;
        if ($count >= $limit) {
            return false;
        }
        $this->pdo->prepare('UPDATE api_rate_limit SET count=count+1 WHERE token_id=? AND window_start=?')->execute([$tokenId, $window]);
        return true;
    }
}

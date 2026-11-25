<?php
declare(strict_types=1);

/**
 * 点评防刷策略：一个 reviewer_key + listing 只能一条；每 reviewer_key 每小时最多 5 条。
 * 纯函数，便于测试；真正唯一约束由 DB 保证。
 */
final class ReviewPolicy
{
    public const MAX_PER_HOUR = 5;

    public static function key(?int $userId, string $account): string
    {
        if ($userId !== null && $userId > 0) {
            return 'user:' . $userId;
        }
        $account = strtolower(trim($account));
        return $account === '' ? 'anon' : 'demo:' . substr(hash('sha256', $account), 0, 32);
    }

    public static function canCreate(bool $alreadyReviewed, int $recentCount): bool
    {
        return !$alreadyReviewed && $recentCount < self::MAX_PER_HOUR;
    }

    public static function reason(bool $alreadyReviewed, int $recentCount): string
    {
        if ($alreadyReviewed) {
            return '你已点评过该物料';
        }
        if ($recentCount >= self::MAX_PER_HOUR) {
            return '点评过于频繁，请稍后再试';
        }
        return '';
    }
}

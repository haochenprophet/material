<?php
declare(strict_types=1);

/**
 * 登录失败限流（会话级）。生产用 Redis/MySQL + IP/设备维度。
 * 规则：15 分钟内失败 5 次 → 锁定 15 分钟。
 */
final class LoginThrottle
{
    public const MAX_FAILURES = 5;
    public const WINDOW_SECONDS = 900;
    public const LOCK_SECONDS = 900;

    /** @return array{allowed:bool, retry_after:int, failures:int} */
    public function check(string $account, ?int $now = null): array
    {
        $now ??= time();
        $state = $this->state($account);
        if ($state['locked_until'] > $now) {
            return ['allowed' => false, 'retry_after' => $state['locked_until'] - $now, 'failures' => $state['count']];
        }
        if ($state['first_at'] > 0 && $state['first_at'] + self::WINDOW_SECONDS <= $now) {
            $this->clear($account);
            return ['allowed' => true, 'retry_after' => 0, 'failures' => 0];
        }
        if ($state['count'] >= self::MAX_FAILURES) {
            return ['allowed' => false, 'retry_after' => self::LOCK_SECONDS, 'failures' => $state['count']];
        }
        return ['allowed' => true, 'retry_after' => 0, 'failures' => $state['count']];
    }

    public function failure(string $account, ?int $now = null): void
    {
        $now ??= time();
        $state = $this->state($account);
        if ($state['first_at'] === 0 || $state['first_at'] + self::WINDOW_SECONDS <= $now) {
            $state = ['count' => 0, 'first_at' => $now, 'locked_until' => 0];
        }
        $state['count']++;
        if ($state['count'] >= self::MAX_FAILURES) {
            $state['locked_until'] = $now + self::LOCK_SECONDS;
        }
        $_SESSION['_login_throttle'][$this->key($account)] = $state;
    }

    public function clear(string $account): void
    {
        unset($_SESSION['_login_throttle'][$this->key($account)]);
    }

    /** @return array{count:int, first_at:int, locked_until:int} */
    private function state(string $account): array
    {
        $key = $this->key($account);
        $state = $_SESSION['_login_throttle'][$key] ?? null;
        if (!is_array($state)) {
            return ['count' => 0, 'first_at' => 0, 'locked_until' => 0];
        }
        return [
            'count' => (int) ($state['count'] ?? 0),
            'first_at' => (int) ($state['first_at'] ?? 0),
            'locked_until' => (int) ($state['locked_until'] ?? 0),
        ];
    }

    private function key(string $account): string
    {
        return hash('sha256', strtolower(trim($account)));
    }
}

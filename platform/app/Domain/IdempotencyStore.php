<?php
declare(strict_types=1);

/**
 * 幂等键存储（内存版）。生产用 Redis/MySQL 唯一键，带 TTL。
 */
final class IdempotencyStore
{
    /** @var array<string, array<string, mixed>> */
    private array $results = [];

    public function has(string $key): bool
    {
        return isset($this->results[$key]);
    }

    /** @return array<string, mixed>|null */
    public function get(string $key): ?array
    {
        return $this->results[$key] ?? null;
    }

    /** @param array<string, mixed> $result */
    public function put(string $key, array $result): void
    {
        $this->results[$key] = $result;
    }

    public function count(): int
    {
        return count($this->results);
    }
}

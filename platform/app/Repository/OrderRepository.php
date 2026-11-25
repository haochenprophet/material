<?php
declare(strict_types=1);

interface OrderRepository
{
    /** @param array<string, mixed> $order */
    public function save(array $order): void;

    /** @return array<string, mixed>|null */
    public function find(string $id): ?array;

    /** @return array<int, array<string, mixed>> */
    public function all(): array;
}

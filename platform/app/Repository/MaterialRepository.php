<?php
declare(strict_types=1);

interface MaterialRepository
{
    /** @return array<int, array<string, mixed>> */
    public function all(): array;

    /** @return array<string, mixed>|null */
    public function find(string $id): ?array;
}

<?php
declare(strict_types=1);

/**
 * 会话级订单存储：让 API 创建的订单能被 /orders/{id} 页面读到。
 * 仅演示；生产用 OrderRepository。
 */
final class OrderStore
{
    private const KEY = 'orders';
    private const MAX = 20;

    /** @param array<string, mixed> $order */
    public function put(array $order): void
    {
        $id = (string) ($order['id'] ?? '');
        if ($id === '') {
            throw new InvalidArgumentException('order.id 必填');
        }
        $orders = is_array($_SESSION[self::KEY] ?? null) ? $_SESSION[self::KEY] : [];
        $orders[$id] = $order;
        if (count($orders) > self::MAX) {
            $orders = array_slice($orders, -self::MAX, null, true);
        }
        $_SESSION[self::KEY] = $orders;
    }

    /** @return array<string, mixed>|null */
    public function get(string $id): ?array
    {
        $orders = is_array($_SESSION[self::KEY] ?? null) ? $_SESSION[self::KEY] : [];
        return is_array($orders[$id] ?? null) ? $orders[$id] : null;
    }
}

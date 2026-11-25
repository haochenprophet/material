<?php
declare(strict_types=1);

/**
 * 订单服务（内存版）：报价 → 创建订单 → 状态流转。
 * 不落库、不支付；支付/库存/物流由后续服务接入。
 */
final class OrderService
{
    /**
     * @param array<int, array{qty:int|float, unit:float}> $lines
     * @param array<string, mixed> $options
     * @return array<string, mixed>
     */
    public function create(array $lines, array $options = []): array
    {
        if ($lines === []) {
            throw new InvalidArgumentException('订单至少一行');
        }
        foreach ($lines as $line) {
            if ((float) ($line['qty'] ?? 0) <= 0 || (float) ($line['unit'] ?? 0) < 0) {
                throw new InvalidArgumentException('数量必须 >0，单价不能为负');
            }
        }
        $pricing = Pricing::quote($lines, $options);
        $now = date('c');
        return [
            'id' => 'SO-' . date('YmdHis') . '-' . strtoupper(bin2hex(random_bytes(2))),
            'status' => 'pending_payment',
            'lines' => array_values($lines),
            'pricing' => $pricing,
            'created_at' => $now,
            'updated_at' => $now,
        ];
    }

    /**
     * @param array<string, mixed> $order
     * @return array<string, mixed>
     */
    public function transition(array $order, string $to): array
    {
        $from = (string) ($order['status'] ?? '');
        StateMachine::assertCan('order', $from, $to);
        $order['status'] = $to;
        $order['updated_at'] = date('c');
        return $order;
    }

    /** @param array<string, mixed> $order */
    public function can(array $order, string $to): bool
    {
        return StateMachine::can('order', (string) ($order['status'] ?? ''), $to);
    }
}

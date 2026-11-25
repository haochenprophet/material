<?php
declare(strict_types=1);

/**
 * 业务状态机。所有状态变更必须先过 can()/assert()；
 * 禁止页面/控制器直接改 status。首版覆盖 listing/order/payment/settlement/qc/after_sales。
 */
final class StateMachine
{
    /** @var array<string, array<string, array<int, string>>> */
    private const MAP = [
        'listing' => [
            'draft' => ['pending_review'],
            'pending_review' => ['approved', 'rejected'],
            'approved' => ['listed'],
            'listed' => ['paused', 'sold_out', 'expired'],
            'paused' => ['listed', 'sold_out'],
            'rejected' => ['draft'],
            'sold_out' => [],
            'expired' => [],
        ],
        'order' => [
            'pending_payment' => ['paid', 'cancelled'],
            'paid' => ['allocating', 'refunded'],
            'allocating' => ['shipped', 'cancelled'],
            'shipped' => ['received'],
            'received' => ['completed', 'after_sales'],
            'completed' => ['after_sales'],
            'after_sales' => ['completed', 'refunded'],
            'cancelled' => [],
            'refunded' => [],
        ],
        'payment' => [
            'created' => ['processing', 'failed'],
            'processing' => ['succeeded', 'failed'],
            'succeeded' => ['refunded', 'partial_refunded'],
            'failed' => [],
            'refunded' => [],
            'partial_refunded' => [],
        ],
        'settlement' => [
            'pending' => ['frozen'],
            'frozen' => ['approved', 'cancelled'],
            'approved' => ['paid'],
            'paid' => ['reconciled'],
            'cancelled' => [],
            'reconciled' => [],
        ],
        'qc_task' => [
            'pending' => ['assigned'],
            'assigned' => ['in_progress'],
            'in_progress' => ['done', 'cancelled'],
            'done' => [],
            'cancelled' => [],
        ],
        'after_sales' => [
            'created' => ['approved', 'rejected'],
            'approved' => ['returning'],
            'returning' => ['received'],
            'received' => ['refunded', 'closed'],
            'rejected' => ['closed'],
            'refunded' => [],
            'closed' => [],
        ],
    ];

    /** @return array<int, string> */
    public static function next(string $entity, string $from): array
    {
        return self::MAP[$entity][$from] ?? [];
    }

    public static function can(string $entity, string $from, string $to): bool
    {
        return in_array($to, self::next($entity, $from), true);
    }

    public static function assertCan(string $entity, string $from, string $to): void
    {
        if (!self::can($entity, $from, $to)) {
            throw new DomainException("非法状态流转：{$entity} {$from} → {$to}");
        }
    }

    /** @return array<string, array<string, array<int, string>>> */
    public static function all(): array
    {
        return self::MAP;
    }
}

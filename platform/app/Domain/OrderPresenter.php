<?php
declare(strict_types=1);

/**
 * 把 OrderService 的订单数组映射成 order.php 需要的展示结构。
 */
final class OrderPresenter
{
    /** @return array<string, mixed> */
    public function present(array $order): array
    {
        $status = (string) ($order['status'] ?? 'pending_payment');
        $labels = [
            'pending_payment' => ['待支付', 'warn'],
            'paid' => ['已支付', 'blue'],
            'allocating' => ['配货中', 'blue'],
            'shipped' => ['已发货', 'blue'],
            'received' => ['已收货', 'ok'],
            'completed' => ['已完成', 'ok'],
            'cancelled' => ['已取消', ''],
            'after_sales' => ['售后中', 'warn'],
            'refunded' => ['已退款', 'fail'],
        ];
        [$label, $kind] = $labels[$status] ?? [$status, ''];

        $pricing = is_array($order['pricing'] ?? null) ? $order['pricing'] : [];
        $total = (float) ($pricing['total'] ?? ($pricing['subtotal'] ?? 0));
        $lines = [];
        foreach (($order['lines'] ?? []) as $line) {
            $qty = (float) ($line['qty'] ?? 0);
            $unit = (float) ($line['unit'] ?? 0);
            $lines[] = [
                (string) ($line['mpn'] ?? '物料'),
                (string) (int) $qty,
                '¥' . number_format($unit, 2),
                '¥' . number_format($qty * $unit, 2),
            ];
        }

        $timeline = [
            ['下单', date('Y-m-d H:i', strtotime((string) ($order['created_at'] ?? 'now'))), 'done'],
            ['支付', $status === 'pending_payment' ? '待支付' : '已支付', in_array($status, ['pending_payment'], true) ? 'current' : 'done'],
            ['发货', in_array($status, ['shipped', 'received', 'completed'], true) ? '已发货' : '待发货', in_array($status, ['paid', 'allocating'], true) ? 'current' : (in_array($status, ['shipped', 'received', 'completed'], true) ? 'done' : 'todo')],
            ['收货', in_array($status, ['received', 'completed'], true) ? '已收货' : '待收货', $status === 'completed' ? 'done' : (in_array($status, ['received'], true) ? 'current' : 'todo')],
        ];

        $user = Auth::user();
        return [
            'no' => (string) ($order['id'] ?? ''),
            'buyer' => $user['name'] ?? '当前用户',
            'seller' => '平台演示',
            'amount' => '¥' . number_format($total, 2),
            'status' => $label,
            'kind' => $kind,
            'time' => date('H:i', strtotime((string) ($order['created_at'] ?? 'now'))),
            'lines' => $lines,
            'timeline' => $timeline,
        ];
    }
}

<?php
declare(strict_types=1);

/**
 * 支付服务（内存版）：创建支付意图 → 处理中 → 成功/失败 → 退款。
 * 不接真实支付机构；金额以服务端订单为准。
 */
final class PaymentService
{
    /**
     * @param array<string, mixed> $order
     * @return array<string, mixed>
     */
    public function create(array $order, string $provider = 'demo'): array
    {
        $amount = (float) ($order['pricing']['total'] ?? 0);
        if ($amount <= 0) {
            throw new InvalidArgumentException('支付金额必须 >0');
        }
        $now = date('c');
        return [
            'id' => 'PAY-' . date('YmdHis') . '-' . strtoupper(bin2hex(random_bytes(2))),
            'order_id' => (string) ($order['id'] ?? ''),
            'provider' => $provider,
            'amount' => round($amount, 2),
            'status' => 'created',
            'transaction_id' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ];
    }

    /**
     * @param array<string, mixed> $payment
     * @return array<string, mixed>
     */
    public function capture(array $payment, string $transactionId): array
    {
        if (trim($transactionId) === '') {
            throw new InvalidArgumentException('transaction_id 不能为空');
        }
        $payment = $this->transition($payment, 'processing');
        $payment = $this->transition($payment, 'succeeded');
        $payment['transaction_id'] = $transactionId;
        return $payment;
    }

    /**
     * @param array<string, mixed> $payment
     * @return array<string, mixed>
     */
    public function fail(array $payment): array
    {
        return $this->transition($payment, 'failed');
    }

    /**
     * @param array<string, mixed> $payment
     * @return array<string, mixed>
     */
    public function refund(array $payment, ?float $amount = null): array
    {
        $original = (float) ($payment['amount'] ?? 0);
        $refund = $amount === null ? $original : round($amount, 2);
        if ($refund <= 0 || $refund > $original) {
            throw new InvalidArgumentException('退款金额必须 >0 且不超过支付金额');
        }
        $to = $refund >= $original - 0.001 ? 'refunded' : 'partial_refunded';
        $payment = $this->transition($payment, $to);
        $payment['refunded_amount'] = $refund;
        return $payment;
    }

    /**
     * @param array<string, mixed> $payment
     * @return array<string, mixed>
     */
    private function transition(array $payment, string $to): array
    {
        StateMachine::assertCan('payment', (string) ($payment['status'] ?? ''), $to);
        $payment['status'] = $to;
        $payment['updated_at'] = date('c');
        return $payment;
    }
}

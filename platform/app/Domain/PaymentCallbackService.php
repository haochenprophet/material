<?php
declare(strict_types=1);

/**
 * 支付回调：HMAC 验签 → 事件解析 → 金额校验 → 幂等 → 状态流转。
 * 不接真实支付机构；生产需时间戳/重放窗口/IP 白名单/证书验签。
 */
final class PaymentCallbackService
{
    public function sign(string $payload, string $secret): string
    {
        return hash_hmac('sha256', $payload, $secret);
    }

    /**
     * @param array<string, mixed> $payment
     * @param array<string, mixed> $event
     * @return array{replayed:bool, payment:array<string, mixed>, event:array<string, mixed>}
     */
    public function handle(array $payment, string $payload, string $signature, string $secret, IdempotencyStore $store): array
    {
        if (!hash_equals($this->sign($payload, $secret), $signature)) {
            throw new InvalidArgumentException('回调签名无效');
        }
        $event = json_decode($payload, true);
        if (!is_array($event)) {
            throw new InvalidArgumentException('回调 JSON 无效');
        }
        $eventId = trim((string) ($event['event_id'] ?? ''));
        $txnId = trim((string) ($event['transaction_id'] ?? ''));
        $status = (string) ($event['status'] ?? '');
        $amount = (float) ($event['amount'] ?? 0);
        if ($eventId === '' || $txnId === '') {
            throw new InvalidArgumentException('event_id/transaction_id 必填');
        }
        if (abs($amount - (float) ($payment['amount'] ?? 0)) > 0.001) {
            throw new InvalidArgumentException('回调金额与支付金额不一致');
        }
        $key = 'payment:' . (string) ($payment['id'] ?? '') . ':' . $eventId;
        $existing = $store->get($key);
        if ($existing !== null) {
            return ['replayed' => true, 'payment' => (array) $existing['payment'], 'event' => $event];
        }

        $service = new PaymentService();
        if ($status === 'succeeded') {
            $payment = $service->capture($payment, $txnId);
        } elseif ($status === 'failed') {
            $payment = $service->fail($payment);
        } else {
            throw new InvalidArgumentException('不支持的回调状态：' . $status);
        }

        $store->put($key, ['payment' => $payment, 'event' => $event]);
        return ['replayed' => false, 'payment' => $payment, 'event' => $event];
    }
}

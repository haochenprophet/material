<?php
declare(strict_types=1);

/**
 * 寄售结算服务（内存版）：订单 → 结算单 → 冻结/复核/放款/对账。
 * 金额公式复用 Pricing；账本落库/发票/二清由后续服务接入。
 */
final class SettlementService
{
    /**
     * @param array<string, mixed> $order
     * @param array{commission_rate?:float, qc?:float, logistics?:float, refund?:float} $opts
     * @return array<string, mixed>
     */
    public function fromOrder(array $order, array $opts = []): array
    {
        $goods = (float) ($order['pricing']['goods'] ?? 0);
        if ($goods <= 0) {
            throw new InvalidArgumentException('订单商品金额必须 >0');
        }
        $calc = Pricing::consignment(
            $goods,
            (float) ($opts['commission_rate'] ?? 0.20),
            (float) ($opts['qc'] ?? 0),
            (float) ($opts['logistics'] ?? 0),
            (float) ($opts['refund'] ?? 0)
        );
        $now = date('c');
        return [
            'id' => 'ST-' . date('YmdHis') . '-' . strtoupper(bin2hex(random_bytes(2))),
            'order_id' => (string) ($order['id'] ?? ''),
            'status' => 'pending',
            'gross' => $calc['gross'],
            'commission' => $calc['commission'],
            'qc' => $calc['qc'],
            'logistics' => $calc['logistics'],
            'refund' => $calc['refund'],
            'seller_net' => $calc['seller_net'],
            'created_at' => $now,
            'updated_at' => $now,
        ];
    }

    /**
     * @param array<string, mixed> $settlement
     * @return array<string, mixed>
     */
    public function transition(array $settlement, string $to): array
    {
        $from = (string) ($settlement['status'] ?? '');
        StateMachine::assertCan('settlement', $from, $to);
        $settlement['status'] = $to;
        $settlement['updated_at'] = date('c');
        return $settlement;
    }
}

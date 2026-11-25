<?php
declare(strict_types=1);

/**
 * 报价/佣金/买家节省测算。金额统一按分四舍五入到 2 位。
 * 不依赖数据库，便于单元测试；后端接单时复用同一套公式。
 */
final class Pricing
{
    /**
     * @param array<int, array{qty:int|float, unit:float}> $lines
     * @param array{qc_fee?:float, fulfillment_fee?:float, shipping?:float, service_rate?:float, tax_rate?:float} $opts
     * @return array{goods:float, qc_fee:float, fulfillment_fee:float, shipping:float, service:float, subtotal:float, tax_included:float, total:float}
     */
    public static function quote(array $lines, array $opts = []): array
    {
        $goods = 0.0;
        foreach ($lines as $line) {
            $goods += (float) ($line['qty'] ?? 0) * (float) ($line['unit'] ?? 0);
        }
        $qc = (float) ($opts['qc_fee'] ?? 0);
        $fulfillment = (float) ($opts['fulfillment_fee'] ?? 0);
        $shipping = (float) ($opts['shipping'] ?? 0);
        $serviceRate = (float) ($opts['service_rate'] ?? 0.03);
        $taxRate = (float) ($opts['tax_rate'] ?? 0.13);

        $goods = round($goods, 2);
        $service = round($goods * $serviceRate, 2);
        $subtotal = round($goods + $qc + $fulfillment + $shipping + $service, 2);
        $taxIncluded = round($subtotal - $subtotal / (1 + $taxRate), 2);

        return [
            'goods' => $goods,
            'qc_fee' => round($qc, 2),
            'fulfillment_fee' => round($fulfillment, 2),
            'shipping' => round($shipping, 2),
            'service' => $service,
            'subtotal' => $subtotal,
            'tax_included' => $taxIncluded,
            'total' => $subtotal,
        ];
    }

    /**
     * 寄售结算：卖家应结 = 成交额 - 佣金 - 质检 - 物流 - 退款。
     * @return array{gross:float, commission:float, qc:float, logistics:float, refund:float, seller_net:float, platform_gross:float}
     */
    public static function consignment(float $gross, float $commissionRate = 0.20, float $qc = 0, float $logistics = 0, float $refund = 0): array
    {
        $commission = round($gross * $commissionRate, 2);
        $sellerNet = round($gross - $commission - $qc - $logistics - $refund, 2);
        if ($sellerNet < 0) {
            $sellerNet = 0.0;
        }
        return [
            'gross' => round($gross, 2),
            'commission' => $commission,
            'qc' => round($qc, 2),
            'logistics' => round($logistics, 2),
            'refund' => round($refund, 2),
            'seller_net' => $sellerNet,
            'platform_gross' => $commission,
        ];
    }

    /** 买家相对“全新价”的节省比例（0–1）。 */
    public static function buyerSavings(float $newPrice, float $resalePrice): float
    {
        if ($newPrice <= 0) {
            return 0.0;
        }
        return max(0.0, min(1.0, round(($newPrice - $resalePrice) / $newPrice, 4)));
    }
}

<?php
declare(strict_types=1);

/**
 * 佣金计算：比例 + 最低/最高费用，返回平台佣金与卖家应结。
 */
final class CommissionCalculator
{
    public function calculate(float $gross, float $rate, float $minFee = 0, ?float $maxFee = null): array
    {
        if ($gross <= 0) {
            throw new InvalidArgumentException('商品金额必须 >0');
        }
        if ($rate < 0 || $rate > 1) {
            throw new InvalidArgumentException('佣金比例必须在 0–1');
        }
        $commission = round($gross * $rate, 2);
        if ($commission < $minFee) {
            $commission = round($minFee, 2);
        }
        if ($maxFee !== null && $commission > $maxFee) {
            $commission = round($maxFee, 2);
        }
        if ($commission < 0) {
            $commission = 0.0;
        }
        return [
            'gross' => round($gross, 2),
            'rate' => $rate,
            'commission' => $commission,
            'seller_net' => round($gross - $commission, 2),
        ];
    }
}

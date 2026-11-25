<?php
declare(strict_types=1);

/**
 * 支付/账本对账（内存版）：比较支付金额与 gateway 借方余额。
 */
final class ReconciliationService
{
    /**
     * @param array<string, mixed> $payment
     * @return array{status:string, expected:float, actual:float, diff:float}
     */
    public function reconcile(array $payment, LedgerService $ledger): array
    {
        $expected = round((float) ($payment['amount'] ?? 0), 2);
        $balance = $ledger->balance();
        $actual = round((float) ($balance['payment_gateway'] ?? 0), 2);
        $diff = round($actual - $expected, 2);
        return [
            'status' => abs($diff) <= 0.01 ? 'matched' : 'diff',
            'expected' => $expected,
            'actual' => $actual,
            'diff' => $diff,
        ];
    }
}

<?php
declare(strict_types=1);

/**
 * 复式记账（内存版）：每笔分录借贷必须平衡。
 * 只做账务规则验证，不接支付/发票/银行。
 */
final class LedgerService
{
    /** @var array<int, array<string, mixed>> */
    private array $entries = [];
    private int $seq = 0;

    /**
     * @param array<int, array{account:string, debit?:float, credit?:float, memo?:string}> $lines
     * @return array<string, mixed>
     */
    public function post(string $ref, array $lines): array
    {
        if (count($lines) < 2) {
            throw new InvalidArgumentException('至少两条分录');
        }
        $debit = 0.0;
        $credit = 0.0;
        foreach ($lines as $line) {
            $account = trim((string) ($line['account'] ?? ''));
            $d = (float) ($line['debit'] ?? 0);
            $c = (float) ($line['credit'] ?? 0);
            if ($account === '') {
                throw new InvalidArgumentException('account 不能为空');
            }
            if ($d < 0 || $c < 0) {
                throw new InvalidArgumentException('金额不能为负');
            }
            if ($d > 0 && $c > 0) {
                throw new InvalidArgumentException('单条分录不能同时借贷');
            }
            if ($d == 0.0 && $c == 0.0) {
                throw new InvalidArgumentException('单条分录金额不能为 0');
            }
            $debit += $d;
            $credit += $c;
        }
        if (abs($debit - $credit) > 0.001) {
            throw new InvalidArgumentException('借贷不平衡：debit=' . $debit . ' credit=' . $credit);
        }

        $batch = ['ref' => $ref, 'posted_at' => date('c'), 'debit' => round($debit, 2), 'credit' => round($credit, 2), 'lines' => []];
        foreach ($lines as $line) {
            $this->seq++;
            $entry = [
                'id' => $this->seq,
                'ref' => $ref,
                'account' => (string) $line['account'],
                'debit' => round((float) ($line['debit'] ?? 0), 2),
                'credit' => round((float) ($line['credit'] ?? 0), 2),
                'memo' => (string) ($line['memo'] ?? ''),
            ];
            $this->entries[] = $entry;
            $batch['lines'][] = $entry;
        }
        return $batch;
    }

    /** @return array<string, float> account => debit - credit */
    public function balance(): array
    {
        $out = [];
        foreach ($this->entries as $e) {
            $account = (string) $e['account'];
            $out[$account] = round(($out[$account] ?? 0) + (float) $e['debit'] - (float) $e['credit'], 2);
        }
        ksort($out);
        return $out;
    }

    /** @return array<int, array<string, mixed>> */
    public function entries(): array
    {
        return $this->entries;
    }

    /** @return array{debit:float, credit:float} */
    public function totals(): array
    {
        $debit = 0.0;
        $credit = 0.0;
        foreach ($this->entries as $e) {
            $debit += (float) $e['debit'];
            $credit += (float) $e['credit'];
        }
        return ['debit' => round($debit, 2), 'credit' => round($credit, 2)];
    }
}

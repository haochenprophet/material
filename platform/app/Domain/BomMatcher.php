<?php
declare(strict_types=1);

/**
 * BOM 简单匹配器：每行选“总成本最低”的可售 offer。
 * 总成本 = 单价 × 数量 + 质检费 + 运费；无可售 offer = 缺料。
 * 真实版本会加交期/等级/替代风险权重，这里保留可测试的最小闭环。
 */
final class BomMatcher
{
    /**
     * @param array<int, array{mpn:string, qty:int|float, offers?:array<int, array{unit:float, qc?:float, shipping?:float, supplier?:string, substitute?:bool}>}> $lines
     * @return array{lines:array<int, array<string, mixed>>, matched:int, shortage:int, total:float}
     */
    public static function match(array $lines): array
    {
        $out = [];
        $matched = 0;
        $shortage = 0;
        $total = 0.0;

        foreach ($lines as $line) {
            $qty = (float) ($line['qty'] ?? 0);
            $best = null;
            $bestCost = null;
            foreach (($line['offers'] ?? []) as $offer) {
                $cost = round((float) ($offer['unit'] ?? 0) * $qty + (float) ($offer['qc'] ?? 0) + (float) ($offer['shipping'] ?? 0), 2);
                if ($bestCost === null || $cost < $bestCost) {
                    $bestCost = $cost;
                    $best = $offer + ['cost' => $cost];
                }
            }
            if ($best === null) {
                $shortage++;
                $status = 'shortage';
            } else {
                $matched++;
                $total += (float) $bestCost;
                $status = !empty($best['substitute']) ? 'substitute' : 'quoted';
            }
            $out[] = [
                'mpn' => (string) ($line['mpn'] ?? ''),
                'qty' => $qty,
                'status' => $status,
                'best' => $best,
                'cost' => $bestCost ?? 0.0,
            ];
        }

        return [
            'lines' => $out,
            'matched' => $matched,
            'shortage' => $shortage,
            'total' => round($total, 2),
        ];
    }
}

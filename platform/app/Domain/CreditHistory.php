<?php
declare(strict_types=1);

/**
 * 信用历史曲线：把 credit_event 转成时间序列和轻量 SVG。
 */
final class CreditHistory
{
    /** @return array<int, array{label:string,score:int}> */
    public function points(array $events): array
    {
        $events = array_values($events);
        usort($events, static fn ($a, $b) => ((int) ($a['id'] ?? 0)) <=> ((int) ($b['id'] ?? 0)));
        $points = [];
        foreach ($events as $e) {
            $score = (int) ($e['score_after'] ?? 0);
            $points[] = [
                'label' => substr((string) ($e['created_at'] ?? ''), 0, 16),
                'score' => max(0, min(100, $score)),
            ];
        }
        return $points;
    }

    /** @param array<int, array{label:string,score:int}> $points */
    public function sparkline(array $points, int $width = 360, int $height = 72): string
    {
        if (count($points) < 2) {
            return '';
        }
        $scores = array_column($points, 'score');
        $min = max(0, min($scores) - 5);
        $max = min(100, max($scores) + 5);
        $span = max(1, $max - $min);
        $step = $width / (count($points) - 1);
        $coords = [];
        foreach ($points as $i => $p) {
            $x = round($i * $step, 1);
            $y = round($height - (($p['score'] - $min) / $span) * $height, 1);
            $coords[] = [$x, $y, $p['score'], $p['label']];
        }
        $path = '';
        foreach ($coords as $i => $c) {
            $path .= ($i === 0 ? 'M' : 'L') . $c[0] . ' ' . $c[1] . ' ';
        }
        $svg = '<svg class="credit-spark" viewBox="0 0 ' . $width . ' ' . $height . '" role="img" aria-label="信用变化曲线" preserveAspectRatio="none">';
        $svg .= '<path d="' . trim($path) . '" fill="none" stroke="#2563eb" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>';
        foreach ($coords as $c) {
            $svg .= '<circle cx="' . $c[0] . '" cy="' . $c[1] . '" r="3" fill="#1e3a8a"><title>' . htmlspecialchars((string) $c[3], ENT_QUOTES, 'UTF-8') . ' · ' . (int) $c[2] . ' 分</title></circle>';
        }
        return $svg . '</svg>';
    }
}

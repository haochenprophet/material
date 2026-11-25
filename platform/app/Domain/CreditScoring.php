<?php
declare(strict_types=1);

/**
 * 可解释信用评分：物料分 + 商家分。
 * 不依赖数据库，便于单测；范围为 0–100。
 */
final class CreditScoring
{
    public function material(array $reviews, array $opts = []): array
    {
        $reviews = array_values(array_filter($reviews, static fn ($r) => is_array($r)));
        $count = count($reviews);
        if ($count === 0) {
            return [
                'review_count' => 0, 'avg_rating' => 0.0, 'score' => 0, 'grade' => 'NA',
                'quality_avg' => 0.0, 'price_avg' => 0.0, 'delivery_avg' => 0.0, 'service_avg' => 0.0,
                'positive_rate' => 0.0, 'verified_count' => 0,
            ];
        }
        $sum = 0; $positive = 0; $verified = 0;
        $q = 0; $p = 0; $d = 0; $s = 0;
        foreach ($reviews as $r) {
            $rating = $this->clamp((float) ($r['rating'] ?? 0), 1, 5);
            $sum += $rating;
            if ($rating >= 4) {
                $positive++;
            }
            if ((int) ($r['verified_purchase'] ?? 0) === 1) {
                $verified++;
            }
            $q += $this->clamp((float) ($r['quality'] ?? $rating), 1, 5);
            $p += $this->clamp((float) ($r['price'] ?? $rating), 1, 5);
            $d += $this->clamp((float) ($r['delivery'] ?? $rating), 1, 5);
            $s += $this->clamp((float) ($r['service'] ?? $rating), 1, 5);
        }
        $avg = $sum / $count;
        $positiveRate = $positive / $count * 100;
        $verifiedRate = $verified / $count;
        $gradeMap = ['A' => 5.0, 'B' => 3.0, 'C' => 1.0, 'NA' => 0.0, 'D' => 0.0];
        $condition = $gradeMap[strtoupper((string) ($opts['grade'] ?? 'NA'))] ?? 0.0;
        $score = (($avg - 1) / 4) * 70
            + ($positiveRate / 100) * 10
            + min($count / 50, 1) * 10
            + $verifiedRate * 5
            + $condition;
        $score = (int) round($this->clamp($score, 0, 100));
        return [
            'review_count' => $count,
            'avg_rating' => round($avg, 2),
            'score' => $score,
            'grade' => $this->grade($score),
            'quality_avg' => round($q / $count, 2),
            'price_avg' => round($p / $count, 2),
            'delivery_avg' => round($d / $count, 2),
            'service_avg' => round($s / $count, 2),
            'positive_rate' => round($positiveRate, 2),
            'verified_count' => $verified,
        ];
    }

    /**
     * @param array<int, array{score:int, review_count:int}> $materials
     */
    public function merchant(array $materials, array $opts = []): array
    {
        $weighted = 0.0; $weight = 0; $totalReviews = 0; $verified = 0;
        foreach ($materials as $m) {
            $w = max(0, (int) ($m['review_count'] ?? 0));
            if ($w === 0) {
                continue;
            }
            $weighted += (float) ($m['score'] ?? 0) * $w;
            $weight += $w;
            $totalReviews += $w;
            $verified += (int) ($m['verified_count'] ?? 0);
        }
        if ($totalReviews === 0) {
            return [
                'review_count' => 0, 'avg_rating' => 0.0, 'score' => 0, 'grade' => 'NA',
                'verified_count' => 0, 'positive_rate' => 0.0, 'material_count' => (int) ($opts['material_count'] ?? 0),
                'listed_count' => (int) ($opts['listed_count'] ?? 0),
            ];
        }
        $materialAvg = $weighted / $weight;
        $storeRating = $this->clamp((float) ($opts['store_rating'] ?? 0), 0, 5);
        $listed = max(0, (int) ($opts['listed_count'] ?? 0));
        $verifiedRate = $verified / max(1, $totalReviews);
        $verifiedMerchant = (int) ($opts['verified_merchant'] ?? 0) === 1;
        $score = $materialAvg * 0.70
            + ($storeRating / 5) * 10
            + min($totalReviews / 100, 1) * 5
            + $verifiedRate * 5
            + min($listed / 20, 1) * 5
            + ($verifiedMerchant ? 5 : 0);
        $score = (int) round($this->clamp($score, 0, 100));
        return [
            'review_count' => $totalReviews,
            'avg_rating' => round((float) ($opts['avg_rating'] ?? 0), 2),
            'score' => $score,
            'grade' => $this->grade($score),
            'verified_count' => $verified,
            'positive_rate' => round((float) ($opts['positive_rate'] ?? 0), 2),
            'material_count' => (int) ($opts['material_count'] ?? 0),
            'listed_count' => $listed,
        ];
    }

    public function grade(int $score): string
    {
        return match (true) {
            $score >= 90 => 'AAA',
            $score >= 80 => 'AA',
            $score >= 70 => 'A',
            $score >= 60 => 'B',
            $score >= 50 => 'C',
            default => 'D',
        };
    }

    private function clamp(float $value, float $min, float $max): float
    {
        return max($min, min($max, $value));
    }
}

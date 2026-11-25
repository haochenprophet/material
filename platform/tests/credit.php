<?php
declare(strict_types=1);

/**
 * 信用评分测试。用法：php platform/tests/credit.php
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    echo 'CLI only';
    exit(1);
}

require dirname(__DIR__) . '/app/bootstrap.php';

$pass = 0;
$fail = 0;
function check(string $name, bool $cond, string $detail = ''): void
{
    global $pass, $fail;
    if ($cond) {
        $pass++;
        echo "  PASS  {$name}" . PHP_EOL;
    } else {
        $fail++;
        echo "  FAIL  {$name}" . ($detail !== '' ? "  ({$detail})" : '') . PHP_EOL;
    }
}

echo '物格云 credit scoring tests · v' . APP_VERSION . PHP_EOL . PHP_EOL;
$s = new CreditScoring();
$empty = $s->material([]);
check('空物料 NA', $empty['grade'] === 'NA' && $empty['score'] === 0);
$perfect = array_fill(0, 50, ['rating' => 5, 'quality' => 5, 'price' => 5, 'delivery' => 5, 'service' => 5, 'verified_purchase' => 1]);
$p = $s->material($perfect, ['grade' => 'A']);
check('满分 100/AAA', $p['score'] === 100 && $p['grade'] === 'AAA');
check('四维均分', $p['quality_avg'] === 5.0 && $p['service_avg'] === 5.0);
$mixed = [
    ['rating' => 5, 'quality' => 5, 'price' => 4, 'delivery' => 5, 'service' => 5, 'verified_purchase' => 1],
    ['rating' => 4, 'quality' => 4, 'price' => 4, 'delivery' => 4, 'service' => 4, 'verified_purchase' => 0],
    ['rating' => 2, 'quality' => 2, 'price' => 2, 'delivery' => 2, 'service' => 2, 'verified_purchase' => 0],
];
$m = $s->material($mixed);
check('混合评分有数据', $m['review_count'] === 3 && $m['avg_rating'] > 0);
check('好评率 66.67', abs($m['positive_rate'] - 66.67) < 0.01);
check('grade 阈值', $s->grade(90) === 'AAA' && $s->grade(80) === 'AA' && $s->grade(70) === 'A' && $s->grade(60) === 'B' && $s->grade(50) === 'C' && $s->grade(49) === 'D');
$merchant = $s->merchant([
    ['score' => 90, 'review_count' => 10, 'verified_count' => 8],
    ['score' => 70, 'review_count' => 30, 'verified_count' => 10],
], ['store_rating' => 4.5, 'listed_count' => 10, 'verified_merchant' => 1, 'avg_rating' => 4.4, 'positive_rate' => 85, 'material_count' => 2]);
check('商家加权有分', $merchant['score'] > 0 && $merchant['review_count'] === 40);
check('商家等级', in_array($merchant['grade'], ['AAA', 'AA', 'A'], true));
$emptyMerchant = $s->merchant([]);
check('空商家 NA', $emptyMerchant['grade'] === 'NA' && $emptyMerchant['score'] === 0);

echo PHP_EOL . '结果：PASS ' . $pass . ' / FAIL ' . $fail . PHP_EOL;
exit($fail > 0 ? 1 : 0);

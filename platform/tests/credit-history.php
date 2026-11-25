<?php
declare(strict_types=1);

/**
 * 信用历史/曲线测试。用法：php platform/tests/credit-history.php
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
    if ($cond) { $pass++; echo "  PASS  {$name}" . PHP_EOL; }
    else { $fail++; echo "  FAIL  {$name}" . ($detail !== '' ? "  ({$detail})" : '') . PHP_EOL; }
}

echo '物格云 credit history tests · v' . APP_VERSION . PHP_EOL . PHP_EOL;
$h = new CreditHistory();
$events = [
    ['id' => 3, 'score_after' => 80, 'created_at' => '2026-11-25 10:03'],
    ['id' => 1, 'score_after' => 70, 'created_at' => '2026-11-25 10:01'],
    ['id' => 2, 'score_after' => 75, 'created_at' => '2026-11-25 10:02'],
];
$p = $h->points($events);
check('按 id 升序', array_column($p, 'score') === [70, 75, 80]);
check('标签时间', $p[0]['label'] === '2026-11-25 10:01');
check('单点无曲线', $h->sparkline([['label' => 'x', 'score' => 50]]) === '');
$svg = $h->sparkline($p);
check('SVG 曲线', str_contains($svg, '<svg') && str_contains($svg, '<path'));
check('评分越界夹取', $h->points([['id' => 1, 'score_after' => 120, 'created_at' => 'x']])[0]['score'] === 100);
check('空事件', $h->points([]) === []);

echo PHP_EOL . '结果：PASS ' . $pass . ' / FAIL ' . $fail . PHP_EOL;
exit($fail > 0 ? 1 : 0);

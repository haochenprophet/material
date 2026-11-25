<?php
declare(strict_types=1);

/**
 * DB 详情映射测试。用法：php platform/tests/catalog-detail.php
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

echo '物格云 catalog detail tests · v' . APP_VERSION . PHP_EOL . PHP_EOL;
$product = [
    'id' => 'p1',
    'title' => 'TE 170364-1 connector',
    'spec' => 'TE 170364-1 · A 级',
    'price' => '¥18.50',
    'unit' => '个',
    'stock' => '库存 1,240',
    'location' => '默认仓 DEFAULT',
    'badge' => 'A 级质检',
    'badgeKind' => 'ok',
];
$d = (new CatalogDetailPresenter())->present($product);
check('标题', ($d['title'] ?? '') === 'TE 170364-1 connector');
check('价格/单位', ($d['price'] ?? '') === '¥18.50' && ($d['unit'] ?? '') === '个');
check('阶梯价 3 档', count($d['tier'] ?? []) === 3);
check('规格 3 条', count($d['specs'] ?? []) === 3);
check('badge 保留', ($d['badges'][0][0] ?? '') === 'A 级质检');
check('库存/卖家', ($d['stock'] ?? '') === '1,240' && ($d['seller'] ?? '') !== '');

echo PHP_EOL . '结果：PASS ' . $pass . ' / FAIL ' . $fail . PHP_EOL;
exit($fail > 0 ? 1 : 0);

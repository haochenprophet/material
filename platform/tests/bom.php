<?php
declare(strict_types=1);

/**
 * BOM CSV 解析测试。用法：php platform/tests/bom.php
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

echo '物格云 BOM parser tests · v' . APP_VERSION . PHP_EOL . PHP_EOL;

$r = BomCsvParser::parse("mpn,qty,desc\nTE 170364-1,200,4Pin\nGRM188,5000,0402\n");
check('标准表头 2 行', $r['count'] === 2 && $r['errors'] === []);
check('型号/数量解析', $r['lines'][0]['mpn'] === 'TE 170364-1' && $r['lines'][0]['qty'] === 200.0);

$r = BomCsvParser::parse("型号,数量,描述\nSTM32,120,MCU\n");
check('中文表头', $r['count'] === 1 && $r['lines'][0]['mpn'] === 'STM32');

$r = BomCsvParser::parse("mpn\tqty\tdesc\nMolex\t300\tconn\n");
check('Tab 分隔', $r['count'] === 1 && $r['delimiter'] === 'tab');

$r = BomCsvParser::parse("\xEF\xBB\xBFmpn,qty\nTE,200\n");
check('UTF-8 BOM 去除', $r['count'] === 1 && $r['lines'][0]['mpn'] === 'TE');

$r = BomCsvParser::parse("TE,200,no header\n");
check('无表头回退', $r['count'] === 1 && $r['lines'][0]['qty'] === 200.0);

$r = BomCsvParser::parse("mpn,qty\nTE,,x\n");
check('缺数量报错', $r['count'] === 0 && count($r['errors']) === 1);

$r = BomCsvParser::parse("mpn,qty\n,10,x\n");
check('缺型号报错', $r['count'] === 0 && str_contains($r['errors'][0]['message'], '型号'));

$r = BomCsvParser::parse("mpn,qty\nTE,0\n");
check('数量 0 报错', $r['count'] === 0 && str_contains($r['errors'][0]['message'], '大于 0'));

$r = BomCsvParser::parse("mpn,qty,desc\nTE,200,\"conn, 4pin\"\n");
check('引号内逗号', $r['count'] === 1 && str_contains($r['lines'][0]['desc'], ','));

$csv = "mpn,qty\n";
for ($i = 1; $i <= 501; $i++) {
    $csv .= "P{$i},1\n";
}
$r = BomCsvParser::parse($csv);
check('超过 500 行报错', count($r['errors']) > 0 && $r['count'] === 500);

echo PHP_EOL . '结果：PASS ' . $pass . ' / FAIL ' . $fail . PHP_EOL;
exit($fail > 0 ? 1 : 0);

<?php
declare(strict_types=1);

/**
 * 旧 33 列映射测试。用法：php platform/tests/field-mapping.php
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

echo '物格云 field mapping tests · v' . APP_VERSION . PHP_EOL . PHP_EOL;
$rows = FieldMapping::all();
check('33 列', count($rows) === FieldMapping::LEGACY_COLUMNS);
check('旧列不重复', count(array_unique(array_column($rows, 'legacy'))) === 33);
$c = FieldMapping::coverage();
check('coverage total 33', $c['total'] === 33);
check('无 unmapped', $c['unmapped'] === 0);
check('mapped + derived', $c['mapped'] + $c['derived'] === 33);
check('目标表', in_array('material', FieldMapping::targetTables(), true) && in_array('listing', FieldMapping::targetTables(), true));
check('legacy_id 映射', in_array('material.legacy_id', array_column($rows, 'target'), true));
check('状态仅合法', array_diff(array_unique(array_column($rows, 'status')), ['mapped', 'derived', 'unmapped']) === []);
check('目标格式', count(array_filter($rows, static fn ($r) => str_contains($r['target'], '.'))) === 33);

echo PHP_EOL . '结果：PASS ' . $pass . ' / FAIL ' . $fail . PHP_EOL;
exit($fail > 0 ? 1 : 0);

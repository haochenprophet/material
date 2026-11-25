<?php
declare(strict_types=1);

/**
 * 领域/UI 单元测试（无框架依赖）。
 * 用法：php platform/tests/run.php
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    echo 'CLI only';
    exit(1);
}

require dirname(__DIR__) . '/app/bootstrap.php';

$pass = 0;
$fail = 0;
$failures = [];

function ok(string $name): void
{
    global $pass;
    $pass++;
    echo "  PASS  {$name}" . PHP_EOL;
}

function bad(string $name, string $detail = ''): void
{
    global $fail, $failures;
    $fail++;
    $failures[] = $name . ($detail !== '' ? ': ' . $detail : '');
    echo "  FAIL  {$name}" . ($detail !== '' ? "  ({$detail})" : '') . PHP_EOL;
}

function check(string $name, bool $condition, string $detail = ''): void
{
    $condition ? ok($name) : bad($name, $detail);
}

function same(string $name, mixed $expected, mixed $actual): void
{
    $condition = is_float($expected) || is_float($actual)
        ? abs((float) $expected - (float) $actual) < 0.001
        : $expected === $actual;
    check($name, $condition, 'expected=' . var_export($expected, true) . ' actual=' . var_export($actual, true));
}

echo '物格云 domain tests · v' . APP_VERSION . PHP_EOL . PHP_EOL;

// 1. 状态机
echo '[1/4] StateMachine' . PHP_EOL;
check('listing draft → pending_review', StateMachine::can('listing', 'draft', 'pending_review'));
check('listing draft → listed 拒绝', !StateMachine::can('listing', 'draft', 'listed'));
$threw = false;
try {
    StateMachine::assertCan('listing', 'draft', 'listed');
} catch (DomainException) {
    $threw = true;
}
check('assertCan 抛 DomainException', $threw);
check('order pending_payment → paid', StateMachine::can('order', 'pending_payment', 'paid'));
check('order shipped → pending_payment 拒绝', !StateMachine::can('order', 'shipped', 'pending_payment'));
check('settlement frozen → approved', StateMachine::can('settlement', 'frozen', 'approved'));
check('settlement pending → paid 拒绝', !StateMachine::can('settlement', 'pending', 'paid'));
check('qc in_progress → done', StateMachine::can('qc_task', 'in_progress', 'done'));
check('after_sales approved → returning', StateMachine::can('after_sales', 'approved', 'returning'));
same('状态机实体数', 6, count(StateMachine::all()));

// 2. 报价/佣金
echo '[2/4] Pricing' . PHP_EOL;
$quote = Pricing::quote(
    [['qty' => 10, 'unit' => 18.50], ['qty' => 100, 'unit' => 0.12]],
    ['qc_fee' => 20, 'fulfillment_fee' => 10, 'shipping' => 5, 'service_rate' => 0.03, 'tax_rate' => 0.13]
);
same('goods', 197.00, $quote['goods']);
same('service 3%', 5.91, $quote['service']);
same('subtotal', 237.91, $quote['subtotal']);
same('tax included', 27.37, $quote['tax_included']);
same('total', 237.91, $quote['total']);

$settle = Pricing::consignment(10000, 0.20, 300, 200, 100);
same('commission 20%', 2000.00, $settle['commission']);
same('seller net', 7400.00, $settle['seller_net']);
same('platform gross', 2000.00, $settle['platform_gross']);
same('buyer savings 45%', 0.45, Pricing::buyerSavings(100, 55));
same('buyer savings 0 new price', 0.0, Pricing::buyerSavings(0, 55));

// 3. BOM 匹配
echo '[3/4] BomMatcher' . PHP_EOL;
$result = BomMatcher::match([
    ['mpn' => 'TE 170364-1', 'qty' => 200, 'offers' => [
        ['supplier' => 'SZ', 'unit' => 18.50, 'qc' => 20, 'shipping' => 0],
        ['supplier' => 'SU', 'unit' => 19.00, 'qc' => 0, 'shipping' => 10],
    ]],
    ['mpn' => 'GRM188', 'qty' => 5000, 'offers' => [
        ['supplier' => 'SZ', 'unit' => 0.12, 'qc' => 0, 'shipping' => 5],
    ]],
    ['mpn' => 'ADUM1201', 'qty' => 80, 'offers' => []],
]);
same('matched', 2, $result['matched']);
same('shortage', 1, $result['shortage']);
same('total', 4325.00, $result['total']);
same('line1 quoted', 'quoted', $result['lines'][0]['status']);
same('line3 shortage', 'shortage', $result['lines'][2]['status']);
same('line1 best supplier', 'SZ', $result['lines'][0]['best']['supplier']);

// 4. UI 安全/组件
echo '[4/4] UI / security' . PHP_EOL;
$escaped = CuiHtml::e('<script>alert(1)</script>');
check('XSS 转义', !str_contains($escaped, '<script>'));
$btn = CuiButton::render(['label' => '保存', 'variant' => 'primary', 'icon' => 'check']);
check('Button 含 variant/icon', str_contains($btn, 'btn-primary') && str_contains($btn, '<svg'));
$table = CuiTable::render(['物料', '金额'], [['TE', '¥18.50']]);
check('Table 响应式 data-label', str_contains($table, 'data-label="物料"'));
$field = CuiField::render(['label' => '型号', 'name' => 'mpn', 'required' => true]);
check('Field name/required', str_contains($field, 'name="mpn"') && str_contains($field, 'required'));
check('CSRF token 长度', strlen(Cui::csrf()) >= 32);

echo PHP_EOL . '结果：PASS ' . $pass . ' / FAIL ' . $fail . PHP_EOL;
if ($fail > 0) {
    foreach ($failures as $f) {
        echo ' - ' . $f . PHP_EOL;
    }
    exit(1);
}
exit(0);

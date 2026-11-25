<?php
declare(strict_types=1);

/**
 * 佣金规则/计算测试（SQLite）。用法：php platform/tests/commission.php
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

echo '物格云 commission tests · v' . APP_VERSION . PHP_EOL . PHP_EOL;
$calc = new CommissionCalculator();
$c = $calc->calculate(100, 0.20);
check('20% 佣金', $c['commission'] === 20.0 && $c['seller_net'] === 80.0);
$c = $calc->calculate(100, 0.20, 5, null);
check('最低费用', $c['commission'] === 20.0);
$c = $calc->calculate(10, 0.20, 5, null);
check('最低费用生效', $c['commission'] === 5.0);
$c = $calc->calculate(1000, 0.20, 0, 50);
check('最高费用', $c['commission'] === 50.0 && $c['seller_net'] === 950.0);
check('非法比例', (static function () use ($calc) { try { $calc->calculate(100, 1.2); return false; } catch (Throwable) { return true; } })());

$pdo = new PDO('sqlite::memory:');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->exec('CREATE TABLE commission_rule (id INTEGER PRIMARY KEY AUTOINCREMENT,rule_key TEXT,version INT,category_id INT,seller_type TEXT,rate REAL,min_fee REAL,max_fee REAL,effective_from TEXT,effective_to TEXT,status TEXT,note TEXT)');
$pdo->exec("INSERT INTO commission_rule (rule_key,version,category_id,seller_type,rate,min_fee,max_fee,effective_from,effective_to,status,note) VALUES ('default',1,NULL,'any',0.20,0,NULL,'2026-01-01',NULL,'active','base')");
$pdo->exec("INSERT INTO commission_rule (rule_key,version,category_id,seller_type,rate,min_fee,max_fee,effective_from,effective_to,status,note) VALUES ('connector',1,11,'company',0.10,2,50,'2026-01-01',NULL,'active','connector')");
$repo = new PdoCommissionRuleRepository($pdo);
$r = $repo->resolve(11, 'company', '2026-11-25');
check('类目规则命中', $r['rule_key'] === 'connector' && $r['rate'] == 0.10);
$r2 = $repo->resolve(12, 'company', '2026-11-25');
check('默认规则回退', $r2['rule_key'] === 'default');
$id = $repo->create(['rule_key' => 'connector', 'category_id' => 11, 'seller_type' => 'company', 'rate' => 0.08, 'effective_from' => '2026-12-01']);
check('新版本创建', $id > 0);
$new = $repo->resolve(11, 'company', '2026-12-02');
check('新规则按日期生效', abs((float) $new['rate'] - 0.08) < 0.001);
check('停用规则', $repo->disable($id) === true);
check('停用不回退旧规则日期', $repo->resolve(11, 'company', '2026-12-02')['rule_key'] === 'connector');

echo PHP_EOL . '结果：PASS ' . $pass . ' / FAIL ' . $fail . PHP_EOL;
exit($fail > 0 ? 1 : 0);

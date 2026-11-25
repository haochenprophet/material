<?php
declare(strict_types=1);
/**
 * 佣金规则种子（幂等）。
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    echo 'CLI only';
    exit(1);
}
require dirname(__DIR__) . '/app/bootstrap.php';
$name = getenv('DB_NAME') ?: 'mats_platform_next2';
if (!preg_match('/^mats_platform_[a-z0-9_]{2,48}$/', $name)) { exit(1); }
$user = (string) getenv('DB_USER');
if ($user === '') { fwrite(STDERR, "DB_USER 未设置\n"); exit(2); }
$pdo = Database::pdo();
$repo = new PdoCommissionRuleRepository($pdo);
$existing = (int) $pdo->query("SELECT COUNT(*) FROM commission_rule WHERE rule_key='connector'")->fetchColumn();
if ($existing === 0) {
    $id = $repo->create(['rule_key' => 'connector', 'category_id' => 11, 'seller_type' => 'company', 'rate' => 0.08, 'min_fee' => 2, 'max_fee' => 50, 'effective_from' => '2026-01-01', 'note' => '连接器类目 8%']);
    echo 'created connector rule #' . $id . PHP_EOL;
} else {
    echo 'connector rule exists' . PHP_EOL;
}
echo 'rules: ' . (int) $pdo->query('SELECT COUNT(*) FROM commission_rule')->fetchColumn() . PHP_EOL;

<?php
declare(strict_types=1);

/**
 * 点评/信用/商家结构验证（真实 MySQL，只读）。
 * 未设置 DB_USER 时 SKIP。
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

echo '物格云 reputation live schema tests · v' . APP_VERSION . PHP_EOL . PHP_EOL;
if ((string) getenv('DB_USER') === '') {
    echo 'SKIP: DB_USER 未设置' . PHP_EOL;
    exit(0);
}
try {
    $pdo = Database::pdo();
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $tables = ['material_review', 'material_credit', 'merchant_credit', 'credit_event', 'review_reply', 'merchant_kyc', 'sandbox_payment', 'sandbox_payment_split', 'sandbox_refund'];
    $found = $pdo->query("SELECT table_name FROM information_schema.tables WHERE table_schema=DATABASE()")->fetchAll(PDO::FETCH_COLUMN) ?: [];
    foreach ($tables as $t) {
        check('表 ' . $t, in_array($t, $found, true));
    }
    $cols = $pdo->query("SELECT TABLE_NAME,COLUMN_NAME FROM information_schema.columns WHERE table_schema=DATABASE()")->fetchAll(PDO::FETCH_NUM) ?: [];
    $has = static function (array $cols, string $table, string $column): bool {
        foreach ($cols as $c) {
            if ((string) $c[0] === $table && (string) $c[1] === $column) { return true; }
        }
        return false;
    };
    check('review 评分列', $has($cols, 'material_review', 'rating'));
    check('review 真实交易列', $has($cols, 'material_review', 'verified_purchase'));
    check('comment 树列', $has($cols, 'material_comment', 'parent_id'));
    check('merchant 信用列', $has($cols, 'merchant_credit', 'grade'));

    $materials = (int) $pdo->query('SELECT COUNT(*) FROM material WHERE deleted_at IS NULL')->fetchColumn();
    $prov = (int) $pdo->query('SELECT COUNT(*) FROM material_provenance')->fetchColumn();
    $imgs = (int) $pdo->query("SELECT COUNT(DISTINCT material_id) FROM material_attachment WHERE type='image'")->fetchColumn();
    $revMats = (int) $pdo->query("SELECT COUNT(DISTINCT material_id) FROM material_review WHERE status='visible'")->fetchColumn();
    check('每物料有溯源', $prov >= $materials && $materials > 0, "{$prov}/{$materials}");
    check('每物料有图片', $imgs >= $materials, "{$imgs}/{$materials}");
    check('每物料有点评', $revMats >= $materials, "{$revMats}/{$materials}");
    check('商家信用已生成', (int) $pdo->query("SELECT COUNT(*) FROM merchant_credit WHERE review_count>0")->fetchColumn() >= 2);
    check('物料信用已生成', (int) $pdo->query("SELECT COUNT(*) FROM material_credit WHERE review_count>0")->fetchColumn() >= 1);
    check('信用事件有审计', (int) $pdo->query("SELECT COUNT(*) FROM credit_event")->fetchColumn() > 0);
    check('商家回复存在', (int) $pdo->query('SELECT COUNT(*) FROM review_reply')->fetchColumn() >= 1);
    check('商家 KYC 存在', (int) $pdo->query('SELECT COUNT(*) FROM merchant_kyc')->fetchColumn() >= 2);
} catch (Throwable $e) {
    echo 'FAIL: ' . $e->getMessage() . PHP_EOL;
    exit(1);
}

echo PHP_EOL . '结果：PASS ' . $pass . ' / FAIL ' . $fail . PHP_EOL;
exit($fail > 0 ? 1 : 0);

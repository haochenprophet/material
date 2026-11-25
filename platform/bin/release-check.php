<?php
declare(strict_types=1);

/**
 * 一键发布检查：verify + 全部离线测试 +（有 DB_USER 时）live 测试。
 * 用法：php platform/bin/release-check.php
 * 退出码 0 = 全部通过。
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    echo 'CLI only';
    exit(1);
}

require dirname(__DIR__) . '/app/bootstrap.php';

echo '物格云 release-check · v' . APP_VERSION . ' · ' . APP_RELEASE_ID . PHP_EOL;
echo 'PHP ' . PHP_VERSION . ' · ' . date('c') . PHP_EOL . PHP_EOL;

$php = escapeshellarg(PHP_BINARY);
$checks = [
    'verify' => PLATFORM_PATH . '/bin/verify.php',
    'domain' => PLATFORM_PATH . '/tests/run.php',
    'api' => PLATFORM_PATH . '/tests/api.php',
    'db' => PLATFORM_PATH . '/tests/db.php',
    'auth' => PLATFORM_PATH . '/tests/auth.php',
    'bom' => PLATFORM_PATH . '/tests/bom.php',
    'upload' => PLATFORM_PATH . '/tests/upload.php',
    'pdo' => PLATFORM_PATH . '/tests/pdo.php',
    'trade' => PLATFORM_PATH . '/tests/trade.php',
    'finance' => PLATFORM_PATH . '/tests/finance.php',
    'order_repo' => PLATFORM_PATH . '/tests/order-repo.php',
    'order_presenter' => PLATFORM_PATH . '/tests/order-presenter.php',
    'listing_repo' => PLATFORM_PATH . '/tests/listing-repo.php',
    'listing_writer' => PLATFORM_PATH . '/tests/listing-writer.php',
    'listing_review' => PLATFORM_PATH . '/tests/listing-review.php',
    'seller_listings' => PLATFORM_PATH . '/tests/seller-listings.php',
    'catalog' => PLATFORM_PATH . '/tests/catalog-source.php',
    'catalog_detail' => PLATFORM_PATH . '/tests/catalog-detail.php',
    'perf' => PLATFORM_PATH . '/tests/perf.php',
    'security' => PLATFORM_PATH . '/tests/security.php',
    'credit' => PLATFORM_PATH . '/tests/credit.php',
    'storefront' => PLATFORM_PATH . '/tests/storefront.php',
    'comment_tree' => PLATFORM_PATH . '/tests/comment-tree.php',
    'review_reply' => PLATFORM_PATH . '/tests/review-reply.php',
    'review_policy' => PLATFORM_PATH . '/tests/review-policy.php',
    'review_moderation' => PLATFORM_PATH . '/tests/review-moderation.php',
    'credit_history' => PLATFORM_PATH . '/tests/credit-history.php',
    'merchant_kyc' => PLATFORM_PATH . '/tests/merchant-kyc.php',
    'image_upload' => PLATFORM_PATH . '/tests/image-upload.php',
    'data_isolation' => PLATFORM_PATH . '/tests/data-isolation.php',
    'field_mapping' => PLATFORM_PATH . '/tests/field-mapping.php',
    'payment_sandbox' => PLATFORM_PATH . '/tests/payment-sandbox.php',
    'commission' => PLATFORM_PATH . '/tests/commission.php',
    'category_pages' => PLATFORM_PATH . '/tests/category-pages.php',
    'api_auth' => PLATFORM_PATH . '/tests/api-auth.php',
    'a11y' => PLATFORM_PATH . '/tests/a11y.php',
    'migration_extended' => PLATFORM_PATH . '/tests/migration-extended.php',
    'regression' => PLATFORM_PATH . '/tests/regression.php',
    'deploy' => PLATFORM_PATH . '/tests/deploy.php',
    'auth_db' => PLATFORM_PATH . '/tests/auth-db.php',
    'throttle' => PLATFORM_PATH . '/tests/throttle.php',
    'payment_callback' => PLATFORM_PATH . '/tests/payment-callback.php',
    'migration' => PLATFORM_PATH . '/tests/migration.php',
    'migration_runner' => PLATFORM_PATH . '/tests/migration-runner.php',
    'e2e' => PLATFORM_PATH . '/tests/e2e.php',
];
if ((string) getenv('DB_USER') !== '') {
    $checks['db_live'] = PLATFORM_PATH . '/tests/db-live.php';
    $checks['listing_live'] = PLATFORM_PATH . '/tests/listing-repo-live.php';
    $checks['auth_db_live'] = PLATFORM_PATH . '/tests/auth-db-live.php';
}

$failed = [];
foreach ($checks as $name => $script) {
    if (!is_file($script)) {
        echo str_pad($name, 20) . 'SKIP  release 分支无测试资产' . PHP_EOL;
        continue;
    }
    $output = [];
    $code = 0;
    exec($php . ' ' . escapeshellarg($script) . ' 2>&1', $output, $code);
    echo str_pad($name, 20) . ($code === 0 ? 'PASS' : 'FAIL') . '  ' . end($output) . PHP_EOL;
    if ($code !== 0) {
        $failed[] = $name;
    }
}

echo PHP_EOL . 'legacy: ';
foreach (['class/mysql.php', 'material.php', '.git'] as $rel) {
    echo $rel . '=' . (file_exists(MATERIAL_PATH . '/' . $rel) ? 'ok' : 'MISSING') . ' ';
}
echo PHP_EOL;
echo 'release: ' . ($failed === [] ? 'READY' : 'BLOCKED (' . implode(', ', $failed) . ')') . PHP_EOL;
exit($failed === [] ? 0 : 1);

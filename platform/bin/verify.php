<?php
declare(strict_types=1);

/**
 * v0.1.0 自检：语法 / 文件 / 类 / 组件 / 路由 / manifest / 旧代码存在性。
 * 用法：php platform/bin/verify.php
 * 退出码 0 = 全部通过；1 = 有失败。
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    echo 'CLI only';
    exit(1);
}

require dirname(__DIR__) . '/app/bootstrap.php';

putenv('AUTH_SOURCE=demo');
ob_start();

$pass = 0;
$fail = 0;
$warn = 0;
$failures = [];

function ok(string $name, string $detail = ''): void
{
    global $pass;
    $pass++;
    echo "  PASS  {$name}" . ($detail !== '' ? "  ({$detail})" : '') . PHP_EOL;
}

function bad(string $name, string $detail = ''): void
{
    global $fail, $failures;
    $fail++;
    $failures[] = $name . ($detail !== '' ? ': ' . $detail : '');
    echo "  FAIL  {$name}" . ($detail !== '' ? "  ({$detail})" : '') . PHP_EOL;
}

function warn(string $name, string $detail = ''): void
{
    global $warn;
    $warn++;
    echo "  WARN  {$name}" . ($detail !== '' ? "  ({$detail})" : '') . PHP_EOL;
}

echo '物格云 platform v' . APP_VERSION . ' · verify' . PHP_EOL;
echo 'PHP ' . PHP_VERSION . ' · ' . date('c') . PHP_EOL . PHP_EOL;

// 1. 环境
echo '[1/7] 环境' . PHP_EOL;
version_compare(PHP_VERSION, '8.2.0', '>=') ? ok('PHP >= 8.2') : bad('PHP >= 8.2', PHP_VERSION);
is_dir(PLATFORM_PATH) ? ok('platform 目录存在') : bad('platform 目录存在');
is_dir(MATERIAL_PATH . '/.git') ? ok('旧仓库 .git 存在（未动）') : warn('旧仓库 .git 不存在');

// 2. 必需文件
echo '[2/7] 必需文件' . PHP_EOL;
$required = [
    'public/index.php',
    'public/router.php',
    'public/assets/css/app.css',
    'public/assets/js/app.js',
    'app/bootstrap.php',
    'app/Router.php',
    'app/Api.php',
    'app/Security/Auth.php',
    'app/Security/Rbac.php',
    'app/Security/LoginThrottle.php',
    'app/Security/MerchantContext.php',
    'app/Security/ApiAuthException.php',
    'app/Security/ApiAuth.php',
    'app/Security/ApiRateLimiter.php',
    'app/Infra/Database.php',
    'app/Repository/MaterialRepository.php',
    'app/Repository/DemoMaterialRepository.php',
    'app/Repository/PdoMaterialRepository.php',
    'app/Repository/PdoListingRepository.php',
    'app/Repository/PdoListingWriter.php',
    'app/Repository/PdoListingReview.php',
    'app/Repository/PdoSellerListingRepository.php',
    'app/Repository/CatalogRepositoryFactory.php',
    'app/Repository/PdoAuthRepository.php',
    'app/Repository/PdoReviewRepository.php',
    'app/Repository/PdoCreditRepository.php',
    'app/Repository/PdoStoreRepository.php',
    'app/Repository/PdoCommerceRepository.php',
    'app/Repository/PdoMerchantKycRepository.php',
    'app/Repository/PdoMaterialImageRepository.php',
    'app/Repository/PdoSandboxPaymentRepository.php',
    'app/Repository/PdoCommissionRuleRepository.php',
    'app/Repository/PdoCategoryCatalogRepository.php',
    'app/Repository/ApiTokenRepository.php',
    'app/Repository/OrderRepository.php',
    'app/Repository/PdoOrderRepository.php',
    'database/dev-schema.sql',
    'database/014_category_catalog.sql',
    'bin/dev-db.php',
    'app/Storage/Storage.php',
    'app/Storage/LocalStorage.php',
    'app/Storage/ImageStorage.php',
    'app/Storage/LocalImageStorage.php',
    'app/Storage/ImageUploadService.php',
    'app/Storage/BomUploadService.php',
    'framework/components/CuiFileDrop.php',
    'config/database.php',
    'config/category-pages.php',
    'bin/db-check.php',
    'app/Data/DemoData.php',
    'app/Domain/StateMachine.php',
    'app/Domain/Pricing.php',
    'app/Domain/BomMatcher.php',
    'app/Domain/BomCsvParser.php',
    'app/Domain/OrderService.php',
    'app/Domain/SettlementService.php',
    'app/Domain/OrderStore.php',
    'app/Domain/OrderPresenter.php',
    'app/Domain/CatalogDetailPresenter.php',
    'app/Domain/CreditScoring.php',
    'app/Domain/ReviewPolicy.php',
    'app/Domain/CreditHistory.php',
    'app/Domain/FieldMapping.php',
    'app/Domain/MaterialCategory.php',
    'app/Domain/CommissionCalculator.php',
    'app/Domain/PaymentService.php',
    'app/Domain/LedgerService.php',
    'app/Domain/PaymentCallbackService.php',
    'app/Domain/IdempotencyStore.php',
    'app/Domain/ReconciliationService.php',
    'app/Domain/SqlMigrationInspector.php',
    'app/Domain/MigrationRunner.php',
    'bin/migration-audit.php',
    'bin/migration-plan.php',
    'bin/migration-run.php',
    'bin/db-status.php',
    'bin/release-check.php',
    'bin/seed-auth.php',
    'bin/seed-api-token.php',
    'bin/healthcheck.php',
    'bin/db-backup.php',
    'bin/schema-rebuild.php',
    'bin/status.php',
    'deploy/nginx.conf.example',
    'deploy/php-fpm-pool.conf.example',
    'deploy/README.md',
    'tests/run.php',
    'tests/bom.php',
    'tests/trade.php',
    'tests/finance.php',
    'tests/order-repo.php',
    'tests/order-presenter.php',
    'tests/listing-repo.php',
    'tests/listing-writer.php',
    'tests/credit.php',
    'tests/storefront.php',
    'tests/comment-tree.php',
    'tests/review-reply.php',
    'tests/review-policy.php',
    'tests/review-moderation.php',
    'tests/credit-history.php',
    'tests/merchant-kyc.php',
    'tests/image-upload.php',
    'tests/data-isolation.php',
    'tests/field-mapping.php',
    'tests/payment-sandbox.php',
    'tests/commission.php',
    'tests/category-pages.php',
    'tests/api-auth.php',
    'tests/a11y.php',
    'tests/migration-extended.php',
    'tests/regression.php',
    'tests/reputation-live.php',
    'tests/listing-review.php',
    'tests/seller-listings.php',
    'tests/catalog-source.php',
    'tests/catalog-detail.php',
    'tests/perf.php',
    'tests/security.php',
    'tests/deploy.php',
    'tests/listing-repo-live.php',
    'tests/payment-callback.php',
    'tests/migration.php',
    'tests/migration-runner.php',
    'tests/db-live.php',
    'framework/Cui.php',
    'framework/CuiHtml.php',
    'framework/CuiLayout.php',
    'framework/CuiPage.php',
    'framework/CuiSidebar.php',
    'framework/CuiBottomNav.php',
    'framework/CuiHeader.php',
    'framework/components/CuiButton.php',
    'framework/components/CuiTable.php',
    'framework/components/CuiField.php',
    'views/pages/home.php',
    'views/pages/search.php',
    'views/pages/detail.php',
    'views/pages/bom.php',
    'views/pages/quote.php',
    'views/pages/order.php',
    'views/pages/seller.php',
    'views/pages/qc.php',
    'views/pages/pda.php',
    'views/pages/admin.php',
    'views/pages/settlement.php',
    'views/pages/ui.php',
    'views/pages/trace.php',
    'views/pages/shops.php',
    'views/pages/shop.php',
    'views/pages/admin-credits.php',
    'views/pages/seller-kyc.php',
    'views/pages/admin-kyc.php',
    'views/pages/seller-images.php',
    'views/pages/admin-payments.php',
    'views/pages/admin-commission.php',
    'views/pages/category.php',
    'views/pages/admin-api-tokens.php',
    'views/pages/login.php',
    'views/pages/error.php',
    'docs/BASELINE.md',
    'docs/ROLLBACK.md',
    'docs/GIT-WORKFLOW.md',
    'docs/TRACEABILITY.md',
    'docs/BUSINESS-ALIGNMENT.md',
    'docs/CLOSURE-REPORT.md',
    'docs/API.md',
    'tests/api.php',
    'tests/db.php',
    'tests/auth.php',
    'tests/auth-db.php',
    'tests/auth-db-live.php',
    'tests/throttle.php',
    'tests/upload.php',
    'tests/pdo.php',
    'tests/e2e.php',
    'docs/DATA-LAYER.md',
    'docs/RBAC.md',
    'docs/BOM-PARSER.md',
    'docs/UPLOAD.md',
    'docs/E2E.md',
    'docs/TRADE.md',
    'docs/FINANCE.md',
    'docs/PAYMENT-CALLBACK.md',
    'docs/MIGRATION.md',
    'docs/MIGRATION-REHEARSAL.md',
    'docs/MIGRATION-RUNNER.md',
    'docs/MIGRATION-EXECUTION.md',
    'docs/DB-STATUS.md',
    'docs/LISTING-REPOSITORY.md',
    'docs/CATALOG-SOURCE.md',
    'docs/SECURITY-PERF.md',
    'docs/ACCEPTANCE.md',
    'docs/RELEASE-CHECKLIST.md',
    'docs/RISK-REGISTER.md',
    'docs/AUTH-DB.md',
    'docs/STATUS.md',
    'docs/TEST-MATRIX.md',
    'docs/GIT-INTEGRITY.md',
    'docs/UI-QA.md',
    'docs/REPUTATION-PLAN.md',
    'docs/MERCHANT-KYC.md',
    'docs/IMAGE-UPLOAD.md',
    'docs/FIELD-MAPPING.md',
    'docs/PAYMENT-SANDBOX.md',
    'docs/COMMISSION.md',
    'docs/CATEGORY-PAGES.md',
    'docs/CATEGORY-TASK-QUEUE.md',
    'docs/API-AUTH.md',
    'docs/A11Y-PERF.md',
    'docs/BACKUP.md',
    'docs/MIGRATION-REBUILD.md',
    'docs/REGRESSION.md',
    'docs/10H-REVIEW.md',
    'docs/SELLER-REVIEW-FLOW.md',
    '.env.example',
    'bin/checkpoint.php',
    'bin/git-release.php',
    'docs/10H-PLAN.md',
    'README.md',
    'CHANGELOG.md',
];
foreach ($required as $rel) {
    // release 分支（无 tests/seed 资产）跳过测试与演示种子文件；ai-test 分支仍完整检查。
    if (!is_dir(PLATFORM_PATH . '/tests') && (str_starts_with($rel, 'tests/') || str_starts_with($rel, 'bin/seed-'))) {
        continue;
    }
    is_file(PLATFORM_PATH . '/' . $rel) ? ok($rel) : bad($rel);
}

// 3. PHP 语法（token_get_all TOKEN_PARSE）
echo '[3/7] PHP 语法' . PHP_EOL;
$it = new RecursiveIteratorIterator(
    new RecursiveCallbackFilterIterator(
        new RecursiveDirectoryIterator(PLATFORM_PATH, FilesystemIterator::SKIP_DOTS),
        static function (SplFileInfo $file): bool {
            $path = $file->getPathname();
            if (str_contains($path, DIRECTORY_SEPARATOR . 'releases' . DIRECTORY_SEPARATOR)) {
                return false;
            }
            if (str_contains($path, DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR)) {
                return false;
            }
            return true;
        }
    )
);
$phpCount = 0;
foreach ($it as $file) {
    if (!$file instanceof SplFileInfo || !$file->isFile() || $file->getExtension() !== 'php') {
        continue;
    }
    $phpCount++;
    $source = (string) file_get_contents($file->getPathname());
    try {
        token_get_all($source, TOKEN_PARSE);
    } catch (ParseError $e) {
        bad('syntax ' . str_replace(PLATFORM_PATH . '/', '', $file->getPathname()), $e->getMessage());
    }
}
$phpCount > 0 ? ok('扫描 PHP 文件', (string) $phpCount) : bad('扫描 PHP 文件');

// 4. 类与组件
echo '[4/7] 类与组件' . PHP_EOL;
$classes = ['Cui', 'CuiHtml', 'CuiTheme', 'CuiIcon', 'CuiLayout', 'CuiPage', 'CuiHeader', 'CuiSidebar', 'CuiBottomNav', 'CuiButton', 'CuiBadge', 'CuiCard', 'CuiStat', 'CuiChips', 'CuiBreadcrumb', 'CuiPageHeader', 'CuiField', 'CuiTable', 'CuiEmpty', 'CuiProductCard', 'CuiMatchCard', 'CuiStepper', 'CuiUploader', 'CuiQcItem', 'CuiKpi', 'CuiRouter', 'DemoData', 'StateMachine', 'Pricing', 'BomMatcher', 'Api', 'Database', 'MaterialRepository', 'DemoMaterialRepository', 'Auth', 'Rbac', 'BomCsvParser', 'Storage', 'LocalStorage', 'BomUploadService', 'CuiFileDrop', 'PdoMaterialRepository', 'PdoListingRepository', 'PdoListingWriter', 'PdoListingReview', 'PdoSellerListingRepository', 'CatalogRepositoryFactory', 'OrderService', 'SettlementService', 'OrderStore', 'OrderPresenter', 'CatalogDetailPresenter', 'PaymentService', 'LedgerService', 'OrderRepository', 'PdoOrderRepository', 'PaymentCallbackService', 'IdempotencyStore', 'ReconciliationService', 'SqlMigrationInspector', 'MigrationRunner', 'PdoAuthRepository', 'LoginThrottle', 'CreditScoring', 'PdoReviewRepository', 'PdoCreditRepository', 'PdoStoreRepository', 'PdoCommerceRepository', 'ReviewPolicy', 'CreditHistory', 'PdoMerchantKycRepository', 'ImageStorage', 'LocalImageStorage', 'ImageUploadService', 'PdoMaterialImageRepository', 'MerchantContext', 'FieldMapping', 'PdoSandboxPaymentRepository', 'CommissionCalculator', 'PdoCommissionRuleRepository', 'MaterialCategory', 'PdoCategoryCatalogRepository', 'ApiAuth', 'ApiRateLimiter', 'ApiTokenRepository', 'ApiAuthException'];
foreach ($classes as $class) {
    $exists = in_array($class, ['MaterialRepository', 'Storage', 'ImageStorage', 'OrderRepository'], true) ? interface_exists($class) : class_exists($class);
    $exists ? ok('class ' . $class) : bad('class ' . $class);
}

$button = CuiButton::render(['label' => '测试', 'variant' => 'primary', 'icon' => 'upload']);
str_contains($button, '<button') && str_contains($button, 'btn-primary') && str_contains($button, '<svg') ? ok('CuiButton 输出') : bad('CuiButton 输出', $button);
$table = CuiTable::render(['物料', '金额'], [['TE', '¥18.50']]);
str_contains($table, 'data-label="物料"') && str_contains($table, '<table') ? ok('CuiTable 输出') : bad('CuiTable 输出');
$field = CuiField::render(['label' => '型号', 'name' => 'mpn', 'required' => true]);
str_contains($field, 'name="mpn"') && str_contains($field, 'required') ? ok('CuiField 输出') : bad('CuiField 输出');
$escaped = CuiHtml::e('<script>alert(1)</script>');
!str_contains($escaped, '<script>') ? ok('转义 XSS') : bad('转义 XSS', $escaped);

// 5. 路由渲染（CLI 捕获 HTML）
echo '[5/7] 路由渲染' . PHP_EOL;
$routes = [
    '/', '/search', '/category/resistor', '/p/p1', '/bom', '/bom/B-1',
    '/orders/SO-20261124-0081', '/seller', '/seller/listings/new',
    '/qc/QC-20261124-018', '/ops/pick/P-1024', '/pda', '/admin/orders',
    '/seller/settlements', '/ui', '/trace', '/login', '/shops', '/shop/hqb-industrial', '/admin/credits',
];
Auth::logout();
Auth::attempt('admin@demo', 'demo123') ? ok('admin 登录（路由烟测）') : bad('admin 登录（路由烟测）');
foreach ($routes as $path) {
    $_SERVER['REQUEST_METHOD'] = 'GET';
    $_SERVER['REQUEST_URI'] = $path;
    $_GET = [];
    http_response_code(200);
    ob_start();
    try {
        CuiRouter::dispatch();
        $html = (string) ob_get_clean();
    } catch (Throwable $e) {
        ob_end_clean();
        bad('route ' . $path, $e->getMessage());
        continue;
    }
    $good = str_contains($html, '<html') && str_contains($html, '</html>') && !str_contains($html, 'Fatal error');
    $good ? ok('route ' . $path, strlen($html) . ' bytes') : bad('route ' . $path, '输出不完整');
}

// 404
$_SERVER['REQUEST_URI'] = '/__not_found__';
$_GET = [];
http_response_code(200);
ob_start();
CuiRouter::dispatch();
$html404 = (string) ob_get_clean();
str_contains($html404, '页面不存在') || str_contains($html404, '404') ? ok('route 404') : bad('route 404');

// API 只读路由（CLI 捕获 JSON）
echo '[5b/7] API' . PHP_EOL;
$apiRoutes = ['/api/v1/health', '/api/v1/materials', '/api/v1/materials/p1', '/api/v1/orders/SO-20261124-0081', '/api/v1/state'];
foreach ($apiRoutes as $path) {
    ob_start();
    Api::dispatch('GET', $path);
    $raw = (string) ob_get_clean();
    $json = json_decode($raw, true);
    $good = is_array($json) && ($json['error'] ?? null) === null && isset($json['meta']['request_id']);
    $good ? ok('api ' . $path, strlen($raw) . ' bytes') : bad('api ' . $path, $raw);
}
Api::withBody([
    'lines' => [['qty' => 10, 'unit' => 18.50]],
    'options' => ['qc_fee' => 20, 'fulfillment_fee' => 10, 'shipping' => 5, 'service_rate' => 0.03, 'tax_rate' => 0.13],
]);
ob_start();
Api::dispatch('POST', '/api/v1/quotes');
$quoteRaw = (string) ob_get_clean();
$quoteJson = json_decode($quoteRaw, true);
Api::withBody(null);
(abs((float) ($quoteJson['data']['subtotal'] ?? 0) - 225.55) < 0.001) ? ok('api POST /quotes') : bad('api POST /quotes', $quoteRaw);
Api::withBody(['csv' => "mpn,qty\nTE,200\n"]);
ob_start();
Api::dispatch('POST', '/api/v1/bom/parse');
$parseRaw = (string) ob_get_clean();
$parseJson = json_decode($parseRaw, true);
Api::withBody(null);
(($parseJson['data']['count'] ?? 0) === 1) ? ok('api POST /bom/parse') : bad('api POST /bom/parse', $parseRaw);

// 6. manifest
echo '[6/7] manifest' . PHP_EOL;
$manifestFile = PLATFORM_PATH . '/manifest.json';
if (!is_file($manifestFile)) {
    warn('manifest.json 不存在', '先运行 php bin/snapshot.php（只更新 manifest）');
} else {
    $manifest = json_decode((string) file_get_contents($manifestFile), true);
    if (!is_array($manifest) || !is_array($manifest['files'] ?? null)) {
        bad('manifest 结构');
    } else {
        $badHash = 0;
        $missing = 0;
        foreach ($manifest['files'] as $rel => $meta) {
            $file = PLATFORM_PATH . '/' . $rel;
            if (!is_file($file)) {
                $missing++;
                continue;
            }
            if (!hash_equals((string) ($meta['sha256'] ?? ''), hash_file('sha256', $file))) {
                $badHash++;
            }
        }
        $missing === 0 ? ok('manifest 文件存在', (string) count($manifest['files'])) : bad('manifest 缺失文件', (string) $missing);
        $badHash === 0 ? ok('manifest SHA-256') : bad('manifest SHA-256', (string) $badHash);
    }
}

// 7. 旧代码存在性
echo '[7/7] 旧代码保护' . PHP_EOL;
$legacy = [
    'class/mysql.php', 'class/page.php', 'class/login.php', 'class/database.php',
    'material.php', 'header.php', 'login.php', 'register.php', '.git',
];
foreach ($legacy as $rel) {
    $path = MATERIAL_PATH . '/' . $rel;
    file_exists($path) ? ok('legacy ' . $rel) : bad('legacy ' . $rel . ' 丢失');
}

echo PHP_EOL . '结果：PASS ' . $pass . ' / FAIL ' . $fail . ' / WARN ' . $warn . PHP_EOL;
if ($fail > 0) {
    echo '失败项：' . PHP_EOL;
    foreach ($failures as $f) {
        echo ' - ' . $f . PHP_EOL;
    }
    $out = (string) ob_get_clean();
    echo $out;
    exit(1);
}
$out = (string) ob_get_clean();
echo $out;
exit(0);

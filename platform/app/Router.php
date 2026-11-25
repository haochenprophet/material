<?php
declare(strict_types=1);

/**
 * 极简路由。文件即路由的旧方式保留在 material/ 旧目录；
 * 新平台统一走 public/index.php，路由表集中在这里，便于回溯。
 */
final class CuiRouter
{
    /** @var array<string, array<string, mixed>> */
    private array $routes;
    private string $base;

    public function __construct()
    {
        $this->base = APP_BASE_URL;
        $this->routes = [
            '/' => ['view' => 'home', 'layout' => ['title' => '首页', 'active' => 'home']],
            '/search' => ['view' => 'search', 'layout' => ['title' => '寻料 / 搜索', 'active' => 'search']],
            '/category/{slug}' => ['view' => 'category', 'layout' => ['title' => '物料品类', 'active' => 'search']],
            '/p/{id}' => ['view' => 'detail', 'layout' => ['title' => '物料详情', 'active' => 'search']],
            '/bom' => ['view' => 'bom', 'layout' => ['title' => 'BOM 配单', 'active' => 'bom']],
            '/bom/{id}' => ['view' => 'quote', 'layout' => ['title' => '匹配与报价', 'active' => 'bom']],
            '/shops' => ['view' => 'shops', 'layout' => ['title' => '物料商城', 'active' => 'shops']],
            '/shop/{slug}' => ['view' => 'shop', 'layout' => ['title' => '商家商城', 'active' => 'shops']],
            '/orders/{id}' => ['view' => 'order', 'permission' => 'order.view', 'layout' => ['title' => '订单详情', 'active' => 'orders']],
            '/seller' => ['view' => 'seller', 'permission' => 'listing.view', 'layout' => ['title' => '卖家工作台', 'active' => 'seller', 'variant' => 'seller']],
            '/seller/listings' => ['view' => 'seller-listings', 'permission' => 'listing.create', 'layout' => ['title' => '我的提报', 'active' => 'seller-listings', 'variant' => 'seller']],
            '/seller/listings/new' => ['view' => 'seller', 'permission' => 'listing.create', 'layout' => ['title' => '提报物料', 'active' => 'seller-new', 'variant' => 'seller']],
            '/seller/kyc' => ['view' => 'seller-kyc', 'permission' => 'listing.create', 'layout' => ['title' => '商家认证', 'active' => 'seller-kyc', 'variant' => 'seller']],
            '/seller/images' => ['view' => 'seller-images', 'permission' => 'listing.create', 'layout' => ['title' => '物料图片', 'active' => 'seller-images', 'variant' => 'seller']],
            '/seller/settlements' => ['view' => 'settlement', 'permission' => 'settlement.view', 'layout' => ['title' => '结算/发票', 'active' => 'seller-settle', 'variant' => 'seller']],
            '/qc/{id}' => ['view' => 'qc', 'permission' => 'qc.report', 'layout' => ['title' => '质检任务', 'active' => 'qc', 'variant' => 'ops']],
            '/ops/pick/{id}' => ['view' => 'pda', 'permission' => 'qc.report', 'layout' => ['title' => '拣货', 'layout' => 'pda']],
            '/pda' => ['view' => 'pda', 'permission' => 'qc.report', 'layout' => ['layout' => 'pda', 'title' => 'PDA 拣货']],
            '/admin/orders' => ['view' => 'admin', 'permission' => 'admin.view', 'layout' => ['title' => '运营后台', 'active' => 'admin', 'variant' => 'admin']],
            '/admin/listings' => ['view' => 'admin-listings', 'permission' => 'admin.view', 'layout' => ['title' => '提报审核', 'active' => 'admin-review', 'variant' => 'admin']],
            '/admin/credits' => ['view' => 'admin-credits', 'permission' => 'admin.view', 'layout' => ['title' => '信用与商家', 'active' => 'admin-credits', 'variant' => 'admin']],
            '/admin/kyc' => ['view' => 'admin-kyc', 'permission' => 'admin.view', 'layout' => ['title' => '商家认证', 'active' => 'admin-kyc', 'variant' => 'admin']],
            '/admin/payments' => ['view' => 'admin-payments', 'permission' => 'admin.view', 'layout' => ['title' => '沙箱支付', 'active' => 'admin-payments', 'variant' => 'admin']],
            '/admin/commission' => ['view' => 'admin-commission', 'permission' => 'admin.view', 'layout' => ['title' => '佣金规则', 'active' => 'admin-commission', 'variant' => 'admin']],
            '/admin/api-tokens' => ['view' => 'admin-api-tokens', 'permission' => 'admin.view', 'layout' => ['title' => 'API Token', 'active' => 'admin-api-tokens', 'variant' => 'admin']],
            '/ui' => ['view' => 'ui', 'layout' => ['title' => 'UI 组件库', 'active' => 'account']],
            '/trace' => ['view' => 'trace', 'layout' => ['title' => '版本与回溯', 'active' => 'account']],
            '/login' => ['view' => 'login', 'layout' => ['title' => '登录', 'layout' => 'auth']],
            '/health' => ['json' => true],
        ];
    }

    public static function dispatch(): void
    {
        $router = new self();
        $router->run($_SERVER['REQUEST_METHOD'] ?? 'GET', $_SERVER['REQUEST_URI'] ?? '/');
    }

    private function run(string $method, string $uri): void
    {
        $path = (string) (parse_url($uri, PHP_URL_PATH) ?: '/');
        if ($this->base !== '' && str_starts_with($path, $this->base)) {
            $path = substr($path, strlen($this->base)) ?: '/';
        }
        $path = '/' . trim($path, '/');
        if ($path === '//') {
            $path = '/';
        }

        if (str_starts_with($path, '/api/v1/')) {
            Api::dispatch($method, $path);
            return;
        }

        if ($path === '/health') {
            $manifest = $this->manifestSummary();
            Cui::json([
                'status' => 'ok',
                'app' => APP_NAME,
                'version' => APP_VERSION,
                'release' => APP_RELEASE_ID,
                'request_id' => APP_REQUEST_ID,
                'php' => PHP_VERSION,
                'time' => date('c'),
                'manifest' => $manifest,
            ]);
            return;
        }

        if (preg_match('#^/reviews/(\d+)/reply$#', $path, $m) === 1 && $method === 'POST') {
            $token = $_POST['_csrf'] ?? '';
            if (!is_string($token) || !hash_equals(Cui::csrf(), $token)) {
                http_response_code(403);
                return;
            }
            if (!Auth::can('admin.view') && !Auth::can('listing.create')) {
                http_response_code(403);
                return;
            }
            if (Database::configured()) {
                $pdo = Database::pdo();
                $stmt = $pdo->prepare('SELECT listing_id FROM material_review WHERE id=?');
                $stmt->execute([(int) $m[1]]);
                $listingId = (int) $stmt->fetchColumn();
                try {
                    (new PdoReviewRepository($pdo))->reply((int) $m[1], Auth::user() ?? [], (string) ($_POST['content'] ?? ''));
                    Cui::flash('review_result', '商家回复已发布');
                } catch (Throwable $e) {
                    Cui::flash('review_error', '回复失败：' . $e->getMessage());
                }
                if ($listingId > 0) {
                    header('Location: ' . Cui::url('/p/' . $listingId . '#reviews'));
                    return;
                }
            }
            header('Location: ' . Cui::url('/shops'));
            return;
        }

        if (preg_match('#^/p/(\d+)/reviews$#', $path, $m) === 1 && $method === 'POST') {
            $token = $_POST['_csrf'] ?? '';
            if (!is_string($token) || !hash_equals(Cui::csrf(), $token)) {
                Cui::flash('review_error', 'CSRF 校验失败');
                header('Location: ' . Cui::url('/p/' . (int) $m[1] . '#reviews'));
                return;
            }
            if (!Auth::check()) {
                header('Location: ' . Cui::url('/login?next=' . rawurlencode('/p/' . (int) $m[1] . '#reviews')));
                return;
            }
            if (!Database::configured()) {
                Cui::flash('review_error', '演示模式无数据库，不能点评');
                header('Location: ' . Cui::url('/p/' . (int) $m[1] . '#reviews'));
                return;
            }
            try {
                $pdo = Database::pdo();
                $stmt = $pdo->prepare("SELECT material_id,store_id FROM listing WHERE id=? AND status='listed' AND deleted_at IS NULL");
                $stmt->execute([(int) $m[1]]);
                $row = $stmt->fetch();
                if (!is_array($row)) {
                    throw new RuntimeException('物料不存在或未上架');
                }
                $u = Auth::user() ?? [];
                $reviewRepo = new PdoReviewRepository($pdo);
                $reviewerKey = $reviewRepo->reviewerKey(Auth::id(), (string) ($u['account'] ?? ''));
                if ($reviewRepo->hasReviewed($reviewerKey, (int) $m[1])) {
                    throw new RuntimeException('你已点评过该物料');
                }
                if ($reviewRepo->recentCount($reviewerKey, 60) >= ReviewPolicy::MAX_PER_HOUR) {
                    throw new RuntimeException('点评过于频繁，请稍后再试');
                }
                $id = $reviewRepo->create([
                    'material_id' => (int) $row['material_id'],
                    'store_id' => (int) $row['store_id'],
                    'listing_id' => (int) $m[1],
                    'user_id' => Auth::id(),
                    'reviewer_key' => $reviewerKey,
                    'author_name' => (string) ($u['name'] ?? '买家'),
                    'author_role' => (string) ($u['role'] ?? 'buyer'),
                    'rating' => (int) ($_POST['rating'] ?? 0),
                    'quality' => (int) ($_POST['quality'] ?? 0),
                    'price' => (int) ($_POST['price'] ?? 0),
                    'delivery' => (int) ($_POST['delivery'] ?? 0),
                    'service' => (int) ($_POST['service'] ?? 0),
                    'content' => (string) ($_POST['content'] ?? ''),
                    'verified_purchase' => (int) ($_POST['verified_purchase'] ?? 0),
                ]);
                $credit = new PdoCreditRepository($pdo);
                $credit->recalculateMaterial((int) $row['material_id']);
                $credit->recalculateMerchant((int) $row['store_id']);
                Cui::flash('review_result', '点评已发布 #' . $id);
            } catch (Throwable $e) {
                Cui::flash('review_error', '点评失败：' . (APP_DEBUG ? $e->getMessage() : '请检查内容'));
            }
            header('Location: ' . Cui::url('/p/' . (int) $m[1] . '#reviews'));
            return;
        }

        if (preg_match('#^/admin/merchants/(\d+)/status$#', $path, $m) === 1 && $method === 'POST') {
            $token = $_POST['_csrf'] ?? '';
            if (!is_string($token) || !hash_equals(Cui::csrf(), $token) || !Auth::can('admin.view')) {
                http_response_code(403);
                return;
            }
            if (Database::configured()) {
                try {
                    (new PdoStoreRepository(Database::pdo()))->setStatus((int) $m[1], (string) ($_POST['status'] ?? 'paused'));
                    Cui::flash('credit_result', '商家状态已更新');
                } catch (Throwable $e) {
                    Cui::flash('credit_error', '更新失败：' . $e->getMessage());
                }
            }
            header('Location: ' . Cui::url('/admin/credits'));
            return;
        }

        if (preg_match('#^/admin/reviews/(\d+)/(approve|reject)$#', $path, $m) === 1 && $method === 'POST') {
            $token = $_POST['_csrf'] ?? '';
            if (!is_string($token) || !hash_equals(Cui::csrf(), $token) || !Auth::can('admin.view')) {
                http_response_code(403);
                return;
            }
            if (Database::configured()) {
                $pdo = Database::pdo();
                $stmt = $pdo->prepare('SELECT material_id,store_id FROM material_review WHERE id=?');
                $stmt->execute([(int) $m[1]]);
                $r = $stmt->fetch();
                $repo = new PdoReviewRepository($pdo);
                $ok = $m[2] === 'approve'
                    ? $repo->approve((int) $m[1], Auth::id(), (string) ($_POST['note'] ?? ''))
                    : $repo->reject((int) $m[1], Auth::id(), (string) ($_POST['note'] ?? ''));
                if (is_array($r)) {
                    $credit = new PdoCreditRepository($pdo);
                    $credit->recalculateMaterial((int) $r['material_id']);
                    if ((int) $r['store_id'] > 0) {
                        $credit->recalculateMerchant((int) $r['store_id']);
                    }
                }
                Cui::flash($ok ? 'credit_result' : 'credit_error', $ok ? '点评审核完成' : '状态已变化');
            }
            header('Location: ' . Cui::url('/admin/credits'));
            return;
        }

        if (preg_match('#^/reviews/(\d+)/appeal$#', $path, $m) === 1 && $method === 'POST') {
            $token = $_POST['_csrf'] ?? '';
            if (!is_string($token) || !hash_equals(Cui::csrf(), $token) || (!Auth::can('admin.view') && !Auth::can('listing.create'))) {
                http_response_code(403);
                return;
            }
            if (Database::configured()) {
                try {
                    (new PdoReviewRepository(Database::pdo()))->appeal((int) $m[1], (string) ($_POST['reason'] ?? ''));
                    Cui::flash('credit_result', '申诉已提交');
                } catch (Throwable $e) {
                    Cui::flash('credit_error', '申诉失败：' . $e->getMessage());
                }
            }
            header('Location: ' . Cui::url('/admin/credits'));
            return;
        }

        if (preg_match('#^/admin/appeals/(\d+)/resolve$#', $path, $m) === 1 && $method === 'POST') {
            $token = $_POST['_csrf'] ?? '';
            if (!is_string($token) || !hash_equals(Cui::csrf(), $token) || !Auth::can('admin.view')) {
                http_response_code(403);
                return;
            }
            if (Database::configured()) {
                try {
                    (new PdoReviewRepository(Database::pdo()))->resolveAppeal((int) $m[1], (string) ($_POST['status'] ?? ''), Auth::id());
                    Cui::flash('credit_result', '申诉已处理');
                } catch (Throwable $e) {
                    Cui::flash('credit_error', '申诉处理失败：' . $e->getMessage());
                }
            }
            header('Location: ' . Cui::url('/admin/credits'));
            return;
        }

        if (preg_match('#^/admin/reviews/(\d+)/hide$#', $path, $m) === 1 && $method === 'POST') {
            $token = $_POST['_csrf'] ?? '';
            if (!is_string($token) || !hash_equals(Cui::csrf(), $token) || !Auth::can('admin.view')) {
                http_response_code(403);
                return;
            }
            if (Database::configured()) {
                $pdo = Database::pdo();
                $stmt = $pdo->prepare('SELECT material_id,store_id FROM material_review WHERE id=?');
                $stmt->execute([(int) $m[1]]);
                $r = $stmt->fetch();
                (new PdoReviewRepository($pdo))->hide((int) $m[1]);
                if (is_array($r)) {
                    $credit = new PdoCreditRepository($pdo);
                    $credit->recalculateMaterial((int) $r['material_id']);
                    if ((int) $r['store_id'] > 0) {
                        $credit->recalculateMerchant((int) $r['store_id']);
                    }
                }
                Cui::flash('credit_result', '点评已隐藏并重算信用');
            }
            header('Location: ' . Cui::url('/admin/credits'));
            return;
        }

        if ($path === '/admin/credits/recalculate' && $method === 'POST') {
            $token = $_POST['_csrf'] ?? '';
            if (!is_string($token) || !hash_equals(Cui::csrf(), $token) || !Auth::can('admin.view')) {
                http_response_code(403);
                return;
            }
            if (Database::configured()) {
                (new PdoCreditRepository(Database::pdo()))->recalculateAll();
                Cui::flash('credit_result', '信用已全量重算');
            }
            header('Location: ' . Cui::url('/admin/credits'));
            return;
        }

        if (preg_match('#^/p/(\d+)/comments$#', $path, $m) === 1 && $method === 'POST') {
            $token = $_POST['_csrf'] ?? '';
            if (!is_string($token) || !hash_equals(Cui::csrf(), $token) || !Auth::check()) {
                http_response_code(403);
                return;
            }
            if (Database::configured()) {
                try {
                    $pdo = Database::pdo();
                    $stmt = $pdo->prepare("SELECT material_id FROM listing WHERE id=? AND status='listed' AND deleted_at IS NULL");
                    $stmt->execute([(int) $m[1]]);
                    $materialId = (int) $stmt->fetchColumn();
                    if ($materialId <= 0) {
                        throw new RuntimeException('物料不存在');
                    }
                    (new PdoCommerceRepository($pdo))->addComment(
                        $materialId,
                        isset($_POST['parent_id']) && $_POST['parent_id'] !== '' ? (int) $_POST['parent_id'] : null,
                        Auth::user() ?? [],
                        (string) ($_POST['content'] ?? '')
                    );
                    Cui::flash('comment_result', '评论已发布');
                } catch (Throwable $e) {
                    Cui::flash('comment_error', '评论失败：' . $e->getMessage());
                }
            }
            header('Location: ' . Cui::url('/p/' . (int) $m[1] . '#comments'));
            return;
        }

        if (preg_match('#^/admin/listings/(\d+)/approve$#', $path, $m) === 1 && $method === 'POST') {            $token = $_POST['_csrf'] ?? '';
            if (!is_string($token) || !hash_equals(Cui::csrf(), $token)) {
                Cui::flash('review_error', 'CSRF 校验失败');
                header('Location: ' . Cui::url('/admin/listings'));
                return;
            }
            if (!Auth::can('admin.view')) {
                http_response_code(403);
                Cui::json(['error' => ['code' => 'FORBIDDEN', 'message' => '无审核权限']], 403);
                return;
            }
            if (!Database::configured()) {
                Cui::flash('review_error', '演示模式无数据库，不能审核');
                header('Location: ' . Cui::url('/admin/listings'));
                return;
            }
            $ok = (new PdoListingReview(Database::pdo()))->approve((int) $m[1], Auth::id());
            Cui::flash($ok ? 'review_result' : 'review_error', $ok ? 'listing 已上架' : '状态已变化，未上架');
            header('Location: ' . Cui::url('/admin/listings'));
            return;
        }

        if (preg_match('#^/admin/listings/(\d+)/reject$#', $path, $m) === 1 && $method === 'POST') {
            $token = $_POST['_csrf'] ?? '';
            if (!is_string($token) || !hash_equals(Cui::csrf(), $token)) {
                Cui::flash('review_error', 'CSRF 校验失败');
                header('Location: ' . Cui::url('/admin/listings'));
                return;
            }
            if (!Auth::can('admin.view')) {
                http_response_code(403);
                Cui::json(['error' => ['code' => 'FORBIDDEN', 'message' => '无审核权限']], 403);
                return;
            }
            if (!Database::configured()) {
                Cui::flash('review_error', '演示模式无数据库，不能驳回');
                header('Location: ' . Cui::url('/admin/listings'));
                return;
            }
            $reason = trim((string) ($_POST['reason'] ?? ''));
            $ok = (new PdoListingReview(Database::pdo()))->reject((int) $m[1], Auth::id(), $reason);
            Cui::flash($ok ? 'review_result' : 'review_error', $ok ? '已驳回并记录原因' : '状态已变化，未驳回');
            header('Location: ' . Cui::url('/admin/listings'));
            return;
        }

        if (preg_match('#^/seller/images/(\d+)/upload$#', $path, $m) === 1 && $method === 'POST') {
            $token = $_POST['_csrf'] ?? '';
            if (!is_string($token) || !hash_equals(Cui::csrf(), $token) || !Auth::can('listing.create')) {
                http_response_code(403);
                return;
            }
            if (Database::configured()) {
                try {
                    $pdo = Database::pdo();
                    $imgRepo = new PdoMaterialImageRepository($pdo);
                    $storeId = $imgRepo->storeIdForMaterial((int) $m[1]);
                    if (!MerchantContext::canAccessStore((int) $storeId)) {
                        throw new RuntimeException('无权操作该物料图片');
                    }
                    $service = new ImageUploadService($pdo, new LocalImageStorage(), $imgRepo);
                    $service->handle((int) $m[1], $_FILES['image'] ?? [], isset($_POST['is_primary']));
                    Cui::flash('image_result', '图片已上传');
                } catch (Throwable $e) {
                    Cui::flash('image_error', '上传失败：' . $e->getMessage());
                }
            }
            header('Location: ' . Cui::url('/seller/images?material_id=' . (int) $m[1]));
            return;
        }

        if (preg_match('#^/seller/images/(\d+)/(\d+)/(primary|delete)$#', $path, $m) === 1 && $method === 'POST') {
            $token = $_POST['_csrf'] ?? '';
            if (!is_string($token) || !hash_equals(Cui::csrf(), $token) || !Auth::can('listing.create')) {
                http_response_code(403);
                return;
            }
            if (Database::configured()) {
                $repo = new PdoMaterialImageRepository(Database::pdo());
                $storeId = $repo->storeIdForMaterial((int) $m[1]);
                if (!MerchantContext::canAccessStore((int) $storeId)) {
                    http_response_code(403);
                    return;
                }
                $ok = $m[3] === 'primary' ? $repo->setPrimary((int) $m[1], (int) $m[2]) : $repo->softDelete((int) $m[1], (int) $m[2]);
                Cui::flash($ok ? 'image_result' : 'image_error', $ok ? '图片已更新' : '图片不存在');
            }
            header('Location: ' . Cui::url('/seller/images?material_id=' . (int) $m[1]));
            return;
        }

        if ($path === '/admin/payments/create' && $method === 'POST') {
            $token = $_POST['_csrf'] ?? '';
            if (!is_string($token) || !hash_equals(Cui::csrf(), $token) || !Auth::can('admin.view')) {
                http_response_code(403);
                return;
            }
            if (Database::configured()) {
                try {
                    (new PdoSandboxPaymentRepository(Database::pdo()))->create(
                        (string) ($_POST['order_no'] ?? ''),
                        (float) ($_POST['amount'] ?? 0),
                        (string) ($_POST['idempotency_key'] ?? ''),
                        (string) ($_POST['channel'] ?? 'wechat')
                    );
                    Cui::flash('pay_result', '沙箱支付已创建');
                } catch (Throwable $e) {
                    Cui::flash('pay_error', '创建失败：' . $e->getMessage());
                }
            }
            header('Location: ' . Cui::url('/admin/payments'));
            return;
        }

        if (preg_match('#^/admin/payments/([^/]+)/(capture|refund)$#', $path, $m) === 1 && $method === 'POST') {
            $token = $_POST['_csrf'] ?? '';
            if (!is_string($token) || !hash_equals(Cui::csrf(), $token) || !Auth::can('admin.view')) {
                http_response_code(403);
                return;
            }
            if (Database::configured()) {
                try {
                    $repo = new PdoSandboxPaymentRepository(Database::pdo());
                    if ($m[2] === 'capture') {
                        $repo->capture(rawurldecode($m[1]));
                        Cui::flash('pay_result', '支付已捕获并分账');
                    } else {
                        $repo->refund(rawurldecode($m[1]), (float) ($_POST['amount'] ?? 0), (string) ($_POST['reason'] ?? ''));
                        Cui::flash('pay_result', '退款已记录');
                    }
                } catch (Throwable $e) {
                    Cui::flash('pay_error', '操作失败：' . $e->getMessage());
                }
            }
            header('Location: ' . Cui::url('/admin/payments'));
            return;
        }

        if ($path === '/admin/api-tokens/create' && $method === 'POST') {
            $token = $_POST['_csrf'] ?? '';
            if (!is_string($token) || !hash_equals(Cui::csrf(), $token) || !Auth::can('admin.view')) {
                http_response_code(403);
                return;
            }
            if (Database::configured()) {
                try {
                    $scopes = array_values(array_filter((array) ($_POST['scopes'] ?? []), 'is_string'));
                    $created = (new ApiTokenRepository(Database::pdo()))->create((string) ($_POST['client_name'] ?? ''), $scopes, (int) ($_POST['rate_limit'] ?? 60));
                    Cui::flash('api_token_plain', $created['token']);
                    Cui::flash('api_result', 'Token 已创建');
                } catch (Throwable $e) {
                    Cui::flash('api_error', '创建失败：' . $e->getMessage());
                }
            }
            header('Location: ' . Cui::url('/admin/api-tokens'));
            return;
        }

        if (preg_match('#^/admin/api-tokens/(\d+)/revoke$#', $path, $m) === 1 && $method === 'POST') {
            $token = $_POST['_csrf'] ?? '';
            if (!is_string($token) || !hash_equals(Cui::csrf(), $token) || !Auth::can('admin.view')) {
                http_response_code(403);
                return;
            }
            if (Database::configured()) {
                (new ApiTokenRepository(Database::pdo()))->revoke((int) $m[1]);
                Cui::flash('api_result', 'Token 已吊销');
            }
            header('Location: ' . Cui::url('/admin/api-tokens'));
            return;
        }

        if ($path === '/admin/commission/create' && $method === 'POST') {
            $token = $_POST['_csrf'] ?? '';
            if (!is_string($token) || !hash_equals(Cui::csrf(), $token) || !Auth::can('admin.view')) {
                http_response_code(403);
                return;
            }
            if (Database::configured()) {
                try {
                    (new PdoCommissionRuleRepository(Database::pdo()))->create([
                        'rule_key' => (string) ($_POST['rule_key'] ?? ''),
                        'category_id' => ($_POST['category_id'] ?? '') !== '' ? (int) $_POST['category_id'] : null,
                        'seller_type' => (string) ($_POST['seller_type'] ?? 'any'),
                        'rate' => (float) ($_POST['rate'] ?? 0),
                        'min_fee' => (float) ($_POST['min_fee'] ?? 0),
                        'max_fee' => (string) ($_POST['max_fee'] ?? ''),
                        'effective_from' => (string) ($_POST['effective_from'] ?? date('Y-m-d')),
                        'effective_to' => (string) ($_POST['effective_to'] ?? ''),
                        'note' => 'admin created',
                    ]);
                    Cui::flash('commission_result', '规则版本已创建');
                } catch (Throwable $e) {
                    Cui::flash('commission_error', '创建失败：' . $e->getMessage());
                }
            }
            header('Location: ' . Cui::url('/admin/commission'));
            return;
        }

        if (preg_match('#^/admin/commission/(\d+)/disable$#', $path, $m) === 1 && $method === 'POST') {
            $token = $_POST['_csrf'] ?? '';
            if (!is_string($token) || !hash_equals(Cui::csrf(), $token) || !Auth::can('admin.view')) {
                http_response_code(403);
                return;
            }
            if (Database::configured()) {
                (new PdoCommissionRuleRepository(Database::pdo()))->disable((int) $m[1]);
                Cui::flash('commission_result', '规则已停用');
            }
            header('Location: ' . Cui::url('/admin/commission'));
            return;
        }

        if ($path === '/seller/kyc' && $method === 'POST') {
            $token = $_POST['_csrf'] ?? '';
            if (!is_string($token) || !hash_equals(Cui::csrf(), $token) || !Auth::can('listing.create')) {
                http_response_code(403);
                return;
            }
            if (!isset($_POST['agreement'])) {
                Cui::flash('kyc_error', '请先同意商家协议');
                header('Location: ' . Cui::url('/seller/kyc'));
                return;
            }
            if (Database::configured()) {
                try {
                    $storeId = MerchantContext::storeId();
                    if ($storeId === null) {
                        throw new RuntimeException('没有可用的商家店铺');
                    }
                    (new PdoMerchantKycRepository(Database::pdo()))->submit($storeId, [
                        'legal_name' => (string) ($_POST['legal_name'] ?? ''),
                        'license_no' => (string) ($_POST['license_no'] ?? ''),
                        'tax_no' => (string) ($_POST['tax_no'] ?? ''),
                        'legal_person' => (string) ($_POST['legal_person'] ?? ''),
                        'contact_name' => (string) ($_POST['contact_name'] ?? ''),
                        'contact_phone' => (string) ($_POST['contact_phone'] ?? ''),
                        'deposit_amount' => (float) ($_POST['deposit_amount'] ?? 0),
                        'agreement_version' => (string) ($_POST['agreement_version'] ?? 'v1'),
                        'agreement_signed_at' => date('Y-m-d H:i:s'),
                        'documents' => ['business_license' => 'pending_upload'],
                    ]);
                    Cui::flash('kyc_result', '认证资料已提交，等待审核');
                } catch (Throwable $e) {
                    Cui::flash('kyc_error', '提交失败：' . $e->getMessage());
                }
            }
            header('Location: ' . Cui::url('/seller/kyc'));
            return;
        }

        if (preg_match('#^/admin/kyc/(\d+)/review$#', $path, $m) === 1 && $method === 'POST') {
            $token = $_POST['_csrf'] ?? '';
            if (!is_string($token) || !hash_equals(Cui::csrf(), $token) || !Auth::can('admin.view')) {
                http_response_code(403);
                return;
            }
            if (Database::configured()) {
                try {
                    (new PdoMerchantKycRepository(Database::pdo()))->review((int) $m[1], (string) ($_POST['status'] ?? ''), Auth::id(), (string) ($_POST['note'] ?? ''));
                    Cui::flash('kyc_result', '认证状态已更新');
                } catch (Throwable $e) {
                    Cui::flash('kyc_error', '更新失败：' . $e->getMessage());
                }
            }
            header('Location: ' . Cui::url('/admin/kyc'));
            return;
        }

        if ($path === '/seller/listings' && $method === 'POST') {
            $token = $_POST['_csrf'] ?? '';
            if (!is_string($token) || !hash_equals(Cui::csrf(), $token)) {
                Cui::flash('seller_error', 'CSRF 校验失败');
                header('Location: ' . Cui::url('/seller/listings/new'));
                return;
            }
            if (!Auth::can('listing.create')) {
                header('Location: ' . Cui::url('/login?next=' . rawurlencode('/seller/listings/new')));
                return;
            }
            if (!Database::configured()) {
                Cui::flash('seller_error', '演示模式不落库；配置 DB_USER 后再提交');
                header('Location: ' . Cui::url('/seller/listings/new'));
                return;
            }
            try {
                $storeId = MerchantContext::storeId();
                if ($storeId === null) {
                    throw new RuntimeException('没有可用的商家店铺');
                }
                $id = (new PdoListingWriter(Database::pdo()))->create([
                    'part_number' => (string) ($_POST['part_number'] ?? ''),
                    'title' => (string) ($_POST['title'] ?? ''),
                    'qty' => (float) ($_POST['qty'] ?? 0),
                    'price' => (float) ($_POST['price'] ?? 0),
                    'spec' => (string) ($_POST['spec'] ?? ''),
                    'store_id' => $storeId,
                ], (string) ($_POST['idempotency_key'] ?? ''));
                Cui::flash('seller_result', '已提交审核，listing id=' . $id);
            } catch (Throwable $e) {
                Cui::flash('seller_error', '提交失败：' . (APP_DEBUG ? $e->getMessage() : '请稍后重试'));
            }
            header('Location: ' . Cui::url('/seller/listings/new'));
            return;
        }

        if ($path === '/bom/upload' && $method === 'POST') {
            $token = $_POST['_csrf'] ?? '';
            if (!is_string($token) || !hash_equals(Cui::csrf(), $token)) {
                Cui::flash('bom_error', 'CSRF 校验失败，请刷新页面重试');
                header('Location: ' . Cui::url('/bom'));
                return;
            }
            if (!Auth::can('bom.upload')) {
                header('Location: ' . Cui::url('/login?next=' . rawurlencode('/bom')));
                return;
            }
            $service = new BomUploadService(new LocalStorage());
            $result = $service->handle(is_array($_FILES['file'] ?? null) ? $_FILES['file'] : []);
            if ($result['ok']) {
                Cui::flash('bom_result', '已解析 ' . (int) $result['count'] . ' 行，原件已存储：' . $result['key']);
            } else {
                $first = $result['errors'][0]['message'] ?? ($result['message'] ?? '上传失败');
                Cui::flash('bom_error', (string) $first);
            }
            header('Location: ' . Cui::url('/bom'));
            return;
        }

        if ($path === '/logout') {
            Auth::logout();
            header('Location: ' . Cui::url('/login'));
            return;
        }

        if ($path === '/login' && $method === 'POST') {
            $token = $_POST['_csrf'] ?? '';
            if (!is_string($token) || !hash_equals(Cui::csrf(), $token)) {
                Cui::flash('login_error', 'CSRF 校验失败，请刷新页面重试');
                header('Location: ' . Cui::url('/login'));
                return;
            }
            $account = trim((string) ($_POST['account'] ?? ''));
            $password = (string) ($_POST['password'] ?? '');
            $throttle = new LoginThrottle();
            $limit = $throttle->check($account);
            if (!$limit['allowed']) {
                Cui::flash('login_error', '尝试次数过多，请 ' . (int) ceil($limit['retry_after'] / 60) . ' 分钟后再试');
                header('Location: ' . Cui::url('/login'));
                return;
            }
            if (Auth::attempt($account, $password)) {
                $throttle->clear($account);
                $next = (string) ($_POST['next'] ?? '/');
                if (!str_starts_with($next, '/') || str_starts_with($next, '//')) {
                    $next = '/';
                }
                header('Location: ' . Cui::url($next));
                return;
            }
            $throttle->failure($account);
            Cui::flash('login_error', '账号或密码错误（演示密码 demo123）');
            header('Location: ' . Cui::url('/login'));
            return;
        }

        if ($method !== 'GET' && $method !== 'HEAD') {
            http_response_code(405);
            Cui::json(['error' => ['code' => 'METHOD_NOT_ALLOWED', 'message' => '仅支持 GET']], 405);
            return;
        }

        foreach ($this->routes as $pattern => $route) {
            $params = $this->match($pattern, $path);
            if ($params === null) {
                continue;
            }
            if (!empty($route['json'])) {
                Cui::json(['status' => 'ok']);
                return;
            }
            $permission = (string) ($route['permission'] ?? '');
            if ($permission !== '' && !Auth::can($permission)) {
                if (!Auth::check()) {
                    header('Location: ' . Cui::url('/login?next=' . rawurlencode($path)));
                } else {
                    http_response_code(403);
                    CuiPage::render('error', [
                        'code' => 403,
                        'title' => '没有权限',
                        'message' => '当前账号没有访问该页面的权限。',
                    ], ['title' => '没有权限', 'active' => '']);
                }
                return;
            }
            $data = array_merge($route['data'] ?? [], $params);
            if (($route['view'] ?? '') === 'search') {
                $data['q'] = (string) ($_GET['q'] ?? '');
            }
            if (($route['view'] ?? '') === 'category' && MaterialCategory::find((string) ($params['slug'] ?? '')) === null) {
                CuiPage::notFound('品类不存在');
                return;
            }
            CuiPage::render((string) $route['view'], $data, (array) ($route['layout'] ?? []));
            return;
        }

        CuiPage::notFound();
    }

    /**
     * @return array<string, string>|null
     */
    private function match(string $pattern, string $path): ?array
    {
        if ($pattern === $path) {
            return [];
        }
        $regex = preg_replace('#\{([a-z_]+)\}#', '(?P<$1>[^/]+)', $pattern);
        if ($regex === null || !preg_match('#^' . $regex . '$#', $path, $m)) {
            return null;
        }
        $params = [];
        foreach ($m as $k => $v) {
            if (is_string($k)) {
                $params[$k] = rawurldecode((string) $v);
            }
        }
        return $params;
    }

    /** @return array{files:int, generated_at:string|null} */
    private function manifestSummary(): array
    {
        $file = PLATFORM_PATH . '/manifest.json';
        if (!is_file($file)) {
            return ['files' => 0, 'generated_at' => null];
        }
        $json = json_decode((string) file_get_contents($file), true);
        return [
            'files' => is_array($json['files'] ?? null) ? count($json['files']) : 0,
            'generated_at' => $json['generated_at'] ?? null,
        ];
    }
}

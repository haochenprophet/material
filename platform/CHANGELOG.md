# Changelog

## 0.58.2 — 2026-11-25

### Removed
- 删除 108 个历史 `platform/releases/` 代码副本；版本回溯改用 git tag/commit。
- `.gitignore` 不再忽略 `releases/`（目录已不存在，防止重新生成副本被隐藏）。
- 文档同步：ROLLBACK/GIT-WORKFLOW/RELEASE-CHECKLIST。

## 0.58.1 — 2026-11-25

### Fixed
- 统一 CLOSURE/TASK-QUEUE/ACCEPTANCE 的最终版本为 v0.58.1。

## 0.58.0 — 2026-11-25

### Changed
- 版本管控改为 **git commit + git tag**，不再复制 release 目录。
- `snapshot.php` 只更新 manifest/RELEASE（文件清单来自 `git ls-files`）。
- 新增 `bin/git-release.php`；新增 `docs/GIT-WORKFLOW.md` / `docs/BRANCHES.md`。
- `ai` 代码分支；`ai-test` 测试/demo 分支；数据库 schema 两分支一致。

## 0.57.0 — 2026-11-25

### Final
- Q01–Q16 全部 DONE，队列无 PENDING。
- 最终验收/CLOSURE 更新；v0.1.0 切片 GO，生产 NO-GO。
- verify 342 / release-check 48 / DB 92 / 旧库未变。

## 0.56.0 — 2026-11-25

### Added
- `tests/regression.php` 18 条：8091/8095/8096/8097。
- 8097 API 401/200；8095 商城/点评；8096 DB 登录门禁。
- `docs/REGRESSION.md`；Q15 DONE。

## 0.55.0 — 2026-11-25

### Added
- `bin/schema-rebuild.php`：基础 7 文件 + 007–013 重建成 92 表。
- `tests/migration-extended.php` 12 条；新库 `mats_platform_rebuild_20261125`。
- `docs/MIGRATION-REBUILD.md`；Q14 DONE。

## 0.54.0 — 2026-11-25

### Added
- `bin/healthcheck.php`：manifest/storage/DB/HTTP。
- `bin/db-backup.php`：仅 mats_platform_*，dry-run/execute，不覆盖。
- 实测备份 162KB；`docs/BACKUP.md`；deploy 测试 11 条。
- Q13 DONE。

## 0.53.0 — 2026-11-25

### Added
- `tests/a11y.php` 10 条：lang/viewport/skip-link/h1/button/label/alt。
- `docs/A11Y-PERF.md`：性能/无障碍/真机矩阵与缺口。
- 500/1440 截图复核；真机仍未测试；Q12 DONE（有限）。

## 0.52.0 — 2026-11-25

### Added
- API token/scope/限流：`api_token`/`api_rate_limit`，`API_REQUIRE_TOKEN` 开关。
- 401/403/429；`/admin/api-tokens` 创建/吊销。
- `tests/api-auth.php` 11 条；live 401/200；Q11 DONE。

## 0.51.0 — 2026-11-25

### Added
- 佣金规则版本化：`rule_key/version`，类目/卖家类型/生效日期解析。
- `CommissionCalculator` 最低/最高佣金；`/admin/commission` 试算/新增/停用。
- `tests/commission.php` 11 条；Q10 DONE（发票/真实结算落库仍待接）。

## 0.50.0 — 2026-11-25

### Added
- 沙箱支付/分账/退款/对账：`sandbox_payment` 等 3 表。
- `/admin/payments` 创建/捕获/退款/对账；卖家 90%/平台 10%。
- 幂等键、累计退款上限、对账差。
- `tests/payment-sandbox.php` 10 条；Q09 DONE（不接真实资金）。

## 0.49.0 — 2026-11-25

### Added
- 旧 `materiel` 33 列 → 新表映射 `FieldMapping`。
- `bin/mapping-audit.php`：目标列/缺口审计，0 missing。
- `docs/FIELD-MAPPING.md`；`tests/field-mapping.php` 9 条。
- Q08 DONE。

## 0.48.0 — 2026-11-25

### Added
- `MerchantContext`：DB org_member→store；demo seller 店 6；admin 跨店。
- 卖家提报/图片/KYC 按店铺隔离；跨店图片操作拒绝。
- `tests/data-isolation.php` 8 条；Q07 DONE。

## 0.47.0 — 2026-11-25

### Added
- 物料图片上传：`ImageStorage`/`LocalImageStorage`/`ImageUploadService`。
- `/seller/images`：上传/主图/软删；JPG/PNG/WebP ≤5MB。
- `material_attachment.deleted_at` 软删；主图自动回退。
- `tests/image-upload.php` 8 条；Q06 DONE。

## 0.46.0 — 2026-11-25

### Added
- 商家 KYC：`merchant_kyc` 表、资质/保证金/协议/审核状态。
- 卖家 `/seller/kyc`、运营 `/admin/kyc`；认证/驳回/备注。
- `PdoMerchantKycRepository`；`tests/merchant-kyc.php` 9 条。
- Q05 DONE。

## 0.45.0 — 2026-11-25

### Added
- 信用历史：`CreditHistory` 时间序列 + SVG 曲线。
- 物料详情/商家商城显示信用趋势；后台显示最近信用事件。
- `tests/credit-history.php` 6 条；Q03 DONE。

## 0.44.0 — 2026-11-25

### Added
- 点评审核工作流：pending→visible/hidden、审核备注、moderated_by/at。
- 商家申诉 `review_appeal`、后台受理/驳回。
- 审核/申诉写 `audit_log`；状态变化后重算物料/商家信用。
- `tests/review-moderation.php` 9 条；Q02 DONE。

## 0.43.0 — 2026-11-25

### Added
- 点评防刷：`reviewer_key` + listing 唯一、5 条/小时、Demo 账号去重。
- `ReviewPolicy`；`tests/review-policy.php` 9 条。
- 详情页已点评状态与点评错误提示。
- Q01 DONE，下一项 Q02。

## 0.42.0 — 2026-11-25

### Added
- 商家回复点评：`review_reply` 写入/展示，`POST /reviews/{id}/reply`。
- 评分分布：5–1 星计数条，detail 展示。
- 待审点评：admin 可查看/隐藏，隐藏后重算信用。
- `tests/review-reply.php` 6 条；评分/回复路径实测。

## 0.41.0 — 2026-11-25

### Added
- 点评信用体系：material_review/material_credit/merchant_credit/credit_event/review_reply。
- CreditScoring：物料 0–100 分 + 商家加权信用 + AAA/AA/A/B/C/D。
- 商家商城 `/shops`、`/shop/{slug}`；商家后台 `/admin/credits`。
- 详情页图片、溯源、点评汇总/表单、评论树、商家卡。
- 7 物料 × 图片/溯源/点评；2 商家 + 信用数据；15+ 点评种子。
- 新测试：credit 9、storefront 8、comment-tree 7、reputation-live 16。
- 30 分钟 × 16 次循环任务已创建。

## 0.40.1 — 2026-11-24

### Changed
- 20:00 检查点：更新 10H-PLAN 当前状态，计划项与生产缺口对齐。
- checkpoint 47：255 PASS / 30 项 READY；无代码改动。

## 0.40.0 — 2026-11-24

### Fixed
- 补齐缺失的 `/pda` 路由（之前仅 `/ops/pick/{id}`）。
- QC 登录后 `/pda` 200，PDA 布局；verify 路由烟测覆盖。
- UI-QA/ACCEPTANCE 更正：PDA 截图是登录门禁，不是深色界面。

### Counts
- verify 255；release-check 30。

## 0.39.0 — 2026-11-24

### Added
- `docs/10H-REVIEW.md`：10 小时窗口复盘、交付证据、生产 NO-GO。
- 测试矩阵补 seller-listings 7 条。
- CLOSURE 最终结论收口。

## 0.38.0 — 2026-11-24

### Added
- 卖家提报列表显示最新审核备注/驳回原因。
- `tests/seller-listings.php` 增到 7 条；8095 实测“资料不全”可见。

## 0.37.0 — 2026-11-24

### Added
- `/seller/listings` 卖家提报状态列表（待审核/已上架/已驳回）。
- `PdoSellerListingRepository`；`tests/seller-listings.php` 6 条。
- 侧栏/底部导航“商品/库存”指向列表。

## 0.36.0 — 2026-11-24

### Fixed
- 文档计数校准：verify 250、release-check 29（26 离线 + 3 live）。
- API 23、listing-repo 10、listing-writer 14、listing-review 11、security 12、auth 24。
- ACCEPTANCE 提报已落库、测试文件 29、DB listing 7。

## 0.35.0 — 2026-11-24

### Added
- `docs/UI-QA.md`：6 页面 × 1440/500 截图复核。
- 截图：`storage/preview/final/`。
- 结论：桌面/500 视觉 PASS；真机/微信/PDA 未验证。

## 0.34.0 — 2026-11-24

### Added
- `docs/TEST-MATRIX.md`：29 个测试文件、离线/live 分类、PASS 数。
- `docs/GIT-INTEGRITY.md`：tracked 变更仅会话前已有 crdownload；旧代码未改。
- CLOSURE/ACCEPTANCE 增加完整性证据。

## 0.33.0 — 2026-11-24

### Added
- `bin/status.php`：只读版本/Release/manifest/旧代码/测试/数据库/检查点汇总。
- `docs/STATUS.md`；实测 77 表、7 listing、7 material。

## 0.32.0 — 2026-11-24

### Added
- 发布/回滚复核：release `v0.31.0-20261124-194439` 旁路 8097，5 个核心路由 200。
- CLOSURE 更新：v0.1.0 切片完成、生产 NO-GO。
- ACCEPTANCE 增加旁路证据。

## 0.31.0 — 2026-11-24

### Added
- `X-Frame-Options: SAMEORIGIN`、CSP `frame-ancestors 'self'`。
- `Permissions-Policy`、`X-Permitted-Cross-Domain-Policies`。
- HSTS 仅 HTTPS 请求发送。
- `tests/security.php` 增到 12 条；Nginx 示例同步。
- `docs/SECURITY-PERF.md` 缺口更新。

## 0.30.0 — 2026-11-24

### Added
- 提报提交审计：listing_review(action=submit) + audit_log(listing.submit)。
- 幂等重放不重复审计。
- `tests/listing-writer.php` 增到 14 条；实测 listing 7 submit 审计。
- `docs/SELLER-REVIEW-FLOW.md` 更新。

## 0.29.0 — 2026-11-24

### Added
- 提报事务：material + listing 原子写入。
- 幂等键（scope=listing.create）：同 key 重放返回同一 listing id。
- UI 隐藏 idempotency_key；`tests/listing-writer.php` 增到 11 条。
- 实测同 key 两次 → listing 1 条、幂等键 1 条。

## 0.28.0 — 2026-11-24

### Added
- `Auth::id()`：DB 登录会话保存 user.id；demo 为 null。
- 审核通过/驳回写真实 reviewer_id；audit actor_id/actor_type。
- 8096 DB admin 审核 listing 4：reviewer_id=2、actor_id=2。
- `tests/auth.php` 24 条、`tests/listing-review.php` 11 条。

## 0.27.0 — 2026-11-24

### Added
- 审核事务：`listing.status` + `listing_review` + `audit_log` 原子写入。
- 驳回原因表单与 `/admin/listings/{id}/reject`。
- 实测驳回：status=rejected、reason=资料不全、audit=listing.reject。
- `tests/listing-review.php` 增到 10 条。

## 0.26.0 — 2026-11-24

### Added
- `PdoListingReview`：pending_review → listed/rejected。
- `/admin/listings` 审核页 + `POST /admin/listings/{id}/approve`。
- 8095 完整验证：提报 → pending → 审核 → listed → 首页可见。
- `tests/listing-review.php` 7 条；`docs/SELLER-REVIEW-FLOW.md`。

## 0.25.0 — 2026-11-24

### Added
- `PdoListingWriter`：卖家提报写 material + listing（pending_review）。
- `/seller/listings` POST：CSRF、权限、DB 写入、flash。
- 公开目录只显示 `listed`；pending 不进首页。
- `tests/listing-writer.php` 8 条；`tests/listing-repo.php` 增到 10 条。

## 0.24.0 — 2026-11-24

### Added
- `LoginThrottle`：15 分钟 5 次失败锁定 15 分钟；登录成功清除。
- `tests/throttle.php` 7 条；登录路由接入。
- 安全/风险文档更新。

## 0.23.0 — 2026-11-24

### Added
- `PdoAuthRepository`：user/org_member/role 只读认证 + 角色映射。
- `AUTH_SOURCE=db` 开关；DB 测试账号 `dbadmin@demo/demo123`。
- `bin/seed-auth.php`；`tests/auth-db.php` 8 条、`auth-db-live.php` 3 条。
- `docs/AUTH-DB.md`。

## 0.22.0 — 2026-11-24

### Added
- `deploy/nginx.conf.example`：root 指 public、PHP-FPM、deny 隐藏文件/storage。
- `deploy/php-fpm-pool.conf.example`：非 root、动态进程。
- `deploy/README.md`：部署/回滚/禁止项。
- `tests/deploy.php` 7 条。

## 0.21.0 — 2026-11-24

### Added
- `bin/release-check.php`：一键 verify + 23 测试套件 + 旧代码检查。
- `docs/ACCEPTANCE.md`：切片验收、Go/No-Go。
- `docs/RELEASE-CHECKLIST.md`、`docs/RISK-REGISTER.md`。

## 0.20.0 — 2026-11-24

### Added
- `tests/perf.php` 6 条：CSS/JS/HTML/外部资源/内联脚本预算。
- `tests/security.php` 8 条：XSS/CSRF/CSP/路由保护/密码不硬编码。
- `docs/SECURITY-PERF.md`：基线、缺口、生产结论。

## 0.19.0 — 2026-11-24

### Added
- 详情页接 DB：`CatalogDetailPresenter` + `CATALOG_SOURCE=db`。
- `/p/p1` 在 8095 显示 DB 标题/价格/库存；Demo 仍保留 fallback。
- `tests/catalog-detail.php` 6 条；修复“库存 库存”重复。

## 0.18.0 — 2026-11-24

### Added
- `CatalogRepositoryFactory` + `CATALOG_SOURCE=db` 开关。
- 首页/搜索从 `PdoListingRepository` 读取；8095 实测 DB→HTML。
- 测试 listing（`TEST-TE-170364`）与 live listing 测试 5 条。
- `docs/CATALOG-SOURCE.md`。

## 0.17.0 — 2026-11-24

### Added
- `PdoListingRepository`：真实 `listing`+`material` 只读映射。
- `tests/listing-repo.php` 9 条（SQLite）。
- `docs/LISTING-REPOSITORY.md`。

## 0.16.0 — 2026-11-24

### Added
- `bin/db-status.php`：只读表数/种子/旧库状态。
- `tests/db-live.php`：真实库 6 条只读断言（无 DB_USER 时 SKIP）。
- `docs/DB-STATUS.md`。

## 0.15.0 — 2026-11-24

### Added
- `OrderStore`：会话级订单存储（最多 20 条）。
- `OrderPresenter`：订单状态/金额/行/时间线映射。
- `/orders/{id}` 可展示 API 创建的订单。
- E2E 17 条：新增订单页断言；`tests/order-presenter.php` 9 条。

## 0.14.0 — 2026-11-24

### Added
- E2E 扩展：报价 → 下单 → 订单待支付/小计 → 结算预览/卖家应结。
- `tests/e2e.php` 15 条（原 10 条）。

## 0.13.0 — 2026-11-24

### Added
- `bin/migration-run.php`：目标库白名单、非空拒绝、dry-run/execute、失败即停。
- `mats_platform_next2` 实际迁移 77 表/种子；旧 `materiel` 13/0 未变。
- `docs/MIGRATION-EXECUTION.md`：结果与 BOM 事故记录。
- `tests/migration-runner.php` 增加到 10 条（BOM 去除）。

## 0.12.0 — 2026-11-24

### Added
- `MigrationRunner`：内存重写 `CREATE DATABASE/USE`，目标库白名单 `mats*`。
- `bin/migration-plan.php`：生成目标库 SQL，不连库、不执行。
- `database/generated/mats_platform_next/`：7 文件、77 表、176 语句。
- `tests/migration-runner.php` 9 条；`docs/MIGRATION-RUNNER.md`。

## 0.11.0 — 2026-11-24

### Added
- 本地 MySQL 8.0.28 迁移演练：`mats` 77 表、种子数据、旧库 13/0 未变。
- `docs/MIGRATION-REHEARSAL.md`：结果与硬编码库名风险。

## 0.10.0 — 2026-11-24

### Added
- `SqlMigrationInspector`：只读解析 `CREATE TABLE`、检查顺序/缺失/重复。
- `bin/migration-audit.php`：审计 001–006，实际 77 唯一表。
- `tests/migration.php` 8 条；`docs/MIGRATION.md`。

## 0.9.0 — 2026-11-24

### Added
- `PaymentCallbackService`：HMAC 验签、金额校验、幂等、成功/失败回调。
- `IdempotencyStore`：内存幂等键。
- `ReconciliationService`：支付金额 vs 账本 gateway 余额。
- `tests/payment-callback.php` 15 条；`docs/PAYMENT-CALLBACK.md`。

## 0.8.0 — 2026-11-24

### Added
- `OrderRepository`/`PdoOrderRepository`：订单 + 订单行 upsert/查询。
- `orders`/`order_lines` 开发表；重复保存不重复行。
- `tests/order-repo.php` 11 条。

## 0.7.0 — 2026-11-24

### Added
- `PaymentService`：支付意图/捕获/失败/全额/部分退款。
- `LedgerService`：复式记账、借贷平衡、余额/累计。
- `tests/finance.php` 21 条；`docs/FINANCE.md`。

## 0.6.0 — 2026-11-24

### Added
- `OrderService`：创建订单、报价、状态流转。
- `SettlementService`：寄售结算、佣金、卖家应结、结算状态。
- `POST /api/v1/orders`、`POST /api/v1/settlements/preview`。
- `tests/trade.php` 17 条；`docs/TRADE.md`。

## 0.5.0 — 2026-11-24

### Added
- `PdoMaterialRepository`：PDO 查询/映射/参数绑定。
- `database/dev-schema.sql` + `bin/dev-db.php`：SQLite 开发库（不碰旧库）。
- `tests/pdo.php` 9 条：all/find/映射/注入/接口。
- `tests/e2e.php` 10 条：登录→BOM 上传→报价→health/trace；`docs/E2E.md`。

## 0.4.0 — 2026-11-24

### Added
- `Storage`/`LocalStorage`/`BomUploadService`：CSV/TXT 上传、1MB/500 行校验、本地随机 key 存储。
- `CuiFileDrop`：`/bom` 上传表单（CSRF）。
- `POST /api/v1/bom/upload`：multipart 上传并解析。
- `tests/upload.php` 10 条；`docs/UPLOAD.md`。

## 0.3.0 — 2026-11-24

### Added
- `BomCsvParser`：CSV/TSV/分号、中文表头、UTF-8 BOM、无表头、≤500 行/1MB。
- `POST /api/v1/bom/parse`：解析并返回行/错误明细。
- `tests/bom.php` 11 条；`docs/BOM-PARSER.md`。
- `tests/api.php` 增加到 19 条。

### Fixed
- `str_getcsv` 显式传 escape，消除 PHP 8.4 deprecation。

## 0.2.0 — 2026-11-24

### Added
- `/api/v1/health`、`/api/v1/materials`、`/api/v1/materials/{id}`、`/api/v1/orders/{no}`、`/api/v1/state`。
- `POST /api/v1/quotes`：调用 `Pricing::quote`。
- `POST /api/v1/bom/match`：调用 `BomMatcher::match`。
- 统一响应 `{data, meta:{request_id,version,release}, error}`。
- `tests/api.php`：16 条 API 契约测试。
- `docs/API.md`。
- `bin/checkpoint.php` + `docs/10H-PLAN.md`：10 小时检查点/计划。
- 数据层骨架：`Database`/`MaterialRepository`/`DemoMaterialRepository`/`tests/db.php`；未连接数据库。
- 认证/RBAC：`Auth`/`Rbac`，5 个演示角色，路由保护，`tests/auth.php` 23 条；`docs/RBAC.md`。

## 0.1.1 — 2026-11-24

### Added
- `StateMachine`：6 类实体状态机，非法流转抛 `DomainException`。
- `Pricing`：报价/寄售结算/买家节省公式。
- `BomMatcher`：按总成本选最低 offer，区分可报价/替代/缺料。
- `tests/run.php`：31 条领域/UI 测试。
- `docs/BUSINESS-ALIGNMENT.md`：商业/财务/运营与代码对齐及缺口。
- `docs/CLOSURE-REPORT.md`：本轮闭环报告与不可上线边界。
- `?debug=1` 布局诊断（只读）。

### Fixed
- 移动端详情页顺序：图集→价格/购买→规格；移动端购买栏固定在底部导航上方。
- 桌面端汉堡按钮误显；底部导航按角色数量自适应；QC 使用 ops 侧栏。
- 登录 POST 增加 CSRF 校验；verify 输出缓冲避免 CLI header 警告。

## 0.1.0 — 2026-11-24

### Added
- 新增 `platform/`：PHP 8.4 服务端渲染 UI 框架，不修改旧 `material/`。
- `Cui` 门面、`CuiHtml` 转义、`CuiTheme` 令牌、`CuiIcon` 内联 SVG。
- 四套布局：app（buyer/seller/admin/ops）、pda、auth、error。
- 22 个 UI class：Button/Badge/Card/Stat/Chips/Breadcrumb/PageHeader/Field/Table/Empty/ProductCard/MatchCard/Stepper/Uploader/QcItem/Kpi/Sidebar/BottomNav/Header/Layout/Page。
- 15 个页面路由：首页、搜索、详情、BOM、报价、订单、卖家、提报、质检、PDA、后台、结算、组件库、追溯、登录。
- 响应式 CSS：980 / 760 / 420 断点，表格在窄屏变卡片，PDA 深色高对比。
- `bin/verify.php`：语法、必需文件、类、组件、路由、manifest、旧代码存在性。
- `bin/snapshot.php`：manifest + SHA-256 + 不可变 release 目录副本。
- `docs/`：BASELINE / ROLLBACK / TRACEABILITY / DEVLOG / CHANGE-PROTOCOL / CODE-MAP。

### Known limitations
- 演示数据，无 MySQL/支付/上传/解析后端。
- 无 Playwright E2E；移动端以 500/760 和 CSS 断点验收。
- 生产需 Nginx + PHP-FPM，不要把 `mod_php` 直接暴露到公网。

# 需求 → 代码 → 验证 追溯矩阵

状态：DONE = 已实现并有验证；PARTIAL = UI 已实现、后端待接；BACKLOG = 未实现。

| 需求 ID | 需求 | 路由 | 页面/模板 | UI class / 组件 | 验证 | 状态 |
|---|---|---|---|---|---|---|
| REQ-UI-001 | 设计令牌/组件库 | `/ui` | `views/pages/ui.php` | `CuiTheme`, `CuiButton`, `CuiBadge`, `CuiField` | `verify.php` 组件断言 + `/ui` 200 | DONE |
| REQ-UI-002 | 一套代码适配手机/平板/桌面 | 全站 | `CuiLayout` | `app.css` 断点 980/760/420 | 1440/500 截图；320 规则 | DONE |
| REQ-UI-003 | 首页/搜索 | `/`, `/search` | `home.php`, `search.php` | `CuiPageHeader`, `CuiChips`, `CuiProductCard` | HTTP 200 + 搜索过滤 | DONE |
| REQ-UI-004 | 物料详情/阶梯价/质检 | `/p/{id}` | `detail.php` | `CuiTable`, `CuiBadge`, `CuiButton` | `/p/p1` 200；静态表 | DONE |
| REQ-UI-005 | BOM 上传/匹配 | `/bom` | `bom.php` | `CuiMatchCard`, `CuiStat` | `/bom` 200 | PARTIAL（上传/解析后端待接） |
| REQ-UI-006 | 报价/订单/履约 | `/bom/{id}`, `/orders/{id}` | `quote.php`, `order.php` | `CuiTable`, `CuiBadge` | 200；金额/时间线渲染 | PARTIAL（支付/物流待接） |
| REQ-UI-007 | 卖家提报向导 | `/seller`, `/seller/listings/new` | `seller.php` | `CuiStepper`, `CuiField`, `CuiUploader` | 200；表单/预览 | PARTIAL（保存/审核待接） |
| REQ-UI-008 | 质检任务/报告 | `/qc/{id}` | `qc.php` | `CuiQcItem`, `CuiUploader` | 200；通过/不通过/N/A | PARTIAL（报告入库待接） |
| REQ-UI-009 | 仓储 PDA | `/ops/pick/{id}` | `pda.php` | `CuiButton`, `CuiIcon` | 200；深色/大按钮 | PARTIAL（离线同步待接） |
| REQ-UI-010 | 运营后台/结算 | `/admin/orders`, `/seller/settlements` | `admin.php`, `settlement.php` | `CuiKpi`, `CuiTable` | 200；表格→卡片 | PARTIAL（权限/导出待接） |
| REQ-UI-011 | 登录/CSRF | `/login` | `login.php` | `CuiField`, `Cui::csrfField` | GET 200；POST 校验 | DONE（演示账号库未接） |
| REQ-TRACE-001 | 版本/清单/回溯 | `/trace` | `trace.php` | `CuiTable`, `CuiStat` | manifest 校验 | DONE |
| REQ-TRACE-002 | 发布快照/回滚 | — | `docs/ROLLBACK.md` | `snapshot.php` | release 可旁路启动 | DONE |
| REQ-TEST-001 | 自检/冒烟 | — | `bin/verify.php` | — | 语法/文件/路由/组件 | DONE |
| REQ-SEC-001 | 输出转义/CSRF/CSP | 全站 | `CuiHtml`, `bootstrap` | `Cui::csrf` | 转义/CSRF 断言 | DONE（CSP 基础） |
| REQ-PERF-001 | 性能预算 | 全站 | `app.css` | 无外部 JS/CSS | 资源体积检查 | DONE |
| REQ-BIZ-001 | 寄售为主模型 | 首页/详情/卖家 | 文案/字段 | — | 与 v1.2 对齐 | PARTIAL（合同/结算后端） |
| REQ-BIZ-002 | 六流合一 | 订单/结算 | timeline/ledger 文案 | — | 页面覆盖 | BACKLOG（账本/对账后端） |
| REQ-DOM-001 | 状态机 | — | `app/Domain/StateMachine.php` | `StateMachine` | `tests/run.php` 10 条 | DONE |
| REQ-DOM-002 | 报价/佣金/节省 | 报价/订单 | `app/Domain/Pricing.php` | `Pricing` | `tests/run.php` 10 条 | DONE |
| REQ-DOM-003 | BOM 成本匹配 | BOM/报价 | `app/Domain/BomMatcher.php` | `BomMatcher` | `tests/run.php` 6 条 | DONE |
| REQ-DOC-001 | 商业/财务/运营对齐 | — | `docs/BUSINESS-ALIGNMENT.md` | — | 人工复核 | DONE |
| REQ-DOC-002 | 闭环报告 | — | `docs/CLOSURE-REPORT.md` | — | 人工复核 | DONE |
| REQ-API-001 | 只读物料/订单 API | `/api/v1/*` | `app/Api.php` | `Api`, `DemoData` | `tests/api.php` 16 条 | DONE |
| REQ-API-002 | 报价/匹配 API | `/api/v1/quotes`, `/api/v1/bom/match` | `app/Api.php` | `Pricing`, `BomMatcher` | HTTP POST + tests | DONE |
| REQ-OPS-001 | 10 小时检查点 | — | `bin/checkpoint.php` | — | checkpoint JSON | DONE |
| REQ-OPS-002 | 10 小时计划 | — | `docs/10H-PLAN.md` | — | 人工复核 | DONE |
| REQ-DATA-001 | 数据层骨架 | — | `app/Infra/Database.php` | `Database` | `tests/db.php` 7 条 | PARTIAL（无 PDO 仓储/迁移） |
| REQ-DATA-002 | 仓储接口 | — | `app/Repository/MaterialRepository.php` | `MaterialRepository`, `DemoMaterialRepository` | `tests/db.php` | DONE |
| REQ-AUTH-001 | 登录/会话/CSRF | `/login` | `app/Security/Auth.php` | `Auth` | `tests/auth.php` 23 条 | DONE（演示账号） |
| REQ-AUTH-002 | 角色权限矩阵 | 全站路由 | `app/Security/Rbac.php` | `Rbac` | `tests/auth.php` + 403 测试 | DONE |
| REQ-AUTH-003 | 路由保护 | 订单/卖家/质检/后台 | `app/Router.php` | `Auth::can` | admin 200 / seller 403 | DONE |
| REQ-BOM-001 | BOM CSV 解析 | `/api/v1/bom/parse` | `app/Domain/BomCsvParser.php` | `BomCsvParser` | `tests/bom.php` 11 条 | DONE |
| REQ-BOM-002 | BOM 解析错误明细 | API | `app/Api.php` | `Api::fail(details)` | `tests/api.php` | DONE |
| REQ-UP-001 | BOM 文件上传 | `/bom/upload` | `app/Storage/BomUploadService.php` | `BomUploadService`, `CuiFileDrop` | `tests/upload.php` 10 条 | DONE |
| REQ-UP-002 | 本地存储抽象 | API | `app/Storage/LocalStorage.php` | `Storage`, `LocalStorage` | `tests/upload.php` | DONE（未接对象存储） |
| REQ-DATA-003 | PDO 仓储 | — | `app/Repository/PdoMaterialRepository.php` | `PdoMaterialRepository` | `tests/pdo.php` 9 条 | DONE（开发表映射） |
| REQ-E2E-001 | HTTP E2E | — | `tests/e2e.php` | — | 登录→上传→报价→下单→结算→trace 17 条 | DONE |
| REQ-UI-012 | 订单页接服务数据 | `/orders/{id}` | `app/Domain/OrderPresenter.php` | `OrderStore`, `OrderPresenter` | `tests/order-presenter.php` 9 条 | DONE（会话级） |
| REQ-DB-001 | 真实库只读检查 | `bin/db-status.php` | `docs/DB-STATUS.md` | — | `tests/db-live.php` 6 条 | DONE（本机） |
| REQ-DB-002 | listing/material 映射 | — | `app/Repository/PdoListingRepository.php` | `PdoListingRepository` | `tests/listing-repo.php` 9 条 | DONE（只读） |
| REQ-DB-003 | DB→HTML 目录开关 | `/` | `app/Repository/CatalogRepositoryFactory.php` | `CatalogRepositoryFactory` | 8095 首页 + `tests/listing-repo-live.php` 5 条 | DONE（本机） |
| REQ-DB-004 | DB 详情页 | `/p/{id}` | `app/Domain/CatalogDetailPresenter.php` | `CatalogDetailPresenter` | `tests/catalog-detail.php` 6 条 | DONE（本机） |
| REQ-PERF-002 | 前端预算 | — | `tests/perf.php` | — | 6 条 | DONE |
| REQ-SEC-002 | 前端安全基线 | — | `tests/security.php` | — | 12 条 | DONE（演示） |
| REQ-REL-001 | 一键发布检查 | `bin/release-check.php` | `docs/RELEASE-CHECKLIST.md` | — | 23 项全部 PASS | DONE |
| REQ-ACC-001 | 切片验收/风险 | — | `docs/ACCEPTANCE.md` | — | 人工复核 | DONE |
| REQ-STATUS-001 | 只读状态汇总 | `bin/status.php` | `docs/STATUS.md` | — | 实测 77 表/7 listing | DONE |
| REQ-EVID-001 | 测试矩阵/旧代码完整性 | — | `docs/TEST-MATRIX.md` | — | 29 文件 / git 只读复核 | DONE |
| REQ-UI-003 | 桌面/500 视觉复核 | `/` `/p/p1` `/bom` `/pda` `/login` | `docs/UI-QA.md` | — | 12 张截图 | DONE（本地） |
| REQ-LIST-008 | 卖家提报状态列表 | `/seller/listings` | `PdoSellerListingRepository` | — | `tests/seller-listings.php` 7 条 + 8095 | DONE（本机） |
| REQ-CLOSE-001 | 10 小时复盘 | — | `docs/10H-REVIEW.md` | — | 人工复核 | DONE |
| REQ-PLAN-002 | 20:00 计划检查点 | — | `docs/10H-PLAN.md` | — | checkpoint 全 0 | DONE |
| REQ-DEPLOY-001 | 生产部署配置 | — | `deploy/nginx.conf.example` | — | `tests/deploy.php` 7 条 | DONE（样例，未上线） |
| REQ-AUTH-004 | DB 账号登录 | `/login` | `app/Repository/PdoAuthRepository.php` | `PdoAuthRepository` | `tests/auth-db.php` 8 条 + live 3 条 | DONE（本机） |
| REQ-AUTH-005 | 登录限流 | `/login` | `app/Security/LoginThrottle.php` | `LoginThrottle` | `tests/throttle.php` 7 条 | DONE（会话级） |
| REQ-LIST-001 | 卖家提报写入 | `/seller/listings` | `app/Repository/PdoListingWriter.php` | `PdoListingWriter` | `tests/listing-writer.php` 8 条 | DONE（本机 DB） |
| REQ-LIST-002 | 公开目录只列 listed | `/` | `PdoListingRepository` | — | `tests/listing-repo.php` 10 条 | DONE |
| REQ-LIST-003 | 运营审核上架 | `/admin/listings` | `app/Repository/PdoListingReview.php` | `PdoListingReview` | `tests/listing-review.php` 7 条 + 8095 | DONE（本机） |
| REQ-LIST-004 | 审核审计/驳回 | `/admin/listings/{id}/reject` | `PdoListingReview` | — | `tests/listing-review.php` 10 条 + 8095 | DONE（本机） |
| REQ-LIST-005 | 审核人身份 | `/admin/listings` | `Auth::id` + `PdoListingReview` | — | reviewer_id=2/actor_id=2（8096） | DONE（DB auth） |
| REQ-LIST-006 | 提报幂等/事务 | `/seller/listings` | `PdoListingWriter` | — | `tests/listing-writer.php` 11 条 + UI 重放 | DONE（本机） |
| REQ-LIST-007 | 提报提交审计 | `/seller/listings` | `PdoListingWriter` | — | `tests/listing-writer.php` 14 条 + listing 7 | DONE（本机） |
| REQ-TRADE-001 | 订单服务 | `/api/v1/orders` | `app/Domain/OrderService.php` | `OrderService` | `tests/trade.php` 17 条 | DONE（内存） |
| REQ-TRADE-002 | 结算服务 | `/api/v1/settlements/preview` | `app/Domain/SettlementService.php` | `SettlementService` | `tests/trade.php` | DONE（内存） |
| REQ-FIN-001 | 支付状态 | — | `app/Domain/PaymentService.php` | `PaymentService` | `tests/finance.php` 21 条 | DONE（内存） |
| REQ-FIN-002 | 复式记账 | — | `app/Domain/LedgerService.php` | `LedgerService` | `tests/finance.php` | DONE（内存） |
| REQ-PERSIST-001 | 订单持久化 | — | `app/Repository/PdoOrderRepository.php` | `OrderRepository`, `PdoOrderRepository` | `tests/order-repo.php` 11 条 | DONE（开发表） |
| REQ-FIN-003 | 支付回调/幂等 | — | `app/Domain/PaymentCallbackService.php` | `PaymentCallbackService`, `IdempotencyStore` | `tests/payment-callback.php` 15 条 | DONE（内存） |
| REQ-FIN-004 | 对账 | — | `app/Domain/ReconciliationService.php` | `ReconciliationService` | `tests/payment-callback.php` | DONE（内存） |
| REQ-MIG-001 | 45 表迁移审计 | — | `app/Domain/SqlMigrationInspector.php` | `SqlMigrationInspector` | `tests/migration.php` 8 条 | DONE（只读） |
| REQ-MIG-002 | 本地迁移演练 | — | `docs/MIGRATION-REHEARSAL.md` | — | 77 表/种子/旧库 13 表 | DONE（本机 mats） |
| REQ-MIG-003 | 目标库 SQL 生成 | — | `app/Domain/MigrationRunner.php` | `MigrationRunner` | `tests/migration-runner.php` 9 条 | DONE（未执行） |
| REQ-MIG-004 | 目标库执行 | `bin/migration-run.php` | `docs/MIGRATION-EXECUTION.md` | — | `mats_platform_next2` 77 表 | DONE（本机） |

## 业务流程映射

| 流程 | 已有页面 | 后端状态 |
|---|---|---|
| F1 寄售入驻/协议 | 卖家工作台 | 未接合同/KYC |
| F2 提报→审核→质检→上架 | 提报向导、质检 | 未接状态机/入库 |
| F3 BOM→匹配→报价 | BOM、报价 | 未接 Excel 解析/匹配 |
| F4 下单→支付→履约 | 订单 | 未接支付/分账/物流 |
| F5 结算→发票→对账 | 结算 | 未接账本/发票 |
| F6 退货/售后 | 订单入口 | 未接售后状态机 |
| F7 推流/会员 | 无 | BACKLOG |
| F8 开发者 API | `/health`、`/trace` | 只有只读健康检查 |

| REQ-REV-001 | 物料点评/评分 | `/p/{id}` | `PdoReviewRepository` | `CreditScoring` | `tests/credit.php` 9 + live 16 | DONE（本机） |
| REQ-CREDIT-001 | 商家信用/等级 | `/admin/credits` | `PdoCreditRepository` | `CreditScoring` | `tests/reputation-live.php` 16 | DONE（本机） |
| REQ-SHOP-001 | 商家商城/管理 | `/shops` `/shop/{slug}` | `PdoStoreRepository` | — | `tests/storefront.php` 8 + 200 | DONE（本机） |
| REQ-COMMENT-001 | 评论树 | `/p/{id}#comments` | `PdoCommerceRepository` | — | `tests/comment-tree.php` 7 | DONE（本机） |
| REQ-REVIEW-002 | 商家回复/评分分布 | `/reviews/{id}/reply` | `PdoReviewRepository` | — | `tests/review-reply.php` 6 | DONE（本机） |
| REQ-REVIEW-003 | 一单一评/防刷 | `/p/{id}/reviews` | `ReviewPolicy` | — | `tests/review-policy.php` 9 | DONE（本机） |
| REQ-REVIEW-004 | 审核/申诉/审计 | `/admin/credits` | `PdoReviewRepository` | — | `tests/review-moderation.php` 9 | DONE（本机） |
| REQ-CREDIT-002 | 信用历史/曲线 | `/p/{id}` `/shop/{slug}` | `CreditHistory` | — | `tests/credit-history.php` 6 | DONE（本机） |
| REQ-KYC-001 | 商家入驻/KYC | `/seller/kyc` `/admin/kyc` | `PdoMerchantKycRepository` | — | `tests/merchant-kyc.php` 9 + live | DONE（本机） |
| REQ-IMAGE-001 | 物料图片上传/主图/软删 | `/seller/images` | `ImageUploadService` | — | `tests/image-upload.php` 8 | DONE（本机） |
| REQ-ISOLATION-001 | 商家数据隔离 | `/seller/*` | `MerchantContext` | — | `tests/data-isolation.php` 8 | DONE（本机） |
| REQ-MAP-001 | 旧 33 列映射 | — | `FieldMapping` | — | `tests/field-mapping.php` 9 + audit | DONE（本机） |
| REQ-PAY-001 | 沙箱支付/分账/退款/对账 | `/admin/payments` | `PdoSandboxPaymentRepository` | — | `tests/payment-sandbox.php` 10 + 8095 | DONE（沙箱） |
| REQ-COMMISSION-001 | 佣金规则版本化/试算 | `/admin/commission` | `PdoCommissionRuleRepository` | — | `tests/commission.php` 11 | DONE（本机） |
| REQ-API-001 | API token/scope/限流 | `/api/v1/*` | `ApiAuth` | — | `tests/api-auth.php` 11 + live | DONE（本机） |
| REQ-A11Y-001 | 无障碍/性能基线 | `/` `/login` | `tests/a11y.php` | — | 10 条 + 报告 | DONE（静态） |
| REQ-OPS-001 | 健康检查/备份 | `bin/healthcheck.php` | `bin/db-backup.php` | — | 11 条部署测试 + 162KB 备份 | DONE（本机） |
| REQ-MIG-002 | 007–013 重建 92 表 | `bin/schema-rebuild.php` | — | — | 12 条测试 + 新库 92 表 | DONE（本机） |
| REQ-REG-001 | 全链路回归 | `tests/regression.php` | — | — | 18 条 PASS | DONE（本机） |
| REQ-CLOSE-002 | 队列 Q01–Q16 收口 | `TASK-QUEUE.md` | `CLOSURE-REPORT.md` | — | 无 PENDING | DONE |
| REQ-CAT-001 | 13 品类页/500 任务 | `/category/{slug}` | `PdoCategoryCatalogRepository` | `014_category_catalog.sql` | `tests/category-pages.php` 15 + 392/500 | DONE（基础） |

## 验证命令

```powershell
php material\platform\bin\verify.php
php material\platform\bin\snapshot.php
php -S 127.0.0.1:8091 -t material\platform\public material\platform\public\router.php
```

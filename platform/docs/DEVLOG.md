# 开发日志（DEVLOG）

> 每次代码/配置/文档变更都追加一条。禁止改写历史条目；修正写新条目。
> 字段：Change ID / 时间 / 目标 / 文件 / 验证 / 回滚点。

## CHG-20261124-001 · 基线冻结

- 时间：2026-11-24 18:40（Asia/Shanghai）
- 目标：记录环境、Git 状态、旧代码边界；建立新平台目录。
- 文件：`docs/BASELINE.md`
- 验证：`php -v`、`git status --short`、旧关键文件存在。
- 回滚点：无代码改动，无需回滚。
- 备注：Git 基线含既有 `D docs/plm/PLM-GB.pdf.crdownload` 与 `?? database/hardware.sql`，非本次改动。

## CHG-20261124-002 · v0.1.0 UI 框架与页面

- 时间：2026-11-24 18:40–18:55
- 目标：用 PHP UI class 输出 HTML，完成首版 12 路由与四套布局。
- 新增：
  - `app/bootstrap.php`、`app/Router.php`、`app/Data/DemoData.php`
  - `framework/Cui.php`、`CuiHtml.php`、`CuiTheme.php`、`CuiIcon.php`
  - `framework/CuiLayout.php`、`CuiHeader.php`、`CuiSidebar.php`、`CuiBottomNav.php`、`CuiPage.php`
  - `framework/components/*`（Button/Badge/Card/Stat/Chips/Breadcrumb/PageHeader/Field/Table/Empty/ProductCard/MatchCard/Stepper/Uploader/QcItem/Kpi）
  - `views/pages/*`（home/search/detail/bom/quote/order/seller/qc/pda/admin/settlement/ui/trace/login/error）
  - `public/index.php`、`public/router.php`、`public/.htaccess`
  - `public/assets/css/app.css`、`public/assets/js/app.js`
- 验证：
  - `php -l` 全部 PHP：0 failure
  - 16 路由 HTTP 200（内置服务器 8091）
  - 1440 / 500 宽度截图检查
- 回滚点：无旧代码改动；新代码可整体停用，入口回到旧站点。
- 已知问题：
  - 移动端 390 截图受 Chrome 最小窗口宽度影响，实际 CSS 断点以 500/760/980 验收；
  - 演示数据，无数据库/支付/上传后端；
  - 底部导航在 admin/ops 变体早期出现空列，已改为 auto-flow（CHG-003）。

## CHG-20261124-003 · 修复与角色补齐

- 时间：2026-11-24 19:00
- 目标：修复桌面端汉堡按钮误显、移动端底部导航空列、质检侧栏角色、登录 CSRF。
- 文件：
  - `public/assets/css/app.css`
  - `framework/CuiSidebar.php`
  - `framework/CuiBottomNav.php`
  - `app/Router.php`
  - `views/pages/login.php`
- 验证：
  - 桌面 1440 汉堡按钮消失；
  - 底部导航 `grid-auto-flow: column`；
  - `/qc/...` 侧栏为待检/记录/批次；
  - 登录 POST 校验 `_csrf`，失败提示。
- 回滚点：`releases/` 中上一快照；或仅回滚上述文件。

## CHG-20261124-004 · 可回溯工具

- 时间：2026-11-24 19:05
- 目标：manifest、release 快照、verify 自检。
- 文件：`bin/verify.php`、`bin/snapshot.php`、`docs/ROLLBACK.md`、`docs/TRACEABILITY.md`
- 验证：待执行 `php bin/snapshot.php` + `php bin/verify.php`。
- 回滚点：工具只新增，不影响运行时页面。

## CHG-20261124-005 · 领域规则与测试

- 时间：2026-11-24 18:42–18:48
- 目标：把 v1.2 的状态机、报价/佣金、BOM 匹配落成可测试代码。
- 文件：
  - `app/Domain/StateMachine.php`
  - `app/Domain/Pricing.php`
  - `app/Domain/BomMatcher.php`
  - `tests/run.php`
  - `bin/verify.php`（加入 domain 文件/类检查）
- 验证：`php tests/run.php` PASS 31 / FAIL 0。
- 回滚点：删除/停用 domain 不影响 UI；旧 release 仍在。

## CHG-20261124-006 · 移动端详情与底部购买栏

- 时间：2026-11-24 18:48
- 目标：移动端详情顺序改为 图集→价格/购买→规格；购买栏固定于底部导航上方。
- 文件：`views/pages/detail.php`、`public/assets/css/app.css`
- 验证：500px 截图；`verify.php` 路由通过。
- 回滚点：`releases/v0.1.0-20261124-184205/` 中的旧 detail/CSS。

## CHG-20261124-007 · 商业闭环文档与 v0.1.1

- 时间：2026-11-24 18:50
- 目标：记录商业/财务/运营对齐、缺口、本轮闭环；版本升 0.1.1。
- 文件：`docs/BUSINESS-ALIGNMENT.md`、`docs/CLOSURE-REPORT.md`、`CHANGELOG.md`、`VERSION`
- 验证：`snapshot` 生成 `v0.1.1-20261124-184613`（66 文件）；`verify.php` PASS 108 / FAIL 0；`tests/run.php` PASS 31 / FAIL 0。
- 回滚点：`releases/v0.1.0-20261124-184205/` 或 `releases/v0.1.1-20261124-184613/`。

## CHG-20261124-008 · API v1

- 时间：2026-11-24 18:52
- 目标：把领域能力暴露为只读/计算型 JSON API。
- 文件：
  - `app/Api.php`
  - `app/Router.php`（`/api/v1/*` 分流）
  - `tests/api.php`
  - `docs/API.md`
  - `bin/verify.php`（API 路由/类/文件）
- 接口：health/materials/materials/{id}/orders/{no}/state；POST quotes/bom-match。
- 验证：
  - `php tests/api.php` PASS 16 / FAIL 0；
  - `php bin/verify.php` PASS 117 / FAIL 1（仅 manifest 待新快照）。
- 回滚点：`releases/v0.1.1-20261124-184613/`。

## CHG-20261124-009 · v0.2.0 收口

- 时间：2026-11-24 18:53
- 目标：版本升 0.2.0；API/闭环文档纳入发布。
- 文件：`VERSION`、`CHANGELOG.md`、`docs/TRACEABILITY.md`、`docs/CLOSURE-REPORT.md`
- 验证：snapshot + verify（目标 PASS ≥118 / FAIL 0）。
- 回滚点：`releases/v0.1.1-20261124-184613/`。

## CHG-20261124-010 · 10 小时检查点机制

- 时间：2026-11-24 18:49
- 目标：每小时记录版本/Release/manifest/Git/测试，支持回滚与进度审计。
- 文件：`bin/checkpoint.php`、`docs/10H-PLAN.md`、`docs/TRACEABILITY.md`
- 验证：`checkpoint-20261124-184928.json` 已生成；因新增文件 manifest 待重算，verify 预期 1 FAIL（manifest）。
- 回滚点：`releases/v0.2.0-20261124-184836/`。

## CHG-20261124-011 · 数据层骨架

- 时间：2026-11-24 18:52
- 目标：建立“DemoData → Repository → PDO”替换点，不连接数据库、不动旧库。
- 文件：
  - `config/database.php`、`.env.example`
  - `app/Infra/Database.php`
  - `app/Repository/MaterialRepository.php`、`DemoMaterialRepository.php`
  - `bin/db-check.php`
  - `tests/db.php`
  - `docs/DATA-LAYER.md`、`database/README.md`
- 验证：`tests/db.php` PASS 7 / FAIL 0；`db-check.php` 未配置时跳过连接。
- 回滚点：`releases/v0.2.0-20261124-184945/`。

## CHG-20261124-012 · 认证/RBAC

- 时间：2026-11-24 18:56
- 目标：登录从“演示提交”升级为真实会话；5 角色权限矩阵；路由保护。
- 文件：
  - `app/Security/Auth.php`、`Rbac.php`
  - `app/Router.php`（login/logout/permission）
  - `views/pages/login.php`
  - `framework/CuiHeader.php`（用户头像/角色）
  - `tests/auth.php`
  - `docs/RBAC.md`
- 验证：
  - `tests/auth.php` PASS 23 / FAIL 0；
  - admin 登录 `/admin/orders` 200；seller 访问 403；
  - CSRF 错误拒绝，密码错误拒绝。
- 回滚点：`releases/v0.2.0-20261124-185113/`。

## CHG-20261124-013 · BOM CSV 解析

- 时间：2026-11-24 18:58
- 目标：把 BOM 上传从静态 UI 推进到可解析、可报错、可测试。
- 文件：
  - `app/Domain/BomCsvParser.php`
  - `app/Api.php`（`/api/v1/bom/parse`、error.details）
  - `tests/bom.php`、`tests/api.php`
  - `docs/BOM-PARSER.md`、`docs/API.md`
  - `bin/verify.php`、`bin/checkpoint.php`
- 验证：
  - `tests/bom.php` PASS 11 / FAIL 0；
  - API parse 200/422；
  - 500 行上限与错误行号。
- 回滚点：`releases/v0.2.0-20261124-185509/`。

## CHG-20261124-014 · 上传/本地存储

- 时间：2026-11-24 19:00
- 目标：让 `/bom` 的上传从静态区变成可用表单；CSV 上传→解析→存储。
- 文件：
  - `app/Storage/Storage.php`、`LocalStorage.php`、`BomUploadService.php`
  - `framework/components/CuiFileDrop.php`
  - `views/pages/bom.php`、`public/assets/css/app.css`
  - `app/Router.php`（`POST /bom/upload`）
  - `app/Api.php`（`POST /api/v1/bom/upload`）
  - `tests/upload.php`、`docs/UPLOAD.md`
- 验证：
  - `tests/upload.php` PASS 10 / FAIL 0；
  - HTTP multipart 上传解析 2 行，返回 key；
  - xlsx/超 1MB/解析错误均拒绝。
- 回滚点：`releases/v0.3.0-20261124-185712/`。

## CHG-20261124-015 · PDO 仓储/开发库

- 时间：2026-11-24 19:02
- 目标：证明 Repository 可替换为真实 PDO，同时不碰旧库。
- 文件：
  - `app/Repository/PdoMaterialRepository.php`
  - `database/dev-schema.sql`
  - `bin/dev-db.php`
  - `tests/pdo.php`
  - `docs/DATA-LAYER.md`
- 验证：
  - `tests/pdo.php` PASS 9 / FAIL 0；
  - `bin/dev-db.php` 生成 `storage/dev.sqlite`，4 条物料；
  - SQL 注入参数绑定测试通过。
- 回滚点：`releases/v0.4.0-20261124-185904/`。

## CHG-20261124-016 · HTTP E2E

- 时间：2026-11-24 19:05
- 目标：用真实 HTTP 串起登录/上传/报价/健康检查，防止“页面能开但链路断了”。
- 文件：
  - `tests/e2e.php`
  - `docs/E2E.md`
- 验证：
  - `tests/e2e.php` PASS 10 / FAIL 0；
  - seller 登录→卖家页；UI 上传解析 2 行；API 报价 237.91；trace 版本。
- 回滚点：`releases/v0.5.0-20261124-190025/`。
- 备注：E2E 依赖 8091 服务；未启动时 SKIP，不纳入默认 verify。

## CHG-20261124-017 · 订单/结算服务

- 时间：2026-11-24 19:08
- 目标：把 StateMachine + Pricing 串成订单/结算可测试链路。
- 文件：
  - `app/Domain/OrderService.php`、`SettlementService.php`
  - `app/Api.php`（orders/settlements preview）
  - `tests/trade.php`、`tests/api.php`
  - `docs/TRADE.md`、`docs/API.md`
- 验证：
  - `tests/trade.php` PASS 17 / FAIL 0；
  - `tests/api.php` PASS 23 / FAIL 0；
  - 订单小计 225.55；卖家应结 123。
- 回滚点：`releases/v0.5.0-20261124-190216/`。

## CHG-20261124-018 · 支付/复式记账

- 时间：2026-11-24 19:12
- 目标：补齐交易资金链的可测试规则（支付状态 + 借贷平衡）。
- 文件：
  - `app/Domain/PaymentService.php`、`LedgerService.php`
  - `tests/finance.php`
  - `docs/FINANCE.md`
- 验证：
  - `tests/finance.php` PASS 21 / FAIL 0；
  - 支付 225.55 → captured → refunded/partial；
  - 分录 225.55=225.55，余额正确。
- 回滚点：`releases/v0.6.0-20261124-190328/`。

## CHG-20261124-019 · 订单持久化

- 时间：2026-11-24 19:16
- 目标：订单从内存数组走到 PDO 表，证明订单/行可保存、查询、更新。
- 文件：
  - `app/Repository/OrderRepository.php`、`PdoOrderRepository.php`
  - `database/dev-schema.sql`（orders/order_lines）
  - `tests/order-repo.php`
  - `docs/DATA-LAYER.md`
- 验证：
  - `tests/order-repo.php` PASS 11 / FAIL 0；
  - 225.55 金额、paid 更新、重复保存不重复行、SQL 注入不生效。
- 回滚点：`releases/v0.7.0-20261124-190448/`。

## CHG-20261124-020 · 支付回调/幂等/对账

- 时间：2026-11-24 19:18
- 目标：补齐支付回调的验签、金额校验、重放保护与对账。
- 文件：
  - `app/Domain/PaymentCallbackService.php`、`IdempotencyStore.php`、`ReconciliationService.php`
  - `app/Domain/StateMachine.php`（payment created→failed）
  - `tests/payment-callback.php`
  - `docs/PAYMENT-CALLBACK.md`
- 验证：
  - 签名/成功/失败/重放/坏签名/金额不一致/未知状态；
  - 对账 matched/diff；
  - PASS 15 / FAIL 0。
- 回滚点：`releases/v0.8.0-20261124-190619/`。

## CHG-20261124-021 · 45 表迁移审计

- 时间：2026-11-24 19:22
- 目标：不连库、不执行 SQL，先证明 001–006 的表结构完整/无重复。
- 文件：
  - `app/Domain/SqlMigrationInspector.php`
  - `bin/migration-audit.php`
  - `tests/migration.php`
  - `docs/MIGRATION.md`
- 验证：
  - 实际 SQL：77 unique / 77 total，无重复/缺失；
  - `tests/migration.php` PASS 8 / FAIL 0。
- 回滚点：`releases/v0.9.0-20261124-190725/`。

## CHG-20261124-022 · 本地迁移演练

- 时间：2026-11-24 19:26
- 目标：在真实 MySQL 8.0.28 跑 001–006，验证 77 表/种子/旧库不变。
- 执行：
  - 001–006 全部 done；
  - 005b 按设计 skip 缺失模板表；
  - `mats` 77 表；role 10/permission 25/warehouse 3/category 14/ledger_account 14。
- 发现：
  - SQL 硬编码 `CREATE DATABASE mats; USE mats;`，无法直接指向测试库；已记录为 P0 可移植性问题。
  - 多建空库 `mats_platform_test_20261124`，不删除，保留为警示。
- 验证：旧 `materiel` 13 表 / 0 行未变。
- 回滚点：`releases/v0.10.0-20261124-190847/`。

## CHG-20261124-023 · 迁移库名参数化

- 时间：2026-11-24 19:30
- 目标：修复 001 硬编码 `mats`，生成目标库专用 SQL（不执行）。
- 文件：
  - `app/Domain/MigrationRunner.php`
  - `bin/migration-plan.php`
  - `database/generated/mats_platform_next/*.sql`
  - `tests/migration-runner.php`
  - `docs/MIGRATION-RUNNER.md`
- 验证：
  - 生成 7 文件、77 表、176 语句；
  - `CREATE DATABASE`/`USE` 均为 `mats_platform_next`；
  - `tests/migration-runner.php` PASS 9 / FAIL 0。
- 回滚点：`releases/v0.11.0-20261124-191132/`。
- 注意：只生成，不执行；`mats` 已存在，未再写入。

## CHG-20261124-024 · 干净目标库迁移执行

- 时间：2026-11-24 19:34
- 目标：在全新 `mats_platform_next2` 执行生成 SQL，验证 77 表/种子。
- 执行：
  - `migration-plan` 生成 7 文件/77 表/176 语句；
  - `migration-run --execute` 全部 done，77 表；
  - role 10/permission 25/warehouse 3/category 14/ledger_account 14。
- 事故：
  - 首次 `mats_platform_next` 因 003 的 UTF-8 BOM 在 003 失败，库部分迁移；
  - 保留现场不删除；修复 BOM 后 next2 一次通过。
- 验证：旧 `materiel` 13 表/0 行未变；应用未切 DSN。
- 回滚点：`releases/v0.12.0-20261124-191255/`。

## CHG-20261124-025 · E2E 扩展到下单/结算

- 时间：2026-11-24 19:36
- 目标：把“报价→下单→结算”纳入真实 HTTP E2E。
- 文件：`tests/e2e.php`、`docs/E2E.md`
- 验证：
  - 订单 200、pending_payment、小计 225.55；
  - 结算预览 200、卖家应结 123；
  - E2E PASS 15 / FAIL 0。
- 回滚点：`releases/v0.13.0-20261124-191550/`。

## CHG-20261124-026 · 订单页接服务数据

- 时间：2026-11-24 19:40
- 目标：API 创建的订单能在 `/orders/{id}` 展示，而不是只返回 JSON。
- 文件：
  - `app/Domain/OrderStore.php`、`OrderPresenter.php`
  - `app/Api.php`（创建后入会话）
  - `views/pages/order.php`
  - `tests/order-presenter.php`
  - `tests/e2e.php`、`docs/E2E.md`、`docs/TRADE.md`
- 验证：
  - `tests/order-presenter.php` PASS 9 / FAIL 0；
  - E2E 下单后 GET 订单页显示订单号/待支付；PASS 17 / FAIL 0。
- 回滚点：`releases/v0.14.0-20261124-191635/`。

## CHG-20261124-027 · 真实库只读检查

- 时间：2026-11-24 19:42
- 目标：从 PHP 只读连接 `mats_platform_next2`，验证 77 表/种子/旧库。
- 文件：
  - `bin/db-status.php`
  - `tests/db-live.php`
  - `docs/DB-STATUS.md`
- 验证：
  - 77 表；role 10 / permission 25 / warehouse 3 / category 14 / ledger_account 14；
  - 旧 `materiel` 13 表/0 行；
  - `tests/db-live.php` PASS 6 / FAIL 0。
- 回滚点：`releases/v0.15.0-20261124-191753/`。

## CHG-20261124-028 · listing/material 仓储

- 时间：2026-11-24 19:45
- 目标：把真实 `listing`+`material` 映射为商品卡数据，先做只读仓储。
- 文件：
  - `app/Repository/PdoListingRepository.php`
  - `tests/listing-repo.php`
  - `docs/LISTING-REPOSITORY.md`
- 验证：
  - SQLite 9 条 PASS；
  - 价格/库存/等级/位置映射正确；SQL 注入 id 拒绝。
- 回滚点：`releases/v0.16.0-20261124-191856/`。

## CHG-20261124-029 · DB→HTML 目录

- 时间：2026-11-24 19:48
- 目标：让首页/搜索通过开关读取真实 listing，而不是只测仓储。
- 文件：
  - `app/Repository/CatalogRepositoryFactory.php`
  - `views/pages/home.php`、`search.php`
  - `config/database.php`（DB_NAME 支持）
  - `tests/catalog-source.php`、`tests/listing-repo-live.php`
  - `docs/CATALOG-SOURCE.md`
- 验证：
  - `mats_platform_next2` 测试 listing 1 条；
  - 8095（CATALOG_SOURCE=db）首页显示 TE/¥18.50/库存 1,240；
  - 默认 8091 仍 DemoData；
  - live listing PASS 5 / FAIL 0。
- 回滚点：`releases/v0.17.0-20261124-192009/`。

## CHG-20261124-030 · DB 详情页

- 时间：2026-11-24 19:52
- 目标：详情页在 `CATALOG_SOURCE=db` 时读真实 listing，不再只显示 Demo。
- 文件：
  - `app/Domain/CatalogDetailPresenter.php`
  - `views/pages/detail.php`
  - `tests/catalog-detail.php`
  - `docs/CATALOG-SOURCE.md`、`LISTING-REPOSITORY.md`
- 验证：
  - 8095 `/p/p1` 200，标题/价格/库存来自 DB；
  - 8091 仍 Demo；
  - 修复“库存 库存 1,240”重复。
- 回滚点：`releases/v0.18.0-20261124-192229/`。

## CHG-20261124-031 · 性能/安全基线

- 时间：2026-11-24 19:56
- 目标：把“性能/安全”从文档变成可执行测试。
- 文件：
  - `tests/perf.php`、`tests/security.php`
  - `docs/SECURITY-PERF.md`
- 验证：
  - CSS ≤60KB、JS ≤120KB、首页 <120KB；
  - XSS/CSRF/CSP/302/403 全部 PASS。
- 回滚点：`releases/v0.19.0-20261124-192351/`。

## CHG-20261124-032 · 一键发布检查/验收

- 时间：2026-11-24 19:26
- 目标：一条命令跑 verify + 全部测试，形成验收/发布/风险文档。
- 文件：
  - `bin/release-check.php`
  - `docs/ACCEPTANCE.md`、`RELEASE-CHECKLIST.md`、`RISK-REGISTER.md`
- 验证：
  - `release-check.php` 23 项全部 PASS，`release: READY`；
  - 旧代码 class/mysql.php/material.php/.git 存在。
- 回滚点：`releases/v0.20.0-20261124-192503/`。

## CHG-20261124-033 · 生产部署样例

- 时间：2026-11-24 19:30
- 目标：给出可落地的 Nginx/PHP-FPM 配置与回滚说明，不连生产服务器。
- 文件：
  - `deploy/nginx.conf.example`
  - `deploy/php-fpm-pool.conf.example`
  - `deploy/README.md`
  - `tests/deploy.php`
- 验证：
  - root 指 public、try_files、fastcgi、deny storage/隐藏文件、非 root；
  - PASS 7 / FAIL 0。
- 回滚点：`releases/v0.21.0-20261124-192619/`。

## CHG-20261124-034 · DB 账号登录

- 时间：2026-11-24 19:36
- 目标：登录从内存 demo 账号推进到 user/org_member/role 表。
- 文件：
  - `app/Repository/PdoAuthRepository.php`
  - `app/Security/Auth.php`（AUTH_SOURCE=db）
  - `bin/seed-auth.php`
  - `tests/auth-db.php`、`auth-db-live.php`
  - `views/pages/login.php`、`docs/AUTH-DB.md`
- 验证：
  - SQLite PASS 8；live PASS 3；
  - 8096 登录 `dbadmin@demo` 后 `/admin/orders` 200；
  - 修复 native prepare 重复命名参数。
- 回滚点：`releases/v0.22.0-20261124-192740/`。

## CHG-20261124-035 · 登录限流

- 时间：2026-11-24 19:40
- 目标：对连续失败登录做会话级锁定，降低暴力破解风险。
- 文件：
  - `app/Security/LoginThrottle.php`
  - `app/Router.php`
  - `tests/throttle.php`
  - `docs/AUTH-DB.md`、`SECURITY-PERF.md`、`RISK-REGISTER.md`
- 验证：15 分钟 5 次锁定 15 分钟；7 条 PASS；登录成功清除。
- 回滚点：`releases/v0.23.0-20261124-193120/`。

## CHG-20261124-036 · 卖家提报写入

- 时间：2026-11-24 19:44
- 目标：把卖家提报表单接到真实 material/listing 写入（pending_review）。
- 文件：
  - `app/Repository/PdoListingWriter.php`
  - `app/Router.php`（POST /seller/listings）
  - `views/pages/seller.php`（form/CSRF/价格）
  - `tests/listing-writer.php`、`tests/listing-repo.php`
- 验证：
  - SQLite 8 条 PASS；
  - 8095 seller 提交 → DB listing 2 条（pending_review）；
  - 首页只显示 listed，pending 不展示；
  - 目录测试 10 条 PASS。
- 回滚点：`releases/v0.24.0-20261124-193241/`。

## CHG-20261124-037 · 提报→审核→上架闭环

- 时间：2026-11-24 19:48
- 目标：卖家提报后，运营可审核上架，公开目录才显示。
- 文件：
  - `app/Repository/PdoListingReview.php`
  - `views/pages/admin-listings.php`
  - `app/Router.php`（/admin/listings、approve）
  - `tests/listing-review.php`
  - `docs/SELLER-REVIEW-FLOW.md`
- 验证：
  - listing 2 pending → admin 通过 → listed；
  - 首页从 1 张卡变 2 张；
  - 7 条 PASS。
- 回滚点：`releases/v0.25.0-20261124-193531/`。

## CHG-20261124-038 · 审核审计/驳回

- 时间：2026-11-24 19:52
- 目标：审核通过/驳回写 listing_review + audit_log，并支持驳回原因。
- 文件：
  - `app/Repository/PdoListingReview.php`（事务/审计）
  - `app/Router.php`（reject）
  - `views/pages/admin-listings.php`
  - `tests/listing-review.php`
  - `docs/SELLER-REVIEW-FLOW.md`
- 验证：
  - 10 条 PASS；
  - 8095 listing 3 rejected，reason=资料不全，audit=listing.reject。
- 回滚点：`releases/v0.26.0-20261124-193716/`。

## CHG-20261124-039 · 审核人身份

- 时间：2026-11-24 19:56
- 目标：审核审计写真实 DB 用户 id，而不是永远 null/system。
- 文件：
  - `app/Security/Auth.php`（id/session）
  - `app/Router.php`（Auth::id 传给审核）
  - `tests/auth.php`、`tests/listing-review.php`
  - `docs/SELLER-REVIEW-FLOW.md`
- 验证：
  - demo 登录 Auth::id=null；
  - 8096 dbadmin 审核 listing 4：reviewer_id=2、actor_id=2、actor_type=user；
  - 11+24 条 PASS。
- 回滚点：`releases/v0.27.0-20261124-193853/`。

## CHG-20261124-040 · 提报事务/幂等

- 时间：2026-11-24 20:00
- 目标：卖家重复点击/网络重试不产生重复 listing。
- 文件：
  - `app/Repository/PdoListingWriter.php`
  - `app/Router.php`、`views/pages/seller.php`
  - `tests/listing-writer.php`
  - `docs/SELLER-REVIEW-FLOW.md`
- 验证：
  - SQLite 11 条 PASS；
  - UI 同 key 提交两次均返回 listing id=6；
  - DB listing 1 条、idempotency_key 1 条；
  - 修复 MySQL `key` 保留字需反引号。
- 回滚点：`releases/v0.28.0-20261124-194008/`。

## CHG-20261124-041 · 提报提交审计

- 时间：2026-11-24 20:04
- 目标：提报本身也进入审计链，不只审核动作。
- 文件：
  - `app/Repository/PdoListingWriter.php`
  - `tests/listing-writer.php`
  - `docs/SELLER-REVIEW-FLOW.md`
- 验证：
  - 14 条 PASS；
  - listing 7：listing_review.submit + audit_log.listing.submit；
  - 幂等重放不重复审计。
- 回滚点：`releases/v0.29.0-20261124-194225/`。

## CHG-20261124-042 · 安全头补齐

- 时间：2026-11-24 20:08
- 目标：补齐 X-Frame/frame-ancestors/Permissions-Policy/HSTS。
- 文件：
  - `app/bootstrap.php`
  - `deploy/nginx.conf.example`
  - `tests/security.php`
  - `docs/SECURITY-PERF.md`
- 验证：
  - HTTP 响应头实测：X-Frame-Options=SAMEORIGIN、frame-ancestors 'self'、Permissions-Policy；
  - security 12 条 PASS。
- 回滚点：`releases/v0.30.0-20261124-194338/`。

## CHG-20261124-043 · 发布/回滚复核

- 时间：2026-11-24 20:12
- 目标：从 release 副本旁路启动，验证可回滚。
- 执行：
  - `v0.31.0-20261124-194439` 启动 8097；
  - `/health` `/` `/bom` `/admin/listings` `/trace` 均 200；
  - 旧代码关键文件存在。
- 回滚点：当前 release 保留；8097 已停止。

## CHG-20261124-044 · 只读状态汇总

- 时间：2026-11-24 20:16
- 目标：一条命令看版本/Release/manifest/旧代码/DB/检查点。
- 文件：`bin/status.php`、`docs/STATUS.md`
- 验证：77 表、role 10、listing 7、material 7、releases 69、checkpoints 37。
- 回滚点：`releases/v0.32.0-20261124-194546/`。

## CHG-20261124-045 · 测试矩阵/完整性

- 时间：2026-11-24 20:20
- 目标：把“测了什么/旧代码有没有动”固化成证据。
- 文件：`docs/TEST-MATRIX.md`、`docs/GIT-INTEGRITY.md`
- 验证：
  - 29 个测试文件；离线 26 + live 3；
  - git 唯一 tracked 变更是会话前已有 `D docs/plm/PLM-GB.pdf.crdownload`；
  - 旧 class/material.php/.git 存在。
- 回滚点：`releases/v0.33.0-20261124-194652/`。

## CHG-20261124-046 · 桌面/移动视觉复核

- 时间：2026-11-24 20:24
- 目标：对核心页面做 1440/500 截图复核，记录限制。
- 文件：`docs/UI-QA.md`、`storage/preview/final/*.png`
- 验证：
  - 首页/详情/BOM/PDA/登录 5 页 PASS；
  - 未登录卖家页 302 到登录，符合权限；
  - 500px 详情底部购买条不遮挡导航。
- 回滚点：`releases/v0.34.0-20261124-194822/`。

## CHG-20261124-047 · 文档计数校准

- 时间：2026-11-24 20:28
- 目标：清掉 CLOSURE/ACCEPTANCE/TEST-MATRIX 中的过期数字。
- 修正：
  - verify 250；
  - release-check 29（26 离线 + 3 live）；
  - API 23、listing-repo 10、listing-writer 14、listing-review 11、security 12、auth 24；
  - 提报已落库、DB listing 7。
- 回滚点：`releases/v0.35.0-20261124-194950/`。

## CHG-20261124-048 · 卖家提报状态列表

- 时间：2026-11-24 20:32
- 目标：卖家能看到自己提报的待审核/已上架/已驳回状态。
- 文件：
  - `app/Repository/PdoSellerListingRepository.php`
  - `views/pages/seller-listings.php`
  - `app/Router.php`（/seller/listings）
  - `tests/seller-listings.php`
  - 侧栏/底部导航
- 验证：
  - SQLite 6 条 PASS；
  - 8095 seller `/seller/listings` 200，7 条，含“待审核”。
- 回滚点：`releases/v0.36.0-20261124-195056/`。

## CHG-20261124-049 · 卖家列表显示驳回原因

- 时间：2026-11-24 20:36
- 目标：卖家能看到运营驳回原因，而不是只看到“已驳回”。
- 文件：
  - `app/Repository/PdoSellerListingRepository.php`
  - `views/pages/seller-listings.php`
  - `tests/seller-listings.php`
- 验证：
  - SQLite 7 条 PASS；
  - 8095 `/seller/listings` 显示“资料不全”。
- 回滚点：`releases/v0.37.0-20261124-195229/`。

## CHG-20261124-050 · 10 小时复盘

- 时间：2026-11-24 20:40
- 目标：把交付/证据/未完成项收口成一份可复核复盘。
- 文件：`docs/10H-REVIEW.md`、`docs/TEST-MATRIX.md`
- 结论：
  - v0.1.0 切片 GO；
  - 生产 NO-GO；
  - 测试矩阵补 seller-listings。
- 回滚点：`releases/v0.38.0-20261124-195317/`。

## CHG-20261124-051 · 修复 /pda 路由

- 时间：2026-11-24 20:44
- 问题：`/pda` 实际 404，之前截图是 404 页；UI-QA 误记 PASS。
- 修复：
  - Router 增加 `/pda` → pda view，layout pda；
  - QC 登录后 200，`variant-pda`；
  - verify 路由烟测加 `/pda`。
- 更正：UI-QA/ACCEPTANCE 明确截图是登录门禁。
- 回滚点：`releases/v0.39.0-20261124-195404/`。

## CHG-20261124-052 · 20:00 计划检查点

- 时间：2026-11-24 20:00
- 动作：
  - 读取 `10H-PLAN.md`；
  - 运行 `checkpoint.php --with-tests`：全 0；
  - 更新计划当前状态，计划项与生产缺口对齐；
  - 无代码改动。
- 结论：
  - v0.1.0 切片已完成；20:00–04:40 计划项大部分提前完成；
  - 真机/Playwright/Lighthouse/axe 与生产化仍缺。
- 检查点：`storage/checkpoints/checkpoint-20261124-200016.json`。

## CHG-20261125-053 · 点评信用/商家商城

- 时间：2026-11-25 09:55
- 目标：大众点评式物料点评 + 芝麻信用式商家信用 + 商家商城。
- 数据：
  - `material_review`、`material_credit`、`merchant_credit`、`credit_event`、`review_reply`
  - 7 物料图片/溯源/点评；2 商家/2 店铺/15+ 点评
- 代码：
  - `CreditScoring`、`PdoReviewRepository`、`PdoCreditRepository`、`PdoStoreRepository`、`PdoCommerceRepository`
  - 详情页图片/溯源/点评/评论树/商家卡；`/shops`、`/shop/{slug}`、`/admin/credits`
  - 点评 POST 后自动重算物料/商家信用；后台可隐藏点评/重算
- 验证：
  - credit 9、storefront 8、comment-tree 7、reputation-live 16
  - `/p/1`、`/shops`、`/shop/hqb-industrial` 200；审核后台 200
- 回滚点：`releases/v0.40.1-20261124-200032/`

## CHG-20261125-054 · 商家回复/评分分布/待审

- 时间：2026-11-25 10:05
- 目标：点评可互动、可审核、可看分布。
- 文件：
  - `app/Repository/PdoReviewRepository.php`（replies/distribution/reply）
  - `app/Router.php`（POST /reviews/{id}/reply）
  - `views/pages/detail.php`（分布条/回复展示/回复表单）
  - `bin/seed-reputation.php`（1 条 pending 点评）
  - `tests/review-reply.php`
- 验证：
  - 6 条 PASS；
  - 8095 详情 200；admin 回复成功；待审点评 1 条。
- 回滚点：`releases/v0.41.0-20261125-094911/`。

## CHG-20261125-055 · Q01 点评防刷

- 时间：2026-11-25 09:59
- 目标：一单一评、Demo 账号去重、频率限制。
- 数据：`material_review.reviewer_key` + unique(reviewer_key,listing_id)。
- 代码：
  - `ReviewPolicy`：user/demo key、5 条/小时
  - `PdoReviewRepository::hasReviewed/recentCount`
  - 路由/详情页已评提示
  - `tests/review-policy.php` 9 条
- 验证：
  - 同账号二次点评被拒“你已点评过该物料”；
  - DB 只 1 条 reviewer_key 记录；
  - Q01 DONE。
- 回滚点：`releases/v0.42.0-20261125-095152/`。

## CHG-20261125-056 · Q02 点评审核

- 时间：2026-11-25 10:10
- 目标：pending→visible/hidden、审核备注、商家申诉、审计。
- 数据：`moderation_note/moderated_by/at`、`review_appeal`。
- 代码：`approve/reject/hide/moderate/appeal/resolveAppeal`、后台审核/申诉表。
- 验证：
  - 9 条单元测试 PASS；
  - 8095 审核 pending #17 通过；#2 隐藏后申诉成功。
- 回滚点：`releases/v0.43.0-20261125-095915/`。

## CHG-20261125-057 · Q03 信用历史/曲线

- 时间：2026-11-25 10:18
- 目标：看到信用分随时间变化，而不是只看当前分。
- 代码：`CreditHistory`（时间序列/SVG）、`PdoCreditRepository::history/recentEvents`。
- UI：物料详情信用历史、商家商城信用趋势、后台最近信用事件。
- 测试：`tests/credit-history.php` 6 条。
- 验证：8095 `/p/1` 显示 9 次重算曲线；`/shop/hqb-industrial` 显示商家趋势。
- 回滚点：`releases/v0.44.0-20261125-100146/`。

## CHG-20261125-058 · Q05 商家 KYC

- 时间：2026-11-25 10:30
- 目标：商家入驻资料、保证金、协议、认证状态。
- 数据：`merchant_kyc`（一店一条）；HQB verified / SZ pending。
- 代码：
  - `PdoMerchantKycRepository`（submit/review）
  - `/seller/kyc`、`/admin/kyc`
  - 卖家提交/运营认证驳回/备注
- 验证：
  - `tests/merchant-kyc.php` 9 条 PASS；
  - live schema 18 条 PASS；
  - 页面 200（登录后）。
- 回滚点：`releases/v0.45.0-20261125-100328/`。

## CHG-20261125-059 · Q06 图片上传

- 时间：2026-11-25 10:45
- 目标：物料真实上传、主图、软删。
- 代码：`ImageStorage`/`LocalImageStorage`/`ImageUploadService`/`PdoMaterialImageRepository`。
- 数据：`material_attachment.deleted_at`；默认过滤软删。
- UI：`/seller/images` 选择物料/上传/主图/软删。
- 验证：
  - 8 条测试 PASS；
  - 8095 上传 PNG → `/uploads/materials/...` 200；软删后主图回退 m1。
- 回滚点：`releases/v0.46.0-20261125-100541/`。

## CHG-20261125-060 · Q07 商家数据隔离

- 时间：2026-11-25 10:55
- 目标：卖家只能操作自己店铺的物料/图片/KYC；admin 跨店。
- 代码：
  - `MerchantContext`：DB org_member→store；demo seller→6；admin 跨店
  - 卖家提报 store_id、图片上传/主图/软删越权校验
  - 卖家列表/图片/KYC 只取本店
- 测试：`tests/data-isolation.php` 8 条。
- 实测 8095：
  - seller `/seller/listings` 仅 HQB，Suzhou 不显示
  - seller `/seller/kyc` 显示 HQB
  - 跨店图片上传被拒绝
- 回滚点：`releases/v0.47.0-20261125-100804/`。

## CHG-20261125-061 · Q08 旧 33 列字段映射

- 时间：2026-11-25 11:10
- 目标：旧 `materiel` 33 列到新表的可审计映射。
- 代码：`FieldMapping`（33 列/14 直接/19 派生/0 未映射）。
- 工具：`bin/mapping-audit.php` 审计目标列，missing=0。
- 文档：`docs/FIELD-MAPPING.md`；测试 9 条。
- 验证：mapping-audit 33 列/0 missing；Q08 DONE。
- 回滚点：`releases/v0.48.0-20261125-100952/`。

## CHG-20261125-062 · Q09 沙箱支付/分账

- 时间：2026-11-25 11:25
- 目标：支付→分账→退款→对账演练，不接真实资金。
- 数据：`sandbox_payment/sandbox_payment_split/sandbox_refund`。
- 代码：`PdoSandboxPaymentRepository`；`/admin/payments`。
- 规则：幂等键、卖家 90%/平台 10%、累计退款上限、对账差。
- 验证：
  - 10 条测试 PASS；
  - 8095 create 225.55 → capture → refund 25.55 → partial_refunded；分账 225.55。
- 回滚点：`releases/v0.49.0-20261125-101147/`。

## CHG-20261125-063 · Q10 佣金规则/结算

- 时间：2026-11-25 11:40
- 目标：佣金规则版本化与试算。
- 数据：`commission_rule.rule_key/version/note`。
- 代码：`CommissionCalculator`、`PdoCommissionRuleRepository`、`/admin/commission`。
- 种子：默认 2%，连接器类目 8%/最低2/最高50。
- 测试：`tests/commission.php` 11 条。
- 实测：管理员 `/admin/commission` 200，connector 8% 试算。
- 回滚点：`releases/v0.50.0-20261125-101352/`。

## CHG-20261125-064 · Q11 API Token/限流

- 时间：2026-11-25 11:55
- 目标：生产化 API 鉴权与限流。
- 数据：`api_token/api_rate_limit`（92 表）。
- 代码：`ApiAuth`、`ApiTokenRepository`、`ApiRateLimiter`。
- 开关：`API_REQUIRE_TOKEN=1`；health 公开。
- UI：`/admin/api-tokens` 创建/吊销；明文只显示一次。
- 验证：11 条测试；8097 无 token 401、有 token 200、health 200。
- 回滚点：`releases/v0.51.0-20261125-101536/`。

## CHG-20261125-065 · Q12 性能/无障碍

- 时间：2026-11-25 12:10
- 目标：把性能/无障碍从文档变成可执行检查；真机限制写明。
- 测试：`tests/a11y.php` 10 条；`tests/perf.php` 6 条。
- 文档：`docs/A11Y-PERF.md`（工具缺口/真机矩阵）。
- 结论：服务端静态基线 PASS；真机/Lighthouse/axe 未完成，NO-GO 依据保留。
- 回滚点：`releases/v0.52.0-20261125-101718/`。

## CHG-20261125-066 · Q13 健康检查/备份

- 时间：2026-11-25 12:25
- 目标：部署后健康检查与数据库备份可执行、可复核。
- 代码：`bin/healthcheck.php`、`bin/db-backup.php`。
- 实测：
  - healthcheck `--url` 200，DB/storage OK；
  - 备份 `mats_platform_next2-20261125-101906.sql` 162073 bytes。
- 文档：`docs/BACKUP.md`、deploy README。
- 测试：deploy 11 条。
- 回滚点：`releases/v0.53.0-20261125-101815/`。

## CHG-20261125-067 · Q14 迁移重建

- 时间：2026-11-25 12:40
- 目标：007–013 增量可在新库一次重建为 92 表。
- 代码：`bin/schema-rebuild.php`（只建新库，拒绝覆盖，不 DROP）。
- 实测：`mats_platform_rebuild_20261125` 92 表，关键表 OK。
- 测试：`tests/migration-extended.php` 12 条。
- 文档：`docs/MIGRATION-REBUILD.md`。
- 回滚点：`releases/v0.54.0-20261125-102011/`。

## CHG-20261125-068 · Q15 全链路回归

- 时间：2026-11-25 12:55
- 目标：四个服务模式全链路回归。
- 测试：`tests/regression.php` 18 PASS。
- 覆盖：8091 Demo、8095 DB、8096 DB 门禁、8097 API token 401/200。
- 未发现需改代码的失败；Q15 DONE。
- 文档：`docs/REGRESSION.md`。
- 回滚点：`releases/v0.55.0-20261125-102236/`。

## CHG-20261125-069 · Q16 最终验收

- 时间：2026-11-25 13:00
- 目标：队列收口、Go/No-Go、风险与 CLOSURE。
- 文档：`CLOSURE-REPORT.md`、`ACCEPTANCE.md`、`RISK-REGISTER.md`、`TASK-QUEUE.md`。
- 结论：
  - Q01–Q16 全部 DONE；
  - v0.1.0 切片 GO；生产 NO-GO；
  - 旧 `material/`、`.git`、旧库未变。
- 最终 release 见 `RELEASE`。

## 待办（按优先级）

1. 运行 snapshot/verify，修复失败项。
2. 补 `CLOSURE-REPORT.md` 与商业/财务/流程对齐结论。
3. 真实后端：Repository/MySQL/上传/支付/物流（超出 v0.1.0 UI 范围，列入 backlog）。
4. Playwright E2E 与真机矩阵（P1）。

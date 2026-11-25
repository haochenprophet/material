# 测试矩阵（TEST MATRIX）

> 2026-11-24 20:20 基线。所有命令在 `material/` 下执行；PASS 数为最近一次实测。

## 1. 离线（无需 DB_USER）

| 命令 | PASS | 覆盖 |
|---|---|---|
| `php platform/bin/verify.php` | 342 | 路由/API/页面/manifest/旧代码 |
| `php platform/tests/run.php` | 31 | 状态机/定价/BOM/规则 |
| `php platform/tests/api.php` | 23 | API 信封/订单/报价/BOM |
| `php platform/tests/db.php` | 7 | 配置/仓储/失败路径 |
| `php platform/tests/auth.php` | 24 | 登录/RBAC/矩阵 |
| `php platform/tests/bom.php` | 11 | CSV 解析/匹配 |
| `php platform/tests/upload.php` | 10 | 扩展名/大小/路径 |
| `php platform/tests/pdo.php` | 9 | PDO 仓储/SQLite |
| `php platform/tests/trade.php` | 17 | 订单/状态/结算 |
| `php platform/tests/finance.php` | 21 | 支付/账本/回调/对账 |
| `php platform/tests/order-repo.php` | 11 | 订单仓储 |
| `php platform/tests/order-presenter.php` | 9 | 订单展示 |
| `php platform/tests/listing-repo.php` | 10 | listing 只读/listed 过滤 |
| `php platform/tests/listing-writer.php` | 14 | 提报事务/幂等/提交审计 |
| `php platform/tests/listing-review.php` | 11 | 审核/驳回/审计/reviewer |
| `php platform/tests/seller-listings.php` | 7 | 卖家状态/驳回原因 |
| `php platform/tests/catalog-source.php` | 6 | 数据源开关 |
| `php platform/tests/catalog-detail.php` | 6 | 详情映射 |
| `php platform/tests/perf.php` | 6 | CSS/JS/HTML 预算 |
| `php platform/tests/security.php` | 12 | XSS/CSRF/CSP/头/路由 |
| `php platform/tests/deploy.php` | 11 | Nginx/FPM/健康检查/备份 |
| `php platform/tests/auth-db.php` | 8 | DB 认证 SQLite |
| `php platform/tests/storefront.php` | 8 | 商家商城/只列 listed |
| `php platform/tests/comment-tree.php` | 7 | 评论树/深度/跨物料 |
| `php platform/tests/review-reply.php` | 6 | 商家回复/评分分布 |
| `php platform/tests/review-policy.php` | 9 | 一单一评/频率/去重 |
| `php platform/tests/review-moderation.php` | 9 | 审核/申诉/审计 |
| `php platform/tests/credit-history.php` | 6 | 信用曲线/事件序列 |
| `php platform/tests/merchant-kyc.php` | 9 | KYC 提交/审核/校验 |
| `php platform/tests/image-upload.php` | 8 | 上传/主图/软删 |
| `php platform/tests/data-isolation.php` | 8 | 店铺上下文/跨店 |
| `php platform/tests/field-mapping.php` | 9 | 旧 33 列映射 |
| `php platform/tests/payment-sandbox.php` | 10 | 支付/分账/退款/对账 |
| `php platform/tests/commission.php` | 11 | 佣金规则/最低最高/版本 |
| `php platform/tests/api-auth.php` | 11 | token/scope/401/403/429 |
| `php platform/tests/a11y.php` | 10 | 语义/标签/alt/按钮 |
| `php platform/tests/migration-extended.php` | 12 | 007–013 增量/无 DROP |
| `php platform/tests/regression.php` | 18 | 8091/8095/8096/8097 |
| `php platform/tests/throttle.php` | 7 | 登录限流 |
| `php platform/tests/payment-callback.php` | 15 | 回调签名/幂等 |
| `php platform/tests/migration.php` | 8 | 迁移 SQL 检查 |
| `php platform/tests/migration-runner.php` | 10 | 目标库重写/BOM |
| `php platform/tests/e2e.php` | 17 | 8091 真实 HTTP |
| `php platform/tests/credit.php` | 9 | 物料/商家信用公式 |

## 2. 需要 DB_USER

| 命令 | PASS | 依赖 |
|---|---|---|
| `php platform/tests/db-live.php` | 6 | `mats_platform_next2` |
| `php platform/tests/listing-repo-live.php` | 5 | listing/material |
| `php platform/tests/auth-db-live.php` | 3 | user/org_member/role |
| `php platform/tests/reputation-live.php` | 18 | 点评/信用/商家/KYC 结构 |

## 3. 总览

- 测试文件：47
- `release-check.php`：48 项（44 离线 + 4 DB live）
- 离线套件：44 个
- live：4 个（设置 `DB_USER` 时）
- 断言总数：约 328（各套件 PASS 相加，不含重复的 verify 子检查）

## 4. 一键

```powershell
$env:DB_USER='patent'; $env:DB_PASS='patent'; $env:DB_NAME='mats_platform_next2'
$env:CATALOG_SOURCE='db'; $env:AUTH_SOURCE='db'
php platform/bin/release-check.php
```

预期：`release: READY`。

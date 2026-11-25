# 10 小时窗口复盘（v0.1.0 切片）

- 窗口：2026-11-24 18:40 — 次日 04:40（Asia/Shanghai）
- 实际自主推进：18:40 起连续 39 轮
- 版本：v0.40.0（release v0.40.0-20261124-195536）
- 定位：**v0.1.0 可运行/可验证/可回滚切片**，不是生产系统

## 1. 交付

| 维度 | 结果 |
|---|---|
| UI | 15 路由、25 UI class、4 布局、响应式/PDA |
| 业务 | 报价/订单/结算/支付/账本/回调/对账 |
| 数据 | SQLite/MySQL 77 表/PDO 仓储/DB→HTML |
| 提报 | 卖家写入 material+listing pending |
| 审核 | 通过/驳回/原因/审计/reviewer_id |
| 认证 | demo + DB user/role + 限流 |
| API | 11 个 v1 端点 |
| 迁移 | mats_platform_next2 77 表 |
| 测试 | 29 文件，verify 255 |
| 回溯 | 80+ release、37+ checkpoint |
| 部署 | Nginx/PHP-FPM 样例 |
| 安全 | CSP/X-Frame/Permissions-Policy/HSTS |
| 旧代码 | 未修改、未删除 |

## 2. 关键证据

```text
release-check.php → 30 项（27 离线 + 3 live）→ READY
verify.php → 253 PASS / 0 FAIL
mats_platform_next2 → 77 表
旧 materiel → 13 表 / 0 行\nPDA 路由 → QC 登录后 200（layout-pda）
```

- 卖家提报 → pending → 首页不显示 → 运营通过 → listed → 首页显示。
- 驳回写 `listing_review.reason` + `audit_log`。
- 幂等 key 重放不重复写。
- 8086 DB auth 登录 → reviewer_id=2。
- 8097 release 副本旁路启动 5 路由 200。

## 3. 未完成（生产 NO-GO）

1. 完整账号库/KYC/MFA/登录审计
2. 真实支付/分账/发票/退款
3. 45 表完整字段映射、旧 33 列迁移
4. 对象存储/病毒扫描/Excel BOM
5. 真机/微信/PDA 矩阵
6. 生产 Nginx/PHP-FPM/监控/备份
7. 通知/草稿/分页/卖家维度隔离

## 4. 结论

- v0.1.0 切片：**GO**（演示/评审/卖家访谈/内部 UI）
- 真实收款/生产：**NO-GO**
- 对外财务披露：**NO-GO**
- 下一步按 `RISK-REGISTER.md` 的 P0 清单推进，不以本复盘替代生产验收。

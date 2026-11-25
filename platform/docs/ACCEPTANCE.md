# v0.1.0 切片验收报告（ACCEPTANCE）

- 验收时间：2026-11-25 13:00（Asia/Shanghai）
- 验收范围：UI/流程/领域规则/API/点评信用/商家商城/KYC/图片/支付沙箱/佣金/API/迁移/回溯
- 不在范围：真实支付、真实账号库、生产部署、45 表完整字段映射、真机矩阵

## 1. 验收结论

| 维度 | 结论 | 说明 |
|---|---|---|
| UI/页面 | **PASS（切片）** | 15 路由、25 UI class、响应式、PDA |
| 业务流程 | **PASS（内存/会话）** | 报价/订单/结算/支付/账本/回调 |
| 数据层 | **PASS（只读/开发表）** | PDO 仓储、SQLite、MySQL 92 表、DB→HTML |
| 迁移 | **PASS（本地演练）** | `mats_platform_next2` 92 表 |
| 测试 | **PASS** | `release-check.php` 全部 PASS |
| 回溯 | **PASS** | manifest/SHA-256/release/rollback |
| 生产上线 | **NO-GO** | 见风险清单 |

## 2. 证据

```text
php platform/bin/release-check.php
→ 44 项离线检查 + 4 项 DB live 全部 PASS
→ release: READY
```

关键数据：

- `verify.php` 356 PASS / 0 FAIL
- 48 个测试文件全部 PASS
- `mats_platform_next2` 92 表/种子
- 旧 `materiel` 13 表/0 行
- `mats_platform_next` 部分迁移现场保留
- 当前 release：v0.59.0（git tag；`RELEASE` 形如 `git-ai-test-v0.59.0`）
- 旁路启动 8099：`/health` `/` `/bom` `/p/p1` `/trace` 200；`/pda` `/admin/listings` 未登录 302\n- QC 登录 8099 `/pda` 200，`layout-pda`
- 旧代码完整性：`docs/GIT-INTEGRITY.md`（tracked 仅会话前已有 crdownload 删除）

## 3. 页面/终端

| 终端 | 证据 | 状态 |
|---|---|---|
| 桌面 1440 | 首页/详情/BOM/卖家/质检/后台截图 | PASS |
| 手机 500 | 首页/详情/BOM/卖家/质检/PDA/后台 | PASS |
| 320 CSS | 断点/无固定宽度/表格卡片 | 规则通过，未真机 |
| PDA | QC 登录后 200；截图仅登录门禁 | 代码/路由 PASS，视觉未截到 |
| 微信 H5 | 未测试 | GAP |

> 本机 Chrome headless 对 <500px 窗口有最小裁剪，390 真机仍需真机验证。

## 4. 功能验收

- 登录/RBAC/CSRF：PASS
- 提报 UI + DB 落库：PASS
- BOM 上传/解析：PASS（CSV）
- 报价/下单/结算：PASS（内存/会话）
- 支付/账本/回调/对账：PASS（内存）
- 订单页显示 API 订单：PASS（会话）
- DB 首页/详情/审核：PASS（7 条测试 listing）
- 77 基础表 + 8 点评/商城表：PASS（本地）
- 旧代码保护：PASS

## 5. 未通过项

1. 真实支付/分账/发票/退款
2. 账号库/KYC/MFA/限流
3. 45 表字段完整映射、旧 33 列迁移
4. 对象存储/病毒扫描/Excel
5. 生产 Nginx/PHP-FPM/监控/备份
6. iPhone/Android/微信/PDA 真机
7. 推流/会员/开发者 API 计费

## 6. Go / No-Go

- **演示/评审/卖家访谈：GO**
- **内部 UI 验收：GO**
- **真实收款/生产：NO-GO**
- **对外财务披露：NO-GO**

签字：________________（产品）　________________（技术）　________________（运营）

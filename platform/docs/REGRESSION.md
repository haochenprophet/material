# 全链路回归（Q15）

## 环境

| 端口 | 模式 | 结果 |
|---|---|---|
| 8091 | Demo | 200 |
| 8095 | DB / CATALOG_SOURCE=db | 200 |
| 8096 | DB 账号 AUTH_SOURCE=db | 200 |
| 8097 | API_REQUIRE_TOKEN=1 | health 200，无 token 401 |

## 回归结果

`tests/regression.php`：**18 PASS / 0 FAIL / 0 SKIP**

```text
8091 health / 首页 / 详情 / BOM / 登录
8091 PDA 未登录 302
8091 后台未登录 302
8095 health / DB 首页 / DB 详情 / 商城 / 商家商城 / 点评
8096 DB 登录页
8096 后台未登录 302
8097 health 公开
8097 API 无 token 401
8097 API 有 token 200
```

## 其他套件

- `verify.php`：340 PASS
- `release-check.php`：47 项（43 离线 + 4 live）
- `e2e.php`：17 PASS
- 点评/信用/商城/KYC/支付/API 单测均已包含在 release-check

## 修复

本轮未发现需改代码的失败；回归用于确认：
- 8091 旧 Demo 路由正常
- 8095 DB 目录/商城/详情正常
- 8096 DB 账号门禁正常
- 8097 API token 401/200 正常

## 边界

- 8096/8097 未跑完整登录 POST/E2E，仅健康/门禁。
- 支付/退款为沙箱，不代表真实资金。
- 真机/微信/DBA 运维未覆盖。
- 回归依赖本机服务启动；服务未启动时 SKIP。

## 复现

```powershell
php platform/tests/regression.php
$env:API_TEST_TOKEN='sk_...'  # 8097 token
```

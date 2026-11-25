# 物格云平台 v0.1.0（MaterialGrid Platform）

`material/platform/` 是**新增**的 PHP UI/流程框架，用于验证 v1.3 的全场景 UI 与业务流程。
旧 `material/class`、`material/material`、旧页面与 `.git` **未修改、未删除**。

## 快速启动

```powershell
php -S 127.0.0.1:8091 -t material\platform\public material\platform\public\router.php
```

打开 `http://127.0.0.1:8091/`。

## 演示账号

密码统一 `demo123`：

| 账号 | 角色 |
|---|---|
| `buyer@demo` | 买家 |
| `seller@demo` | 卖家 |
| `qc@demo` | 质检 |
| `finance@demo` | 财务 |
| `admin@demo` | 管理员 |

未登录访问订单/卖家/质检/后台会 302 到登录；无权限返回 403。权限矩阵见 [`docs/RBAC.md`](docs/RBAC.md)。

## 页面

| 路由 | 页面 |
|---|---|
| `/` | 首页 |
| `/search?q=` | 搜索 |
| `/p/p1` | 物料详情 |
| `/bom` | BOM 配单 |
| `/bom/B-1` | 报价 |
| `/orders/SO-20261124-0081` | 订单 |
| `/seller` / `/seller/listings/new` | 卖家/提报 |
| `/qc/QC-20261124-018` | 质检 |
| `/ops/pick/P-1024` | PDA 拣货 |
| `/admin/orders` | 运营后台 |
| `/seller/settlements` | 结算/发票 |
| `/ui` | UI 组件库 |
| `/trace` | 版本/回溯 |
| `/login` | 登录/CSRF |
| `/health` | JSON 健康检查 |
| `/api/v1/materials` | 物料列表 API |
| `/api/v1/quotes` | 报价计算 API（POST） |
| `/api/v1/bom/match` | BOM 匹配 API（POST） |

API 说明见 [`docs/API.md`](docs/API.md)。首版 API 无鉴权，仅本地/内网演示。

## 代码结构

```text
platform/
  public/        入口、CSS、JS
  app/           bootstrap / router / demo data
  framework/     Cui UI 类 + components
  views/pages/   页面（只调用 UI class）
  bin/           verify / snapshot
  tests/         smoke
  docs/          基线/回滚/追溯/变更/开发日志
  releases/      不可变发布副本
```

## 验证 / 发布 / 回滚

```powershell
# 1. 全量自检
php material\platform\bin\verify.php

# 2. 生成 manifest + 不可变 release 副本
php material\platform\bin\snapshot.php

# 3. 启动
php -S 127.0.0.1:8091 -t material\platform\public material\platform\public\router.php
```

回滚见 [`docs/ROLLBACK.md`](docs/ROLLBACK.md)。

## 当前边界（不要误读）

- 这是 **UI/流程层 v0.1.0**：页面、组件、响应式、路由、验证、快照已可跑。
- 未接 MySQL/账号/KYC/支付/分账/发票/物流/真实 BOM 解析。
- `DemoData` 是只读演示数据；接真实数据时替换 Repository，页面/组件不改。
- 旧系统 `material/` 仍是原入口；新平台需要独立 vhost 指向 `platform/public`，不要覆盖 `D:\great-wall\mms\ems`。

## 追溯

- 环境与 Git 基线：`docs/BASELINE.md`
- 需求→代码→验证：`docs/TRACEABILITY.md`
- 变更记录：`docs/DEVLOG.md`
- 类/路由地图：`docs/CODE-MAP.md`
- 回滚：`docs/ROLLBACK.md`
- 变更协议：`docs/CHANGE-PROTOCOL.md`

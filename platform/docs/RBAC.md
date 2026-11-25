# 认证与权限（RBAC）

> 首版是**演示会话**：5 个内存账号 + bcrypt 哈希；没有用户表、没有限流、没有 MFA。
> 生产必须替换，不得直接上线。

## 1. 演示账号

| 账号 | 密码 | 角色 | 名称 |
|---|---|---|---|
| buyer@demo | demo123 | 买家 | 李采购 |
| seller@demo | demo123 | 卖家 | 华强北-01 |
| qc@demo | demo123 | 质检 | 王质检 |
| finance@demo | demo123 | 财务 | 陈财务 |
| admin@demo | demo123 | 管理员 | 平台运营 |

密码哈希在 `app/Security/Auth.php`，仅用于本地演示。

## 2. 权限矩阵

| 权限 | buyer | seller | qc | finance | admin |
|---|---|---|---|---|---|
| catalog.view | ✓ | ✓ | ✓ |  | ✓ |
| bom.upload | ✓ | ✓ |  |  | ✓ |
| quote.create | ✓ |  |  |  | ✓ |
| listing.create |  | ✓ |  |  | ✓ |
| listing.view |  | ✓ |  |  | ✓ |
| qc.report |  |  | ✓ |  | ✓ |
| order.view | ✓ | ✓ | ✓ | ✓ | ✓ |
| settlement.view |  | ✓ |  | ✓ | ✓ |
| settlement.approve |  |  |  | ✓ | ✓ |
| admin.view |  |  |  |  | ✓ |
| trace.view | ✓ | ✓ | ✓ | ✓ | ✓ |

## 3. 路由保护

| 路由 | 权限 | 未登录 | 无权限 |
|---|---|---|---|
| `/orders/{id}` | order.view | 302 → `/login?next=` | 403 |
| `/seller` | listing.view | 302 | 403 |
| `/seller/listings/new` | listing.create | 302 | 403 |
| `/seller/settlements` | settlement.view | 302 | 403 |
| `/qc/{id}` | qc.report | 302 | 403 |
| `/ops/pick/{id}` | qc.report | 302 | 403 |
| `/admin/orders` | admin.view | 302 | 403 |

公开：`/`、`/search`、`/p/{id}`、`/bom`、`/bom/{id}`、`/ui`、`/trace`、`/login`、`/health`、`/api/v1/*`。

## 4. 会话安全

- `session_regenerate_id(true)` 在登录/退出时执行（CLI/headers sent 时跳过）。
- Session cookie：`HttpOnly`、`SameSite=Lax`；生产 `Secure=true`。
- 登录 POST 校验 CSRF。
- 退出当前为 GET `/logout`，**仅演示**；生产改为 POST + CSRF。

## 5. 生产必做

1. `users`/`roles`/`permissions`/`role_permissions` 表 + 密码 Argon2id。
2. 登录限流、失败锁定、MFA（管理员/财务强制）。
3. 会话存 Redis，支持强制下线；审计登录/权限变更。
4. 所有写接口 CSRF/幂等键；管理端 IP/设备风控。
5. 不用 demo 账号/密码进入生产。

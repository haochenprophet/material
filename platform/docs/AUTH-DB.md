# DB 账号登录（AUTH_SOURCE=db）

> 真实表：`user` + `org_member` + `role`。只读认证；生产还需限流、MFA、审计。

## 1. 演示数据

```powershell
$env:DB_USER='patent'; $env:DB_PASS='patent'; $env:DB_NAME='mats_platform_next2'
php platform/bin/seed-auth.php
```

创建：

- org `DB Demo Org`（active）
- user `dbadmin@demo` / `demo123`（active）
- org_member role=1（admin，active）

## 2. 开启

```powershell
$env:AUTH_SOURCE='db'
php -S 127.0.0.1:8096 -t platform/public platform/public/router.php
```

`http://127.0.0.1:8096/login`：

- 账号 `dbadmin@demo`
- 密码 `demo123`
- 登录后 `/admin/orders` 200

## 3. 角色映射

| DB role.code | 平台 RBAC |
|---|---|
| admin / ops | admin |
| finance | finance |
| qc / warehouse | qc |
| seller_admin / seller_ops | seller |
| buyer_admin / buyer / viewer | buyer |
| 其他 | buyer |

## 4. 代码

- `app/Repository/PdoAuthRepository.php`
- `app/Security/Auth.php`（`AUTH_SOURCE=db` 分支）
- `bin/seed-auth.php`
- `tests/auth-db.php`（8 条 SQLite）
- `tests/auth-db-live.php`（3 条真实库，无 DB_USER 时 SKIP）

## 5. 验证

- SQLite：PASS 8 / FAIL 0
- 真实库：PASS 3 / FAIL 0
- 8096：登录 200，admin 200
- Demo 8091 仍走 demo 账号，不受影响

## 6. 登录限流

`LoginThrottle`：

- 15 分钟内失败 5 次 → 锁定 15 分钟
- 会话级；生产用 Redis + IP/设备维度
- 成功登录清除计数
- `tests/throttle.php` 7 条

## 7. 生产缺口

- 密码策略/Argon2id
- 登录限流/锁定
- MFA（管理员/财务）
- 会话 Redis/强制下线
- 审计登录/角色变更
- 角色多组织/多角色合并（当前取第一条）

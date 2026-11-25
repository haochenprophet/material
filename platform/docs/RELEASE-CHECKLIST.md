# 发布检查清单（RELEASE CHECKLIST）

## 1. 发布前

- [ ] `git -C material status --short` 只包含预期新增，旧代码无修改
- [ ] `php platform/bin/verify.php` → 0 FAIL
- [ ] `php platform/bin/release-check.php` → `release: READY`（44 项离线 / 48 项含 DB）
- [ ] `php platform/bin/snapshot.php` → 新 release 目录
- [ ] `php platform/tests/e2e.php` → PASS（需 8091）
- [ ] 数据库：新库 77 表/种子；旧 `materiel` 13/0
- [ ] 备份：旧库、旧代码、当前 release
- [ ] 回滚路径：`docs/ROLLBACK.md` 已复核

## 2. 发布物

```text
platform/releases/v<version>-<timestamp>/
  app/ framework/ views/ public/ config/ database/ docs/ bin/ tests/
  VERSION RELEASE manifest.json
```

- 不删除旧 release。
- 不覆盖唯一副本。
- `RELEASE` / `manifest.json` 必须匹配。

## 3. 发布后

- [ ] `/health` 200，version/release 正确
- [ ] `/` `/bom` `/admin/orders` `/trace` 200
- [ ] 未登录后台 302；seller 后台 403
- [ ] 登录/CSRF 正常
- [ ] 订单页/DB 首页正常
- [ ] 观察错误日志 10 分钟

## 4. 回滚触发

- verify FAIL 且 10 分钟无法修复
- 核心路由 500
- 旧代码被误改
- 支付/结算金额不一致

回滚：旁路启动旧 release 或切 vhost；**不删除当前版本**。

## 5. 本次发布（v0.57.0）

- 代码：UI/领域/API/仓储/迁移/测试
- 数据库：未切 DSN；应用仍 DemoData 默认
- 生产：NO-GO（见 ACCEPTANCE / RISK-REGISTER）

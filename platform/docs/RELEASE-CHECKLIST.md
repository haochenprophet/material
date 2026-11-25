# 发布检查清单（RELEASE CHECKLIST）

## 1. 发布前

- [ ] `git -C material status --short` 只包含预期新增，旧代码无修改
- [ ] `php platform/bin/verify.php` → 0 FAIL
- [ ] `php platform/bin/release-check.php` → `release: READY`（44 项离线 / 48 项含 DB）
- [ ] `php platform/bin/snapshot.php` → 只更新 manifest/RELEASE（不复制代码）
- [ ] `php platform/bin/git-release.php` → git tag
- [ ] `php platform/tests/e2e.php` → PASS（需 8091）
- [ ] 数据库：新库 92 表/种子；旧 `materiel` 13/0
- [ ] 备份：数据库备份；代码由 git tag 保存（不再复制 release）
- [ ] 回滚路径：`docs/ROLLBACK.md` 已复核

## 2. 发布物

```text
git tag: ai-v<version> / ai-test-v<version>
manifest.json / RELEASE（git 跟踪文件清单）
```

- 不再创建 release 目录副本。
- 历史 release 副本已删除；不再创建目录副本。
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

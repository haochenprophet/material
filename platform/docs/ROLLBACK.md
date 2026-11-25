# 回滚手册（ROLLBACK，Git 版）

> 原则：版本由 **git commit + git tag** 管理；不使用目录复制 release。
> 禁止 `git reset --hard`、`git clean -fd`、`git checkout -- .`；不删除旧代码/旧 commit/tag。

## 1. 追溯

- `git -C material log --oneline --decorate`
- `git -C material tag --list`
- `/health`、`/trace`、页面底部的 `RELEASE` / `manifest.json`
- `manifest.json` 记录当前 git 跟踪文件的 SHA-256

## 2. 回滚到上一个 tag

```powershell
git -C material tag --list
git -C material show ai-v0.57.0 --stat
git -C material worktree add ..\material-rollback ai-v0.57.0
```

- 用 worktree 旁路启动旧 tag（示例端口 8092）：
  ```powershell
  php -S 127.0.0.1:8092 -t ..\material-rollback\platform\public ..\material-rollback\platform\public\router.php
  ```
- 验证 `/health`、`/`、`/bom`、`/admin/orders`、`/trace`。
- 确认后再切 Nginx/vhost 或临时停当前服务。
- 当前分支/当前目录保留，不删除。

## 3. 单文件回滚

```powershell
git -C material show ai-v0.57.0:platform/app/Router.php > platform/app/Router.php
php platform/bin/verify.php
```

- 只用于紧急单文件；优先用 worktree 整体回滚。
- 改完必须 commit 并重新 tag。

## 4. 发布

```powershell
git -C material switch ai
php platform/bin/release-check.php
php platform/bin/snapshot.php
git -C material add platform
git -C material commit -m "release: vX.Y.Z"
php platform/bin/git-release.php
```

`snapshot.php` 只更新 manifest/RELEASE，不再复制代码。

## 5. 禁止

- 删除 `material/class`、`material/.git`、旧 tag。
- 用复制目录冒充新版本。
- 未验证就切换入口。
- 覆盖当前故障现场。

## 6. 回滚判定

- `/health` 非 200 或 manifest 校验失败
- 核心路由 500
- 登录/CSRF 失效且 10 分钟无法修复
- 支付/结算金额不一致
- 旧代码被误改/误删

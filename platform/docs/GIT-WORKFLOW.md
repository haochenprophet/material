# Git 版本管控（不使用目录复制备份）

## 原则

- **版本 = git commit + git tag**；不再生成 `releases/` 代码副本。
- `bin/snapshot.php` 只更新 `manifest.json` / `RELEASE`，文件清单来自 `git ls-files`。
- `bin/git-release.php` 只创建 annotated tag，不复制代码。
- 旧 `releases/` 是历史遗留，保留不动，但不再新增/不作为发布流程。

## 分支

| 分支 | 内容 |
|---|---|
| `ai` | 生产代码、schema、迁移、工具、文档 |
| `ai-test` | `ai` + tests/ + seed 脚本 + 演示图片 |
| `main` | 旧代码，不改 |

## 发布流程

```powershell
# 1. 在 ai 或 ai-test 上修改并测试
php material\platform\bin\release-check.php --help 2>$null

# 2. 先 stage 代码/文档
git -C material add platform

# 3. 生成 manifest（只读 git 跟踪文件，不复制）
php material\platform\bin\snapshot.php

# 4. 提交（manifest/RELEASE 一并提交）
git -C material add platform
git -C material commit -m "release: vX.Y.Z"

# 5. tag（不 push）
php material\platform\bin\git-release.php
```

Tag 命名：

```text
ai-v0.58.0
ai-test-v0.58.0
```

## 回滚

- 查看 tag：`git -C material tag --list`
- 用 `git show <tag>` / `git worktree add` 取旧版本；
- **不要** `git reset --hard`、`git clean -fd`、`git checkout -- .`。
- 不删除当前分支/当前 commit；故障现场保留。

## 数据库

- 两个分支使用同一 schema/migration；分支切换不自动改库。
- 测试 seed/演示图片只在 `ai-test`。
- 数据库仍由 `schema-rebuild.php` 指向新库操作，不覆盖 `materiel`。

## 说明

- `manifest.json` 的 `release` 形如 `git-ai-v0.58.0`，以 git tag 为准。
- `verify.php` 在 `ai` 分支无 tests 时跳过测试/seed 文件检查；`release-check.php` 对缺失测试输出 SKIP。
- 不推送远程，除非用户明确要求。

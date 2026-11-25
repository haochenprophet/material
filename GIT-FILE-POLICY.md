# 全仓库 Git / 文件管理约束

适用目录：`E:\patent-sys\material` 整个仓库，不只是 `platform/`。

## 1. Git 约束

- 所有**源码、配置、迁移、文档、测试**必须被 git 追踪；`git status` 不得有未处理的源码文件。
- AI 代码只在 `ai` / `ai-test` 分支：
  - `ai`：生产代码、schema、迁移、工具、文档。
  - `ai-test`：`ai` + tests + seed + 演示图片。
  - `main`：旧代码，只读，不提交 AI 新功能。
- 版本 = **commit + annotated tag**；禁止目录复制 release、禁止 `releases/` 副本。
- 回滚用 `git worktree` / tag，不用复制目录。
- 禁止：`git reset --hard`、`git clean -fd`、`git checkout -- .`、删除旧分支/tag。
- 提交前：
  ```bash
  git -C material status --porcelain
  php material/platform/bin/verify.php
  php material/platform/bin/release-check.php
  ```
- 发布：
  ```bash
  git -C material switch ai
  php material/platform/bin/snapshot.php   # 只更新 manifest/RELEASE
  git -C material add .
  git -C material commit -m "release: vX.Y.Z"
  php material/platform/bin/git-release.php
  ```
- `snapshot.php` 只读 `git ls-files`，不再复制代码。
- 不把密钥、token、密码写入仓库；只提交 `.env.example`。

## 2. 文件约束

必须忽略（见根 `.gitignore`）：

```text
releases/ storage/ backups/ checkpoints/
migration-test-*/ preview/ uploads/ cache/ tmp/
*.log *.tmp *.bak *.orig *.rej *.crdownload
*.sqlite *.db
node_modules/ vendor/ dist/ build/
.env（保留 .env.example）
*.pem *.key
```

保留并提交：

- `app/`、`framework/`、`views/`、`public/` 源码
- `database/*.sql` schema/migration
- `tests/` 测试
- `docs/` 文档
- `deploy/` 配置样例
- 旧代码目录（`class/`、`material/`、`sop/` 等）

特殊已跟踪大文件：

- `database/hardware.sql`（约 14 MB，旧库 dump）
- `docs/` 下的 PDF/MHTML 参考资料

新增 >10 MB 二进制文件前必须明确说明用途；不得用复制目录做版本备份。

## 3. 当前例外

- `platform/storage/` 下仍有数据库备份、测试 SQLite、checkpoint 元数据；它们被忽略，不是源码。
- `platform/manifest.json` / `RELEASE` 被追踪，仅作当前版本标识；文件清单来自 git。
- 旧 `releases/` 已删除，不再恢复。

## 4. 检查命令

```bash
git -C material status --porcelain=v1 --untracked-files=all
git -C material ls-files | wc -l
git -C material tag --list
```

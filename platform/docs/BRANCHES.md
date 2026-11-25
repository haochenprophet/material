# Git 分支策略（AI / AI-TEST）

## 分支

| 分支 | 内容 | 用途 |
|---|---|---|
| `ai` | `platform/` 生产代码、schema、迁移、部署、工具、文档 | 发布/评审代码 |
| `ai-test` | `ai` + `tests/`、`bin/seed-*.php`、演示图片/测试资产、测试文档 | 演示数据与测试 |
| `main` | 原 `material/` 旧代码 | 不修改 |

## 数据库

- 两个分支使用**同一套数据库 schema / migration / 业务 SQL 逻辑**。
- 差异只在数据：
  - `ai`：不含测试 seed 脚本、测试图片。
  - `ai-test`：包含 demo seed、测试用例、测试图片。
- 生产库/旧库 `materiel` 不在分支里；刷新 schema 需显式执行 `bin/schema-rebuild.php` 或迁移，不使用分支切换自动改库。

## 发布

```bash
git switch ai
php platform/bin/verify.php
php platform/bin/release-check.php
```

`ai` 无 `tests/` 时：
- `verify.php` 跳过测试/seed 文件检查；
- `release-check.php` 对缺失测试脚本输出 SKIP。

## 测试/demo

```bash
git switch ai-test
php platform/bin/release-check.php
php platform/tests/regression.php
```

## 注意

- 不修改旧 `material/` 代码。
- 不删除旧 branch/release。
- 不 `git reset --hard`。
- 数据库重建必须指向新库，不能覆盖 `materiel`。

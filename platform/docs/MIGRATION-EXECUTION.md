# 迁移执行结果（干净目标库）

- 时间：2026-11-24 19:34（Asia/Shanghai）
- MySQL：8.0.28
- 目标库：`mats_platform_next2`（新建）
- 生成 SQL：`database/generated/mats_platform_next2/`
- 旧库：`materiel`（13 表 / 0 行，未变）

## 1. 执行命令

```powershell
php platform/bin/migration-plan.php mats_platform_next2 --write
$env:DB_USER='patent'; $env:DB_PASS='patent'
php platform/bin/migration-run.php mats_platform_next2 --execute
```

## 2. 结果

```text
target: mats_platform_next2
files:  7
tables: 77
stmts:  176
mode:   EXECUTE
done 001_master.sql ... done 006_seed.sql
tables_after: 77
OK: migrated 77 tables
```

| 项 | 值 |
|---|---|
| 表数 | 77 |
| role | 10 |
| permission | 25 |
| warehouse | 3 |
| category | 14 |
| ledger_account | 14 |
| commission_rule | 1 |
| `materiel` 表数 | 13（未变） |
| `materiel.materiel` 行数 | 0（未变） |

## 3. 事故记录：mats_platform_next

第一次执行 `mats_platform_next` 时，`003_listing.sql` 开头有 UTF-8 BOM，导致：

```text
FAIL 003_listing.sql: SQLSTATE[42000] ... near '﻿-- ...'
```

- 001/002 已执行，目标库处于**部分迁移**状态。
- 按“不删除”原则保留 `mats_platform_next`，作为事故现场。
- 修复：`MigrationRunner::rewrite` 与 `migration-run.php` 去除 UTF-8 BOM。
- 重新生成 `mats_platform_next2` 后一次通过。

## 4. 安全边界

- 只允许 `mats_platform_*` 新库；拒绝 `mats`/`materiel`。
- 目标库非空默认拒绝，`--allow-existing-empty` 仅用于空库。
- 默认 dry-run；必须 `--execute`。
- 失败即停，不删除、不回滚数据库。
- 应用仍使用 `DemoData`，**没有切 DSN**，没有对外服务。

## 5. 下一步

1. 应用 Repository 指向 `mats_platform_next2` 做只读联调（需单独配置）。
2. 45 表字段映射、旧 `materiel` 33 列迁移。
3. 备份/回滚演练、双人复核。
4. 生产独立实例，不在本机 `mats` 上继续。

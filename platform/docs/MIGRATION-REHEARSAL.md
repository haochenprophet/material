# 迁移演练结果（本地 MySQL 8.0.28）

- 时间：2026-11-24 19:26（Asia/Shanghai）
- 环境：MySQL `8.0.28`，`127.0.0.1`
- 目标库：`mats`（SQL 内硬编码）
- 旧库：`materiel`（13 表，0 行，只读）
- 未执行：生产切换、应用切 DSN、旧库写操作

## 1. 执行

```text
001_master.sql                done
002_inventory.sql             done
003_listing.sql               done
004_bom_trade.sql             done
005_migration_legacy.sql      done（源表 0 行）
005b_migration_templates.sql  done（bill/constitute/order 不存在，按设计 skip）
006_seed.sql                  done
```

## 2. 结果

| 项 | 值 |
|---|---|
| `mats` 表数 | 77 |
| role | 10 |
| permission | 25 |
| warehouse | 3 |
| category | 14 |
| ledger_account | 14 |
| commission_rule | 1 |
| `materiel` 表数 | 13（未变） |
| `materiel.materiel` 行数 | 0（未变） |

## 3. 发现（必须修）

1. **SQL 硬编码 `CREATE DATABASE mats; USE mats;`**
   - 本想跑 `mats_platform_test_20261124`，实际创建/写入了 `mats`。
   - 这是迁移脚本的严重可移植性问题；生产前必须参数化库名或使用独立实例。
2. 空库 `mats_platform_test_20261124` 被创建但未写入；按“不删除”原则保留，作为警示。
3. `005b` 因旧库没有 `bill/constitute/order` 自动跳过，符合设计。
4. 旧 `materiel` 仍 13 表 / 0 行，未被修改。

## 4. 结论

- 表结构/种子能在真实 MySQL 8.0.28 跑通（77 表）。
- **不能**直接把当前 SQL 用于多环境；先修“库名硬编码”。
- 应用仍用 `DemoData`，没有切到 `mats`；没有对外服务。
- 回滚：应用配置不动；保留 `mats` 与旧库，不做删除。

## 5. 下一步

1. 增加 `bin/migration-run.php`：校验目标库名、备份、执行、校验、失败即停。
2. SQL 增加占位符/包装脚本，禁止 `CREATE DATABASE` 硬编码。
3. 在独立 MySQL 实例/容器重跑，避免影响本机 `mats`。
4. 45 表字段映射与旧 `materiel` 33 列迁移。

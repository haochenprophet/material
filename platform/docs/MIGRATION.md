# 45 表迁移演练方案

> 只读审计已跑通：`001–006` 共 **77 张唯一表**，无重复、无缺失。
> 本文是**演练方案**，尚未执行任何生产迁移。

## 1. 事实基线

| 项 | 值 |
|---|---|
| 核心表 | 45（设计目标） |
| 总表 | 77（含辅助/模板/迁移表） |
| SQL 文件 | 7 个（001/002/003/004/005/005b/006） |
| 审计命令 | `php platform/bin/migration-audit.php` |
| 审计结果 | 27+8+8+30+1+3+0 = 77 unique |
| 旧库 | `materiel`（13 表，只读，不动） |

## 2. 执行顺序

```text
001_master.sql
002_inventory.sql
003_listing.sql
004_bom_trade.sql
005_migration_legacy.sql
005b_migration_templates.sql   # 可选：源库无 bill/constitute/order 时跳过
006_seed.sql
```

## 3. 新库

- 新库名：`mats_platform`（空库）
- 测试库：`mats_platform_test`
- 账号：`mats_app`（读写）、`mats_ro`（只读）
- **禁止**在旧 `materiel` 上执行 001–006。

## 4. 演练步骤

1. 备份旧库/旧代码；记录 Git 状态。
2. 建 `mats_platform_test` 空库。
3. 跑 `migration-audit.php`，确认 77 表无重复/缺失。
4. 按 001→006 执行；005b 先检查源库是否有模板表。
5. 校验：
   - `information_schema.tables` 数量 ≥45；
   - `material` 行数 = 旧库源行数（只读对账）；
   - 外键/索引存在；
   - 种子角色/权限/仓库/账本科目存在。
6. 跑应用集成测试（`verify` + `e2e`，DSN 指向 test）。
7. 通过后再建生产 `mats_platform`，重复 4–6。
8. 应用配置切到新库；旧库保留只读。

## 5. 回滚

- **不删新库**：保留现场。
- 配置指回旧库/旧 release，立即恢复读。
- 记录失败 SQL、行号、表名到 DEVLOG。
- 修复后在全新库重跑，不在失败库上“补救”。

## 6. 安全

- 账号最小权限；迁移账号只给 DDL/DML。
- 迁移窗口内停写；或双写+校验。
- 不把生产密码写进仓库；用环境变量。
- 迁移脚本只读审计，不由 Web 请求触发。

## 7. 本地演练结果

2026-11-24 已在 MySQL 8.0.28 执行 001–006：

- `mats`：77 表，role 10 / permission 25 / warehouse 3 / category 14 / ledger_account 14 / commission_rule 1
- `materiel`：13 表 / 0 行，未变
- 005b 按设计跳过缺失模板表
- 详细发现见 [`MIGRATION-REHEARSAL.md`](MIGRATION-REHEARSAL.md)

**重要发现**：SQL 内硬编码 `CREATE DATABASE mats; USE mats;`，不能直接指向其他库；生产前必须参数化。

已提供不执行 SQL 的计划器：

```powershell
php platform/bin/migration-plan.php mats_platform_next --write
```

生成 `database/generated/mats_platform_next/`，其中 CREATE DATABASE/USE 已改为目标库。详见 [MIGRATION-RUNNER.md](MIGRATION-RUNNER.md)。

执行器：

```powershell
php platform/bin/migration-run.php mats_platform_next2 --execute
```

已在 `mats_platform_next2` 跑通 77 表；事故与结果见 [MIGRATION-EXECUTION.md](MIGRATION-EXECUTION.md)。

## 8. 未完成

- 实际多环境迁移执行（本机已执行一次，不可复用为生产）
- 45/77 表与页面字段完整映射
- 旧 `materiel` 33 列 → 新 `material` 字段映射
- 双写/增量同步
- 性能/索引压测

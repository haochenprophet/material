# 迁移计划器（不执行 SQL）

## 问题

`001_master.sql` 硬编码：

```sql
CREATE DATABASE IF NOT EXISTS `mats`;
USE `mats`;
```

直接给不同环境跑会写错库。`MigrationRunner` 在**内存中重写**库名，生成目标库专用 SQL，不修改原始 SQL。

## 生成

```powershell
php platform/bin/migration-plan.php mats_platform_next --write
```

输出：

```text
target: mats_platform_next
files:  7
tables: 77
stmts:  176
written: platform/database/generated/mats_platform_next
OK: plan only; no SQL executed
```

生成文件里的 `CREATE DATABASE` / `USE` 均为目标库。

## 安全

- 目标必须匹配 `mats` / `mats_*`，禁止 `materiel`。
- 只读源 SQL、只写新生成目录；不连数据库、不执行 SQL。
- 原 `docs/material-platform/sql` 不改。
- 生成目录进 manifest，可回滚。

## 测试

`tests/migration-runner.php` 9 条：

- CREATE DATABASE / USE 改名
- 旧库名不再出现
- 拒绝 materiel/非法名
- plan 27 表/语句数/哈希

## 下一步

1. `migration-run.php --execute`：连接、备份、逐文件执行、失败即停。
2. 先对空库 `mats_platform_next` 执行，再校验 77 表。
3. 生产前独立实例 + 备份 + 双人复核。

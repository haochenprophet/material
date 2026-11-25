# 数据层（v0.2.0 骨架）

> 当前**没有**连接任何数据库，也没有执行迁移；页面仍用 `DemoData`。
> 本文说明如何从演示数据切到 MySQL，以及为什么现在不直接连旧库。

## 1. 边界

- 新库建议：`mats_platform`（空库），**不要**在旧 `materiel` 上直接改表。
- 旧库 `materiel` 只读；旧 `material/` 代码不动。
- 45 张核心表 DDL 已在 `docs/material-platform/sql/001–006`（项目文档），实施时复制到 `platform/database/migrations/`，按 001→006 顺序执行。
- 迁移前必须：全量备份、在 `mats_platform_test` 演练、验证 77 表、回滚脚本。

## 2. 已落地骨架

| 文件 | 作用 |
|---|---|
| `config/database.php` | DSN/账号从环境变量读，无硬编码密码 |
| `app/Infra/Database.php` | 懒连接；未配置时抛异常，不影响 UI |
| `app/Repository/MaterialRepository.php` | 物料读取接口 |
| `app/Repository/DemoMaterialRepository.php` | 演示实现，替换点 |
| `bin/db-check.php` | 只检查配置/可选连接，不建表不写数据 |
| `tests/db.php` | DSN/仓储测试，不连接 |
| `app/Repository/PdoMaterialRepository.php` | PDO 实现（开发表 `materials`） |
| `database/dev-schema.sql` | 开发用最小表（不是 45 表生产 schema） |
| `bin/dev-db.php` | 创建/填充 `storage/dev.sqlite`，只写新文件 |
| `tests/pdo.php` | SQLite 内存测试，9 条 |
| `app/Repository/PdoOrderRepository.php` | 订单/订单行 PDO 仓储（开发表） |
| `tests/order-repo.php` | 订单保存/查询/更新/去重 11 条 |

## 3. 切换步骤（未来）

1. 建空库 `mats_platform`，创建只读/读写两个账号。
2. 复制 SQL 001–006 到 `database/migrations/`。
3. `bin/db-check.php --connect` 通过。
4. 执行迁移 → 校验 45/77 表 → 种子数据。
5. 实现 `PdoMaterialRepository`，替换 `DemoMaterialRepository`。
6. 页面/控制器只依赖 `MaterialRepository` 接口，不改 UI。

## 4. 禁止

- 不把 `patent/patent` 或生产账号写进代码。
- 不在旧 `materiel` 库执行 DROP/ALTER。
- 不在未备份时跑迁移。

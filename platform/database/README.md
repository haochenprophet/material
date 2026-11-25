# database/

- 当前只放迁移/种子脚本，**尚未连接数据库**。
- 45 张核心表 DDL 参考项目文档 `docs/material-platform/sql/001_master.sql` … `006_seed.sql`。
- 迁移前务必：备份、在测试库演练、校验 77 表、准备回滚。
- 不要对旧 `materiel` 库执行 DROP/ALTER。
- 数据层说明见 `platform/docs/DATA-LAYER.md`。

# 真实库只读状态

## 命令

```powershell
$env:DB_USER='patent'; $env:DB_PASS='patent'; $env:DB_NAME='mats_platform_next2'
php platform/bin/db-status.php
php platform/tests/db-live.php
```

## 结果（2026-11-24 19:42）

```json
{
  "database": "mats_platform_next2",
  "tables": 92,
  "seed": {
    "role": 10,
    "permission": 25,
    "warehouse": 3,
    "category": 14,
    "ledger_account": 14,
    "commission_rule": 1
  },
  "legacy": {
    "tables": 13,
    "materiel_rows": 0
  },
  "read_only": true
}
```

测试 listing：material `TEST-TE-170364` / listing 1 条（`listed`）；另 1 条 UI 提报 `pending_review`，不进公开目录。

`tests/db-live.php`：PASS 6 / FAIL 0。

## 边界

- 只读查询，不写库。
- 只允许 `mats*` 库名，拒绝 `materiel`。
- 未设置 `DB_USER` 时测试 SKIP，不阻塞离线 CI。
- 应用仍未切 DSN；页面仍用 DemoData。
- 生产数据库账号/密码不写入仓库。

## 下一步

1. `PdoListingRepository` 只读映射 `listing`+`material`，先空表验证。
2. 插入一条测试 listing，验证页面可从 DB 读取。
3. 生产切 DSN 前做备份/回滚演练。

# 迁移重建（001–006 + 007–013）

## 目标

- 基础 7 文件（001–006）→ 77 表
- 平台增量 7 文件（007–013）→ 15 表
- 全新库重建 → **92 表**
- 不覆盖已有库；不删除旧库

## 命令

```powershell
$env:DB_USER='patent'; $env:DB_PASS='patent'
php platform/bin/schema-rebuild.php mats_platform_rebuild_20261125 --execute
```

- 目标必须是 `mats_platform_rebuild_*`
- 默认 dry-run
- 目标已有表 → 拒绝，不覆盖
- 失败保留现场，不 DROP

## 实测

```text
files: 14 (7 base + 7 additive)
tables: 92 (expected 92)
required tables: OK
```

新库：`mats_platform_rebuild_20261125`。

## 增量文件

| 文件 | 内容 |
|---|---|
| 007_commerce | provenance/comment/merchant profile |
| 008_reputation | review/credit/event/reply/appeal |
| 009_merchant_kyc | KYC |
| 010_material_image | attachment 软删 |
| 011_sandbox_payment | 沙箱支付/分账/退款 |
| 012_commission_version | 佣金版本 |
| 013_api_auth | API token/限流 |

## 测试

- `tests/migration-extended.php` 12 条
- `tests/migration-runner.php` 10 条
- `tests/migration.php` 8 条

## 边界

- `MigrationRunner` 原 7 文件路径仍保留；新库重建用 `schema-rebuild`。
- 未把增量纳入原 `migration-plan` 外部 SQL 目录（避免改动旧源）。
- 重建库是验证库，不切生产 DSN；旧 `materiel` 不动。

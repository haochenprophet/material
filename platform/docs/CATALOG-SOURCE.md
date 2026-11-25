# 目录数据源：DemoData ↔ 真实 DB

## 开关

```powershell
# 默认 DemoData
php -S 127.0.0.1:8091 -t platform/public platform/public/router.php

# DB 模式（只读）
$env:DB_USER='patent'; $env:DB_PASS='patent'; $env:DB_NAME='mats_platform_next2'
$env:CATALOG_SOURCE='db'
php -S 127.0.0.1:8095 -t platform/public platform/public/router.php
```

`CATALOG_SOURCE=db` 时：

- `CatalogRepositoryFactory` 返回 `PdoListingRepository`
- 首页/搜索/详情从 `listing` + `material` 读取。
- 公开目录只显示 `listing.status='listed'`；`pending_review` 不展示。
- 详情用 `CatalogDetailPresenter` 映射；DB 无记录时回退 DemoData。
- 未配置 `DB_USER` 时明确报错，不静默回退。

## 已验证

- `mats_platform_next2` 插入 1 条测试 listing（material code `TEST-TE-170364`）。
- `http://127.0.0.1:8095/` 返回 200，页面显示：
  - `TE 170364-1 connector`
  - `¥18.50 / 个`
  - `库存 1,240`
  - `A 级质检`
- 截图：`storage/preview/db-home.png`
- `http://127.0.0.1:8095/p/p1` 详情 200，标题/价格/库存来自 DB。
- 默认 8091 仍使用 DemoData，不受影响。

## 边界

- 只读；没有写回、没有下单落库。
- 测试 listing 只在本机 `mats_platform_next2`，旧 `materiel` 未动。
- 详情页已接 DB 仓储；演示数据仍保留 fallback。
- 生产需要分页、缓存、权限、字段脱敏。

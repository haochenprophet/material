# 旧 `materiel` 33 列字段映射

- 旧表：`materiel`，33 列
- 新表：`material` / `listing` / `stock_lot` / `material_provenance`
- 映射定义：`app/Domain/FieldMapping.php`
- 审计：`bin/mapping-audit.php`

## 覆盖

| 项 | 数量 |
|---|---|
| 旧列 | 33 |
| 直接映射 | 14 |
| 派生映射 | 19 |
| 未映射 | 0 |

## 映射表

| 旧列 | 新目标 | 类型 | 说明 |
|---|---|---|---|
| idmateriel | material.legacy_id | 直接 | 旧主键保留 |
| uuid | material.uuid | 直接 | UUID |
| time | material.created_at | 直接 | 创建时间 |
| name | material.name | 直接 | 名称 |
| type | material.attributes_summary.type | 派生 | 旧自由文本入 JSON |
| barcode | material.barcode | 直接 | 条码 |
| serial_number | material.attributes_summary.serial_number | 派生 | 序列号 |
| part_number | material.part_number | 直接 | MPN |
| date_code | material.attributes_summary.date_code | 派生 | 生产周期 |
| inbound_quantity | listing.quantity | 直接 | 入库数量 |
| cost_price | stock_lot.unit_cost | 派生 | 成本 |
| total_cost | stock_lot.unit_cost | 派生 | 按单价×数量留存 |
| price | listing.price | 直接 | 售价 |
| manufacturer | material.manufacturer_id | 派生 | 厂商名→维表 |
| manufacture_date | stock_lot.manufacture_date | 派生 | 生产日期 |
| life_date | stock_lot.expiry_date | 派生 | 有效期 |
| location | listing.location_id | 派生 | 库位文本→维表 |
| owner | listing.store_id | 派生 | 货主→店铺 |
| from | material_provenance.source_name | 派生 | 来源 |
| to | listing.warehouse_id | 派生 | 去向仓库 |
| how | material.attributes_summary.how | 派生 | 获取方式 |
| who | material.created_by | 直接 | 创建人 |
| what | material.description | 直接 | 描述 |
| when | material.updated_at | 直接 | 更新时间 |
| where | listing.location_id | 派生 | 地点 |
| why | material.attributes_summary.why | 派生 | 原因 |
| action | listing.status | 派生 | 动作→状态（待确认） |
| country | material_provenance.country | 直接 | 原产国 |
| url | material.datasheet_url | 直接 | 资料链接 |
| status | material.lifecycle_status | 直接 | 状态枚举 |
| remark | material.description | 派生 | 备注 |
| priority | material.attributes_summary.priority | 派生 | 优先级 |
| partial | material.attributes_summary.partial | 派生 | 部分/拆零 |

## 缺口与决策

1. `total_cost` 新库无独立列，按 `unit_cost × quantity` 重算，迁移日志留原值。
2. 厂商/库位/货主需维表匹配；匹配失败写入 `attributes_summary.legacy_*` 而不是丢弃。
3. `action/status` 是旧自由文本，迁移时进入 `listing.status` 映射表；未知值保留原文到 `attributes_summary.legacy_status`。
4. 旧 `from/to/who/when/where/why/how` 属 5W1H 语义，部分只保留在 JSON，不建议全部升为强列。
5. 迁移只读旧库，不删除旧表；导入脚本需幂等 + 回滚。

## 验收

```powershell
php platform/tests/field-mapping.php
# PASS 9 / FAIL 0

$env:DB_USER='patent'; $env:DB_PASS='patent'; $env:DB_NAME='mats_platform_next2'
php platform/bin/mapping-audit.php
# legacy 33 / mapping 33 / unmapped 0
```

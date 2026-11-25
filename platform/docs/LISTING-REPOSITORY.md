# 真实 listing/material 仓储

## 映射

`PdoListingRepository` 只读查询：

```sql
SELECT l.id, l.title, l.grade, l.price, l.quantity,
       m.part_number, m.description,
       w.name AS warehouse_name, loc.code AS location_code
FROM listing l
JOIN material m ON m.id = l.material_id
LEFT JOIN warehouse w ON w.id = l.warehouse_id
LEFT JOIN location loc ON loc.id = l.location_id
WHERE l.deleted_at IS NULL AND m.deleted_at IS NULL
```

输出与 `CuiProductCard` 兼容：

- `id`：`p{listing.id}`
- `title`：listing.title
- `spec`：part_number · grade 级
- `price`：¥18.50
- `stock`：库存 1,240
- `location`：仓库 + 库位
- `badge`：A 级质检 / 待质检

## 测试

`tests/listing-repo.php` 9 条（SQLite 内存）：

- all 2 条
- find p1
- 价格/库存格式化
- A 级 badge
- 位置合并
- 不存在 / 非数字 id 拒绝
- 接口实现

## 边界

- 只读；不写数据库。
- 公开目录只读 `status='listed'`；`pending_review` 不进入首页。
- `PdoListingWriter` 负责提报写入（material + listing pending_review）。
- 默认仍是 DemoData；DB 无记录时详情回退 DemoData。
- 生产切换前必须补字段映射、权限、缓存、分页。

## 下一步

1. 在 `mats_platform_next2` 插入 1 条测试 listing（不改旧库）。
2. 增加 `CATALOG_SOURCE=db` 开关，让首页只读该仓储。
3. 打开页面验证 DB → HTML。

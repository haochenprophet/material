# 提报 → 审核 → 上架

## 流程

```text
卖家 /seller/listings/new
  → POST /seller/listings
  → material + listing(pending_review)
  → 运营 /admin/listings
  → POST /admin/listings/{id}/approve
  → listing.status=listed
  → 公开目录 / 可见
```

## 已验证（8095）

1. seller 提报 `UI-TEST-1` → listing 2 `pending_review`
2. 首页不显示 pending
3. admin 审核通过 → listing 2 `listed`
4. 首页显示 `UI test listing`
5. 商品卡数量 1 → 2

## 代码

- `app/Repository/PdoListingWriter.php`
- `app/Repository/PdoListingReview.php`（事务 + `listing_review` + `audit_log`）
- `views/pages/admin-listings.php`（通过/驳回原因）
- `app/Router.php`
- `tests/listing-writer.php` 8 条
- `tests/listing-review.php` 10 条
- `tests/listing-repo.php` 10 条

## 审核审计

每次通过/驳回在同一事务内写：

- `listing.status`
- `listing_review(listing_id, action, reason)`
- `audit_log(action=listing.approve/reject, object_id)`

实测：

```text
listing 3 pending_review
→ admin 驳回（资料不全）
→ listing 3 rejected
→ listing_review.reason=资料不全
→ audit_log action=listing.reject
```

## 卖家状态列表

`/seller/listings`：

- `PdoSellerListingRepository` 查全部状态
- 展示 ID/MPN/标题/价格/数量/状态/时间
- 状态：待审核 / 已上架 / 已驳回
- 最新审核备注/驳回原因显示在“备注”列
- 待审核与已驳回不会出现在公开目录

实测 8095 seller：7 条，含待审核/已上架/已驳回。

## 提报幂等

`PdoListingWriter::create($data, $idempotencyKey)`：

- 事务写 material + listing
- 同时写 `listing_review(action=submit)` + `audit_log(action=listing.submit)`
- 幂等键 scope `listing.create`
- 同 key 重放返回同一 listing id，不重复写/不重复审计
- UI 表单带隐藏 `idempotency_key`

实测：

```text
新提报 listing 7
→ listing_review.action=submit
→ audit_log.action=listing.submit, actor_type=system
```

审核人：

```text
DB auth 模式（8096）dbadmin@demo
→ approve listing 4
→ listing_review.reviewer_id=2
→ audit_log.actor_id=2, actor_type=user
```

Demo 8091 无 DB id 时 reviewer_id 为 null，actor_type=system。

## 边界

- 审核只改 status/published_at，不删除数据。
- 没有审核备注、驳回原因、操作人审计（下一轮）。
- 没有事务/幂等键。
- 质检通过不会自动上架；当前审核与质检分离。
- 生产仍需权限/审计/通知。

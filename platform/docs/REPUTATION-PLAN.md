# 物格云 · 点评 / 信用 / 物料商城 开发计划（v2）

- 日期：2026-11-25
- 目标库：`mats_platform_next2`
- 原则：只加不删；旧 `material/`、`.git`、`materiel` 不动；每个阶段可回滚。

## 1. 业务目标

1. 物料级点评：1–5 星 + 质量/价格/交付/服务四维 + 文字；按物料汇总评分、评价数、好评率。
2. 商家级信用：聚合商家所有物料的点评，计算 0–100 分 + AAA/AA/A/B/C/D 等级，类似芝麻信用。
3. 商家商城：每个商家独立店铺页，展示商家信用、商品、全部点评。
4. 商家管控：信用分随点评/隐藏评价/退款/质检变化；后台可重算、隐藏违规点评。
5. 可溯源：素材来源、批次、供应商与点评关联，形成“物料-商家-点评-信用”链路。

## 2. 数据模型

- `material_review`：物料点评（rating、quality/price/delivery/service、verified_purchase、status、store_id、listing_id、user_id）。
- `material_credit`：物料聚合（review_count、avg_rating、score、grade、positive_rate、四维均分）。
- `merchant_credit`：商家聚合（review_count、avg_rating、score、grade、material_count、listed_count、verified_count、positive_rate）。
- `credit_event`：分数变化审计（subject、event、before/after、metadata）。
- `review_reply`：商家回复。
- 复用现有 `material_attachment` 图片；`material_comment` 保留讨论树。
- 复用 `store`/`org`/`merchant_profile`。

## 3. 信用公式（可解释、可测试）

物料分：
- 评分 70%：`(avg_rating-1)/4*70`
- 好评率 10%：4–5 星占比
- 评价量 10%：`min(count/50,1)*10`
- 真实交易 5%：verified 占比
- 质检/成色 5%：A=5、B=3、C=1、NA=0

商家分：
- 物料分加权 70%（按点评数加权）
- 店铺评分 10%
- 评价量 5%
- 真实交易 5%
- 在售物料 5%
- 商家认证/质检 5%

等级：AAA≥90，AA≥80，A≥70，B≥60，C≥50，D<50。

## 4. 阶段

| 阶段 | 内容 | 验收 |
|---|---|---|
| P0 | 008 表结构、种子商家、点评/信用 seed | 结构测试 PASS |
| P1 | CreditScoring + 仓储/服务 | 单测 PASS |
| P2 | 详情页点评区（汇总/列表/表单/商家卡） | `/p/1` 200 且可评论 |
| P3 | `/shops`、`/shop/{slug}` 商家商城 | 只列 listed，显示信用 |
| P4 | `/admin/credits` 商家/物料信用、点评隐藏、重算 | admin 200，seller 403 |
| P5 | 图片/溯源/评论树接入详情 | 每物料有图/溯源/树 |
| P6 | 回归、文档、release、30 分钟任务 | verify/release-check READY |

## 5. 8 小时 / 30 分钟任务

- 每 30 分钟执行一个阶段或一个可验证子项。
- 每轮：checkpoint → 代码/测试/文档 → verify → snapshot。
- 16 次任务：10:00–18:00（Asia/Shanghai），目标 8 小时。

## 6. 验收

- 每个物料有评分/评价数/信用等级/评价列表。
- 每个商家有信用分/等级/全部商品点评聚合。
- 点评可提交、可回复、可隐藏；信用重算有审计。
- 商家商城只展示 listed；后台可启停商家。
- 结构/单元/live 测试 PASS；旧代码与旧库不变。

# 订单 / 结算服务

> 内存版服务：不落库、不接支付/库存/物流。用于把状态机、报价、佣金公式串成可测试的业务链。

## 1. 订单

`OrderService::create($lines, $options)`：

- 校验非空、数量 >0、单价 ≥0
- `Pricing::quote` 计算商品/质检/履约/运费/服务费/含税
- 订单 ID：`SO-<timestamp>-<random>`
- 初始状态：`pending_payment`

`transition($order, $to)`：

```text
pending_payment → paid → allocating → shipped → received → completed
pending_payment → cancelled
received/completed → after_sales
```

非法流转抛 `DomainException`。

## 2. 结算

`SettlementService::fromOrder($order, $options)`：

- 以订单 `pricing.goods` 为成交额
- 佣金默认 20%
- 扣质检/物流/退款
- 卖家应结不为负
- 结算单 ID：`ST-<timestamp>-<random>`
- 初始 `pending`

状态：

```text
pending → frozen → approved → paid → reconciled
pending → cancelled
```

## 3. API

| 方法 | 路径 | 说明 |
|---|---|---|
| POST | `/api/v1/orders` | 创建内存订单（报价+状态） |
| POST | `/api/v1/settlements/preview` | 结算预览 |

API 创建的订单会写入 `OrderStore`（会话，最多 20 条），`/orders/{id}` 用 `OrderPresenter` 展示；页面刷新前有效，生产用 `OrderRepository`。

## 4. 测试

`tests/trade.php` 17 条：

- 订单 ID/状态/小计
- 完整履约流转
- 非法流转拒绝
- 空行/数量 0
- 佣金 20%、卖家应结 123
- 结算 pending→reconciled
- 跳级拒绝
- 卖家应结不为负

`tests/api.php` 23 条，含订单和结算预览。

## 5. 未完成

- 订单/结算持久化（Repository + MySQL）
- 支付回调、分账、退款
- 库存锁定/释放
- 发票/账本/对账
- 幂等键与重复回调

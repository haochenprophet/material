# 沙箱支付 / 分账 / 退款

> 仅沙箱，不接真实资金；不产生真实扣款，不得用于生产收款。

## 表

- `sandbox_payment`：支付单（payment_no/order_no/amount/status/idempotency_key）
- `sandbox_payment_split`：分账（seller / platform_commission）
- `sandbox_refund`：退款

## 流程

```text
created → capture → succeeded → 分账
succeeded/partial_refunded → refund → partial_refunded/refunded
```

- 创建幂等键重复 → 返回同一支付单
- 捕获：卖家 90% / 平台佣金 10%
- 退款：累计退款 ≤ 原金额
- 对账：成功支付合计 vs 分账合计

## 页面

`/admin/payments`

- 创建沙箱支付
- 捕获并分账
- 退款
- 对账差/支付单列表

## 代码

- `PdoSandboxPaymentRepository`
- `views/pages/admin-payments.php`
- `tests/payment-sandbox.php` 10 条

## 生产边界

1. 不接持牌支付机构；无真实签名/证书/回调网络。
2. 无清算、结算周期、手续费、发票、分账接收方 KYC。
3. 无退款审批流/风控；生产需审批+审计。
4. 正式支付应使用 `payment/payment_split/refund` 并在 `sales_order` 存在后落库；沙箱表只做演练。
5. 上线前必须做资金安全评审与对账演练。

## 实测

8095：

```text
create SO-SANDBOX-1 225.55 → SBX-...
capture → succeeded
refund 25.55 → partial_refunded
splits 225.55
refund 25.55
```

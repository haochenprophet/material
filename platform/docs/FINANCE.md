# 支付 / 复式记账

> 内存版：不接支付机构、不落库、不开发票。只验证状态与借贷规则。

## 1. 支付

`PaymentService`：

```text
created → processing → succeeded
             └──────→ failed
succeeded → refunded / partial_refunded
```

- 金额以订单 `pricing.total` 为准
- `capture` 必须给非空 `transaction_id`
- 全额退款 → `refunded`
- 部分退款 → `partial_refunded`
- 超额/0/负数拒绝

## 2. 复式记账

`LedgerService::post($ref, $lines)`：

每条分录只能借记或贷记，金额 ≥0 且不能同时为 0；所有分录借贷合计必须相等。

示例（支付成功）：

| 账户 | 借 | 贷 |
|---|---:|---:|
| payment_gateway | 225.55 | |
| seller_payable | | 123.00 |
| platform_revenue | | 37.00 |
| tax_payable | | 65.55 |

平衡：借 225.55 = 贷 225.55。

账户建议：

- `payment_gateway`：支付通道在途
- `seller_payable`：应付卖家
- `platform_revenue`：平台佣金/服务费
- `tax_payable`：销项税
- `qc_expense`、`logistics_expense`：质检/物流成本
- `buyer_receivable`、`refund_payable`

## 3. 不变量

1. 每批分录借贷相等。
2. 单条分录不能既借又贷。
3. 累计借贷总额始终相等。
4. 金额按 2 位小数四舍五入。
5. 退款不超过原支付。

## 4. 测试

`tests/finance.php` 21 条：

- 支付 created/captured/refunded/partial
- 空 transaction、超额退款、0 金额拒绝
- 平衡/不平衡/负数/零金额/同时借贷
- gateway/seller 余额
- 多批次累计仍平衡

## 5. 未完成

- 支付机构 SDK、回调签名、重放保护
- 分账/二清合规
- 账本落库、日结、对账
- 发票/红冲
- 幂等键与重复回调

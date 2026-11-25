# 支付回调 / 幂等 / 对账

> 内存版：不接真实支付机构。签名是 HMAC-SHA256 示例，生产必须加时间戳、重放窗口、证书/平台公钥、IP 白名单。

## 1. 回调事件

```json
{
  "event_id": "evt_1",
  "transaction_id": "TXN-1",
  "status": "succeeded",
  "amount": 225.55
}
```

签名：

```php
$signature = hash_hmac('sha256', $payload, $secret);
```

## 2. 处理顺序

1. 验签
2. JSON 解析
3. 校验 `event_id` / `transaction_id`
4. 校验金额与支付单一致
5. 幂等键：`payment:<payment_id>:<event_id>`
6. 成功 → `capture`；失败 → `fail`
7. 保存幂等结果

重放同一 `event_id` 返回 `replayed=true`，不重复改状态。

## 3. 对账

`ReconciliationService::reconcile($payment, $ledger)`：

- expected = 支付金额
- actual = 账本 `payment_gateway` 借方余额
- `diff = actual - expected`
- `|diff| <= 0.01` → `matched`，否则 `diff`

## 4. 测试

`tests/payment-callback.php` 15 条：

- 签名可复算
- 成功/失败回调
- 重放识别
- 坏签名/金额不一致/未知状态/缺 event_id
- 对账 matched/diff
- 幂等 store 读写

## 5. 生产 TODO

- 时间戳 + 5 分钟重放窗口
- 支付平台公钥/证书验签
- IP 白名单
- Redis/MySQL 幂等键 + 唯一约束 + TTL
- 回调原始报文留存 + 审计
- 对账差异工单/告警

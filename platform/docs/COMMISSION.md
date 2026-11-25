# 结算 / 佣金规则 / 发票

## 佣金规则

- 表：`commission_rule`
- 版本字段：`rule_key` + `version`（同 key 自动递增）
- 匹配：类目（NULL=全部）、卖家类型、生效日期
- 优先级：生效日 DESC → version DESC → id DESC
- 无匹配：回退 20% 默认（`fallback=true`）

## 计算

`CommissionCalculator`：

```text
commission = gross * rate
commission = max(commission, min_fee)
commission = min(commission, max_fee)  // max_fee 可为空
seller_net = gross - commission
```

## 后台

`/admin/commission`

- 佣金试算（金额/类目/卖家类型/日期）
- 命中规则/费率
- 平台佣金/卖家应结
- 新增规则版本
- 停用规则
- 规则版本列表

## 种子

`bin/seed-commission.php`：

- 默认规则：2%（原种子）
- 连接器类目：8%，最低 2，最高 50

## 测试

`tests/commission.php` 11 条：

- 20% 佣金
- 最低/最高费用
- 非法比例
- 类目规则命中
- 默认规则回退
- 新版本创建/按日期生效
- 停用规则

## 边界

- 发票表已存在但尚无开票 UI/税控对接
- 结算单尚未落 `settlement/settlement_line`（真实订单接入后）
- 规则仅按类目+卖家类型+日期；未做商家维度/活动叠加
- 佣金计算为演示规则，生产需财务/税务复核

# 商家认证 / KYC

## 表

`merchant_kyc`（一店一条）：

- 企业名称、统一社会信用代码、税号、法人
- 联系人/电话
- 资质文件 JSON、保证金、协议版本/签署时间
- 状态：pending / verified / rejected / expired
- 审核备注、审核人、审核时间

## 页面

- 卖家：`/seller/kyc` 提交/更新资料，查看状态/保证金/协议/审核备注
- 运营：`/admin/kyc` 查看全部，认证/驳回并写备注

## 规则

- 企业名称、证件号、协议版本必填
- 保证金 ≥0 且 ≤1 亿
- 提交后状态回到 pending，清空旧审核备注
- 审核只允许 pending/rejected → verified/rejected
- 证件真实上传在 Q06 接入；当前 documents 为占位

## 测试

- `tests/merchant-kyc.php` 9 条
- `tests/reputation-live.php` 校验表/KYC 行

## 边界

- 演示卖家取第一个 active 店铺；多店铺归属在 Q07 做隔离
- 未接 OCR/工商核验；生产需三方核验与人工复核

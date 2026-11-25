# API v1（首版只读/计算型）

- Base：`/api/v1`
- 响应：`{ data, meta:{request_id,version,release}, error }`
- 认证：首版无鉴权；**不可直接暴露公网**。生产必须加 OAuth2/API Key + 限流。
- Content-Type：请求/响应均 `application/json`。

## 1. 端点

| 方法 | 路径 | 说明 |
|---|---|---|
| GET | `/api/v1/health` | 健康检查/版本 |
| GET | `/api/v1/materials` | 物料列表（Demo） |
| GET | `/api/v1/materials/{id}` | 物料详情（p1 含阶梯价/规格） |
| GET | `/api/v1/orders/{no}` | 订单详情/时间线 |
| GET | `/api/v1/state` | 六类状态机定义 |
| POST | `/api/v1/quotes` | 报价计算 |
| POST | `/api/v1/bom/match` | BOM 成本匹配 |
| POST | `/api/v1/bom/parse` | BOM CSV 解析（≤500 行/1MB） |
| POST | `/api/v1/bom/upload` | BOM 文件上传（multipart，字段 file） |
| POST | `/api/v1/orders` | 创建内存订单（报价+状态） |
| POST | `/api/v1/settlements/preview` | 结算预览（佣金/卖家应结） |

## 2. 示例

### 报价
```powershell
$body = @{
  lines = @(
    @{ qty = 10; unit = 18.50 },
    @{ qty = 100; unit = 0.12 }
  )
  options = @{ qc_fee = 20; fulfillment_fee = 10; shipping = 5; service_rate = 0.03; tax_rate = 0.13 }
} | ConvertTo-Json -Depth 6

Invoke-RestMethod -Method Post -Uri http://127.0.0.1:8091/api/v1/quotes `
  -ContentType 'application/json' -Body $body
```

返回：
```json
{
  "data": {
    "goods": 197.0,
    "qc_fee": 20.0,
    "fulfillment_fee": 10.0,
    "shipping": 5.0,
    "service": 5.91,
    "subtotal": 237.91,
    "tax_included": 27.37,
    "total": 237.91
  },
  "meta": {"request_id":"req_...","version":"0.2.0","release":"v0.2.0-..."},
  "error": null
}
```

### BOM 匹配
```json
{
  "lines": [
    {"mpn":"TE 170364-1","qty":200,"offers":[{"supplier":"SZ","unit":18.5,"qc":20,"shipping":0}]},
    {"mpn":"ADUM1201ARZ","qty":80,"offers":[]}
  ]
}
```

返回 `matched`、`shortage`、`total`，每行 `status` = `quoted|substitute|shortage`。

## 3. 错误码

| HTTP | code | 场景 |
|---|---|---|
| 404 | NOT_FOUND | 物料/订单/接口不存在 |
| 422 | VALIDATION_ERROR | lines 为空/结构错误 |
| 500 | INTERNAL | 未捕获异常（生产隐藏细节） |

## 4. 安全边界

- 首版无认证，**只能本地/内网演示**。
- 生产加：API Key/OAuth2、IP/租户限流、审计、金额幂等键、BOM 脱敏。
- 金额以服务端计算为准；客户端传的 price 不可信。

# API Token / 限流

## 开关

```text
API_REQUIRE_TOKEN=1
```

- 默认关闭，本地演示无 token。
- 开启后 `/api/v1/health` 仍公开；其余 API 需 `Authorization: Bearer sk_...`。

## 表

- `api_token`：token_hash（SHA-256）、client_name、scopes_json、rate_limit、status
- `api_rate_limit`：token_id + window_start + count（固定 60 秒窗口）

## Scope

- `read`：GET
- `write`：POST + GET
- `admin`：全部

无 scope → 403；token 无效/吊销 → 401；超额 → 429 + `Retry-After: 60`。

## 管理

`/admin/api-tokens`

- 创建 token（明文只显示一次）
- scope/每分钟额度
- 吊销
- 最后使用时间

## 测试

`tests/api-auth.php` 11 条：创建/查找/scope/401/403/429/吊销。

## 边界

- 固定窗口；生产建议 Redis 滑动窗口。
- 未接 OAuth2/mTLS/IP 白名单。
- token 明文不落库；丢失只能重建。
- 审计只记 last_used_at，完整 API 审计需 event_log。

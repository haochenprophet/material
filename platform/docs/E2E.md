# HTTP E2E

## 运行

```powershell
# 先启动（任选其一）
php -S 127.0.0.1:8091 -t material\platform\public material\platform\public\router.php

# 再跑
php material\platform\tests\e2e.php
```

可用 `E2E_BASE_URL` 指向其他端口/环境。

## 覆盖

1. `/health` 200 + 版本/release
2. `/login` CSRF
3. seller 登录 → 卖家页
4. `/bom` 有上传表单
5. UI 上传 CSV → “已解析 2 行”
6. API 报价 → 小计 237.91
7. API 下单 → 待支付 / 小计 225.55
8. `/orders/{id}` 显示订单号/待支付
9. API 结算预览 → 卖家应结 123
10. API BOM 解析 → 1 行
11. `/trace` 含版本

## 设计

- 每次运行生成唯一 cookie jar（`storage/e2e/cookies-*.txt`），避免上一轮登录态污染。
- 不删除任何文件；临时 CSV/cookie 保留在 `storage/e2e/`。
- 服务未启动时返回 `SKIP`（退出 0），不阻塞离线检查。
- 仅本地演示；不用于生产压测。

## 结果

`PASS 17 / FAIL 0`（2026-11-24 19:40，v0.15.0）

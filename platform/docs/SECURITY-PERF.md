# 性能与安全基线

## 性能测试

`tests/perf.php` 6 条：

- CSS ≤60KB
- JS ≤120KB
- 无外部资源 URL
- 首页 HTML <120KB
- 首页无内联 script
- 首页有 viewport

实测（v0.20.0 基线）：

- CSS 约 22KB
- JS 约 4KB
- 首页约 9–11KB
- 无外部 CDN/字体/图片依赖

## 安全测试

`tests/security.php` 8 条：

- XSS 转义
- CSRF token ≥32
- CSP `default-src 'self'`
- CSP `script-src 'self'`
- 数据库密码不硬编码
- 未登录访问后台 302
- seller 访问后台 403
- 登录页有 CSRF

## 已实现

- 输出统一 `CuiHtml::e()`
- 登录 POST CSRF
- Session HttpOnly/SameSite=Lax
- CSP：`default-src 'self'`、`script-src 'self'`、`frame-ancestors 'self'`
- `X-Frame-Options: SAMEORIGIN`
- `Permissions-Policy: camera=(self), microphone=(), geolocation=()`
- 路由权限（未登录 302 / 无权限 403）
- 上传 CSRF + 扩展名/大小限制
- SQL 参数绑定（仓储测试）
- HSTS：仅 HTTPS 请求发送

## 已知缺口（未通过生产验收）

1. 本地 HTTP 不发送 HSTS；生产 HTTPS 后需验证
2. CSP 允许 `style-src 'unsafe-inline'`（内联样式）
3. 登录无限流/锁定/MFA → 已加会话级限流（5 次/15 分钟），MFA 仍缺
4. 无 API 鉴权/限流
5. 上传无病毒扫描
6. 无真实 WAF/监控/告警
7. 移动真机矩阵未执行（仅 500px/1440px 截图 + CSS 断点）

## 结论

- 本地演示基线：通过。
- 生产安全：**未通过**，需按上表补齐。

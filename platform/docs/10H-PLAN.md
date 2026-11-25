# 10 小时持续开发计划（2026-11-24 18:40 → 次日 04:40）

> 目标不是“10 小时写满代码”，而是**每小时都有可验证的增量、可回滚的快照、可追溯的记录**。
> 任何时刻都不等待用户输入；遇到阻塞先绕过并记录，不删除旧代码。

## 时间盒与门禁

| 时段 | 目标 | 产出 | 门禁 |
|---|---|---|---|
| 18:40–19:00 | 基线/UI 纵向切片 | 15 路由、25 UI 类、响应式 | verify 0 FAIL |
| 19:00–20:00 | 领域规则/API | 状态机/报价/BOM/6 API | 31+16 tests |
| 20:00–21:30 | 数据层 | Repository/PDO/迁移脚本（不碰旧库） | 连接/回滚演练 |
| 21:30–23:00 | 账号/RBAC/审计 | 登录/角色/权限矩阵 | 4 角色越权测试 |
| 23:00–00:30 | 上传/BOM 解析 | 本地/对象存储抽象、CSV 解析 | 10MB/500 行 |
| 00:30–02:00 | 交易服务 | 订单/支付/结算服务骨架 | 状态机/金额测试 |
| 02:00–03:00 | E2E/真机 | Playwright/设备矩阵 | 核心 30 条 |
| 03:00–04:00 | 性能/安全 | Lighthouse/axe/CSP/限流 | 预算达标 |
| 04:00–04:40 | 发布/闭环 | 最终 release + CLOSURE | verify 全绿 |

## 每轮固定动作

```powershell
php material\platform\bin\checkpoint.php --with-tests
```

- 记录时间/版本/Release/请求 ID/manifest 哈希/Git 状态/测试结果。
- 追加 `docs/DEVLOG.md`（CHG-编号）。
- 有代码改动 → `snapshot.php` 生成新 release；不覆盖旧 release。
- 任何失败 → 先修复；10 分钟修不好 → 旁路旧 release 并记录。

## 当前状态（20:00 检查点）

- v0.40.1 检查点快照（见 `RELEASE`；检查点前版本 v0.40.0-20261124-195621）。
- `verify.php` 255 PASS / 0 FAIL；`release-check.php` 30 项 READY。
- 已完成：UI/领域/API/Repository/PDO/MySQL 迁移/DB 目录/账号/RBAC/上传/BOM/E2E/性能/安全。
- 未完成：45 表完整字段映射、真实支付分账、对象存储/病毒扫描、真机/Playwright/微信/PDA、Lighthouse/axe、生产部署/监控。

| 计划项 | 状态 |
|---|---|
| 20:00–21:30 数据层 | 完成（77 表、PDO/迁移） |
| 21:30–23:00 账号/RBAC/审计 | 完成（DB auth/限流/审核审计） |
| 23:00–00:30 上传/BOM | 完成（本地存储/CSV 1MB/500 行） |
| 00:30–02:00 交易服务 | 完成（内存/会话，非真实收款） |
| 02:00–03:00 E2E | 部分完成（17 条 HTTP；无 Playwright/真机） |
| 03:00–04:00 性能/安全 | 部分完成（预算/CSP/限流；无 Lighthouse/axe） |
| 04:00–04:40 发布/闭环 | 完成（release/CLOSURE/ROLLBACK） |

## 下一优先（生产化，不冒充完成）

1. 45 表字段映射与旧 33 列迁移
2. 真实支付/分账沙箱与对账
3. 对象存储 + 病毒扫描 + Excel BOM
4. Playwright + 真机/微信/PDA 矩阵
5. 生产 Nginx/PHP-FPM/监控/备份
6. 通知/草稿/分页/卖家维度隔离

## 约束

1. 旧 `material/` 与 `.git` 只读；不删除、不 reset。
2. 新代码只进 `material/platform/`。
3. 不把 Demo 数据当生产；不对外收款。
4. 每个 release 可旁路启动；回滚见 `docs/ROLLBACK.md`。

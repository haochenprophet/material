# 回滚手册（ROLLBACK）

> 原则：**不删除旧代码、不覆盖唯一副本、不 git reset。**
> 回滚 = 找到上一个已验证 release，旁路启动/切换入口；当前版本保留为事故现场。

## 1. 追溯标识

每个请求响应头都有：

```text
X-App-Version: 0.1.0
X-Release-Id: v0.1.0-20261124-184000
X-Request-Id: req_xxxxxxxxxxxxxxxx
```

页面底部、`/trace`、`/health` 也能看到版本/Release ID。
每个文件在 `platform/manifest.json` 中有 SHA-256。

## 2. 发布快照结构

```text
platform/releases/v0.1.0-20261124-184000/
  app/ framework/ views/ public/ config/ docs/ bin/ tests/
  VERSION  RELEASE  manifest.json
```

它是**完整可运行副本**，不是 diff。启动上一个版本：

```powershell
php -S 127.0.0.1:8092 -t material\platform\releases\v0.1.0-20261124-184000\public material\platform\releases\v0.1.0-20261124-184000\public\router.php
```

然后访问 `http://127.0.0.1:8092/health` 与 `/trace` 验证。

## 3. 标准回滚步骤（不删除）

1. 记录事故：
   ```powershell
   php material\platform\bin\verify.php
   ```
   保存输出与当前 `X-Release-Id`。
2. 列出 release，按时间选上一个已知正常版本：
   ```powershell
   Get-ChildItem material\platform\releases | Sort-Object Name -Descending
   ```
3. 先旁路启动旧版本（8092），确认 `/health`、`/`、`/bom`、`/admin/orders` 正常。
4. 切换入口，二选一：
   - **推荐**：改 Nginx/Apache vhost 的 DocumentRoot 到旧 release 的 `public/`；
   - 临时：停当前 8091，启旧 release 到 8091。
5. 再验证：
   ```powershell
   Invoke-WebRequest http://127.0.0.1:8091/health
   Invoke-WebRequest http://127.0.0.1:8091/trace
   ```
6. 在 `docs/DEVLOG.md` 记录：时间、当前 Release、回滚到 Release、原因、验证人。
7. 当前版本目录保留；不要删除、不要覆盖。

## 4. 单文件回滚（不整站切换）

1. 在 `manifest.json` 找到文件旧 SHA-256。
2. 在目标 release 中找到同路径文件：
   ```text
   platform/releases/<good>/framework/components/CuiButton.php
   ```
3. 复制到临时目录验证，再替换当前文件；替换前先把当前文件复制到
   `platform/releases/incident-<timestamp>/`（新增，不删除）。
4. 运行：
   ```powershell
   php material\platform\bin\verify.php
   ```

## 5. 禁止事项

- 禁止 `git reset --hard`、`git clean -fd`、`git checkout -- .`。
- 禁止删除 `material/class`、`material/material`、`material/.git`。
- 禁止用新快照覆盖旧 release。
- 禁止在未跑 `verify.php` 的情况下切换入口。

## 6. 回滚判定

以下任一情况立即回滚：
- `/health` 非 200 或 manifest 校验失败；
- 首页/详情/BOM/后台任一核心路由 500；
- 登录 CSRF 失效且无法在 10 分钟内修复；
- 移动端 320–430px 出现横向滚动且影响主 CTA；
- 任何旧代码被误改/误删。

# 变更协议（CHANGE PROTOCOL）

## 1. 铁律

1. **不删除**旧代码、旧目录、`.git`、旧数据库文件。
2. **不覆盖**唯一副本；先快照，再改。
3. **不 git reset / clean / checkout -- .**。
4. 每次变更必须有一个 `CHG-YYYYMMDD-NNN` 编号。
5. 每次变更必须能回答：改了什么、为什么、怎么验证、怎么回滚。

## 2. 标准流程

### 变更前
```powershell
php material\platform\bin\verify.php
php material\platform\bin\snapshot.php
```
记录当前 `RELEASE` 与失败项。

### 变更中
- 只做小步、可审查的改动；一次只改一个关注点。
- 新文件优先，避免大范围重写。
- UI 改动必须同时检查 1440 / 760 / 500 宽度。
- 数据/金额/状态机改动必须补断言。

### 变更后
```powershell
php -l <changed.php>
php material\platform\bin\verify.php
```
- 追加 `docs/DEVLOG.md`。
- 更新 `docs/TRACEABILITY.md` 状态。
- 版本号按 semver：修复 `0.1.x`，新增页面 `0.2.0`，破坏性 `1.0.0`。
- 生成新快照：
```powershell
php material\platform\bin\snapshot.php
```

## 3. 回滚触发

- `verify.php` 失败且 10 分钟内无法修复；
- 核心路由 500；
- 旧代码被误改/误删；
- 移动端横向滚动影响主 CTA；
- 支付/结算金额不一致（未来接入后）。

回滚步骤见 `docs/ROLLBACK.md`。

## 4. 文件命名

- UI 类：`CuiXxx.php`，放在 `framework/` 或 `framework/components/`。
- 页面：`views/pages/xxx.php`，只调用 UI class，不写大段 HTML。
- 组件不得直接读 `$_GET/$_POST`；数据由 controller/route 传入。
- 所有输出经 `CuiHtml::e()`；白名单 HTML 才允许 `raw`。

## 5. 评审清单

- [ ] 没有删除/移动旧文件
- [ ] 新文件在 `platform/`
- [ ] `php -l` 通过
- [ ] `verify.php` 0 FAIL
- [ ] 桌面/移动截图通过
- [ ] DEVLOG/TRACEABILITY 已更新
- [ ] 新 release 已生成

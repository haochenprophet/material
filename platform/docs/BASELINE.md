# 基线记录（BASELINE）

- 记录时间：2026-11-24 18:40（Asia/Shanghai）
- 项目根：`E:\patent-sys\material`
- 新平台根：`E:\patent-sys\material\platform`
- 目标：先冻结“旧代码 + 环境 + Git 状态”，再开始新增代码；任何回滚都以本文件为源头。

## 1. 代码环境

| 项 | 实测值 |
|---|---|
| PHP CLI | 8.4.3（ZTS，Windows） |
| Apache | 2.4，mod_php 8.4.2（既有环境） |
| MySQL | 8.0.28 |
| 浏览器验收 | Chrome 最新版（headless 截图） |
| 旧站点 DocumentRoot | `D:\great-wall\mms\ems`（不在本仓库，不修改） |
| 旧代码目录 | `E:\patent-sys\material`（`class/`、`material/`、`table/`、`erp/` 等） |
| 新代码目录 | `E:\patent-sys\material\platform`（本次只新增） |

## 2. Git 基线（未提交、未删除）

```text
branch: main
pre-existing:
  D docs/plm/PLM-GB.pdf.crdownload
  ?? database/hardware.sql
```

说明：以上两项是本次开发开始前就存在的状态，**不是本次改动**。本次没有 `git add`、没有 commit、没有 reset、没有 checkout、没有删除任何旧文件。

## 3. 旧代码关键文件（必须保留）

以下文件在基线时存在，平台代码不得依赖修改它们：

- `material/class/mysql.php`
- `material/class/page.php`
- `material/class/login.php`
- `material/class/database.php`
- `material/material.php`
- `material/header.php`
- `material/login.php`
- `material/register.php`
- `material/.git/`

## 4. 新平台边界

```text
platform/
  public/index.php        # 新入口
  public/router.php       # 内置服务器路由
  app/                    # bootstrap / router / demo data
  framework/              # Cui / CuiHtml / components
  views/pages/            # 页面模板（只调用 UI class）
  public/assets/          # CSS/JS
  bin/                    # verify / snapshot
  tests/                  # smoke
  docs/                   # 本目录
```

- 旧 `material/*.php` 不修改、不删除、不移动。
- 新平台不写旧数据库；首版为 UI/流程演示，数据来自 `DemoData`。
- 生产接入 MySQL 时，通过 Repository 替换 DemoData，不改页面结构。

## 5. 首版已完成 / 未完成边界

已完成（v0.1.0）：
- 服务端渲染入口、路由、四套布局（app/pda/auth/error）；
- 22 个 UI class（基础组件 + 布局 + 页面壳）；
- 12 个页面路由（首页/搜索/详情/BOM/报价/订单/卖家/质检/PDA/后台/结算/追溯/组件库/登录）；
- 响应式 CSS（手机/平板/桌面/PDA）、无 JS 依赖渐进增强；
- `bin/verify.php` 自检、`bin/snapshot.php` 发布快照、manifest/SHA-256；
- 本目录的回溯文档。

未完成（明确 backlog，不伪装成完成）：
- 真实 MySQL 连接、账号/KYC、支付/分账、发票、物流、文件上传后端；
- 真实 BOM Excel 解析、匹配算法、报价计算；
- 自动化 E2E（Playwright）与真机矩阵；
- 生产 Nginx/PHP-FPM 部署与监控。

## 6. 基线验证命令

```powershell
php -l material\platform\app\bootstrap.php
php material\platform\bin\verify.php
php material\platform\bin\snapshot.php
```

`verify.php` 的“legacy”检查只验证旧文件仍存在，不修改它们。

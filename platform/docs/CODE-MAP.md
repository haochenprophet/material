# 代码地图（CODE MAP）

## 1. 入口与请求链

```text
public/index.php
  -> app/bootstrap.php（常量/自动加载/session/安全头/异常）
  -> app/Router.php（路由表/参数/404/health/login POST）
  -> framework/CuiPage.php（layout + view）
  -> framework/CuiLayout.php（app/pda/auth 外壳）
  -> views/pages/*.php（调用 UI class）
  -> framework/components/*（输出 HTML）
```

## 2. 核心类

| 类 | 文件 | 职责 |
|---|---|---|
| `Cui` | `framework/Cui.php` | URL/asset/icon/CSRF/money/release |
| `CuiHtml` | `framework/CuiHtml.php` | 转义/属性/标签/class |
| `CuiTheme` | `framework/CuiTheme.php` | 设计令牌 |
| `CuiIcon` | `framework/CuiIcon.php` | 内联 SVG sprite |
| `CuiLayout` | `framework/CuiLayout.php` | app/pda/auth 布局 |
| `CuiPage` | `framework/CuiPage.php` | view 渲染/JSON/404 |
| `CuiHeader` | `framework/CuiHeader.php` | 顶部搜索/菜单/头像 |
| `CuiSidebar` | `framework/CuiSidebar.php` | buyer/seller/admin/ops 侧栏 |
| `CuiBottomNav` | `framework/CuiBottomNav.php` | 底部导航 |
| `CuiButton` | `components/CuiButton.php` | 按钮 |
| `CuiBadge` | `components/CuiBadge.php` | 状态徽章 |
| `CuiCard` | `components/CuiCard.php` | 卡片/标题 |
| `CuiStat` | `components/CuiStat.php` | 统计块 |
| `CuiChips` | `components/CuiChips.php` | 筛选 chips |
| `CuiBreadcrumb` | `components/CuiBreadcrumb.php` | 面包屑 |
| `CuiPageHeader` | `components/CuiPageHeader.php` | 页头/动作 |
| `CuiField` | `components/CuiField.php` | 输入/下拉/文本域/错误 |
| `CuiTable` | `components/CuiTable.php` | 响应式表格（td data-label） |
| `CuiEmpty` | `components/CuiEmpty.php` | 空状态 |
| `CuiProductCard` | `components/CuiProductCard.php` | 商品卡 |
| `CuiMatchCard` | `components/CuiMatchCard.php` | 匹配卡 |
| `CuiStepper` | `components/CuiStepper.php` | 步骤条 |
| `CuiUploader` | `components/CuiUploader.php` | 拍照/相册上传 UI |
| `CuiQcItem` | `components/CuiQcItem.php` | 质检项 |
| `CuiKpi` | `components/CuiKpi.php` | KPI |
| `CuiRouter` | `app/Router.php` | 路由/健康检查/404 |
| `DemoData` | `app/Data/DemoData.php` | 只读演示数据 |

## 3. 路由表

| 路由 | View | 布局 | 说明 |
|---|---|---|---|
| `/` | home | app/buyer | 首页 |
| `/search` | search | app/buyer | 搜索 `q` |
| `/p/{id}` | detail | app/buyer | 详情 |
| `/bom` | bom | app/buyer | BOM |
| `/bom/{id}` | quote | app/buyer | 报价 |
| `/orders/{id}` | order | app/buyer | 订单 |
| `/seller` | seller | app/seller | 工作台 |
| `/seller/listings/new` | seller | app/seller | 提报 |
| `/seller/settlements` | settlement | app/seller | 结算 |
| `/qc/{id}` | qc | app/ops | 质检 |
| `/ops/pick/{id}` | pda | pda | 拣货 |
| `/admin/orders` | admin | app/admin | 后台 |
| `/ui` | ui | app/buyer | 组件库 |
| `/trace` | trace | app/buyer | 版本/清单 |
| `/login` | login | auth | 登录/CSRF |
| `/health` | JSON | — | 健康检查 |

## 4. 资源

| 文件 | 说明 |
|---|---|
| `public/assets/css/app.css` | 设计令牌、四端布局、组件样式 |
| `public/assets/js/app.js` | 侧栏/Toast/质检切换/上传提示 |
| `public/router.php` | PHP 内置服务器路由 |
| `public/.htaccess` | Apache rewrite |
| `bin/verify.php` | 语法/文件/类/路由/manifest/旧代码 |
| `bin/snapshot.php` | manifest + 不可变 release 副本 |

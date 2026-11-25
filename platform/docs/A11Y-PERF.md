# 性能 / 无障碍 / 真机复核

## 自动化

| 检查 | 结果 |
|---|---|
| `tests/perf.php` | 6 PASS：CSS/JS/HTML 预算、无外部资源、无内联 script、viewport |
| `tests/a11y.php` | 10 PASS：lang/viewport/skip-link/h1/button type/tabindex/alt/label/input name |
| `tests/security.php` | 12 PASS（CSP/X-Frame 等） |
| `tests/e2e.php` | 17 PASS（8091 HTTP） |

## 视觉

- 1440：首页/详情/BOM/PDA/登录
- 500：首页/详情/BOM/PDA/登录
- 截图：`storage/preview/final/`
- 500px 详情底部购买条不遮挡导航
- 截图见 `docs/UI-QA.md`

## 真机矩阵

| 设备/浏览器 | 状态 |
|---|---|
| Windows Chrome 1440 | PASS |
| Chrome headless 500 | PASS |
| iPhone Safari | **未测试** |
| Android Chrome | **未测试** |
| 微信内置浏览器 | **未测试** |
| 真实 PDA | **未测试** |

> Chrome headless 对 <500px 有最小窗口裁剪，390/375 不能作为真机结论。

## 工具缺口

- 未安装/未运行 Lighthouse、axe-core。
- 未做自动视觉回归（无基线 diff）。
- 未做弱网/低端机性能测试。
- 未做屏幕阅读器（NVDA/VoiceOver）实测。

## 结论

- 服务端静态基线：PASS。
- 真机/工具审计：**未完成**，不能作为生产验收依据。

## 复现

```powershell
php material\platform\tests\perf.php
php material\platform\tests\a11y.php
php material\platform\tests\security.php
php material\platform\tests\e2e.php
```

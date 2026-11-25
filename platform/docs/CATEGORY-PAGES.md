# 品类页 / 物料分类

## 1. 数据源

`material/database/hardware.sql`（MS Access → MySQL dump）

- 数据库：`hardware` / 导入学习库 `hardware_learning`
- 主表：`Greatwall CIS`，**25,270 行**
- 次表：`TMPPRTS`
- 字段：Part Type、Part Number、Component_Name、Description、Value、Package_Type 等

### Part Type 分布

| legacy | 品类 | 行数 |
|---|---|---:|
| 10RESISTOR | 电阻 | 4,454 |
| 12CONNECTOR | 连接器 | 5,609 |
| 13MECHANICAL | 机械/结构件 | 5,260 |
| 06IC | 集成电路 | 2,866 |
| 11CAPACITOR | 电容 | 1,904 |
| 07DISCRETE | 分立器件 | 1,822 |
| 09INDUCTOR | 电感/磁珠 | 1,514 |
| 02CHIPSET | 芯片组/PMIC | 1,094 |
| 03MEMORY_IC | 存储器 | 313 |
| 05PROGRAMMABLE_IC | 可编程/EEPROM | 238 |
| 04SYSTEM_COMPONENT | 系统组件 | 87 |
| 01CPU | 处理器 | 67 |
| 0CCOMMUNCATION | 通信模块 | 42 |
| **合计** | **13 类** | **25,270** |

封装：SMD 18,454 / DIP 6,660 / 空 156。

### 数据质量

- `Sub-category` 全部为空，必须用 Component_Name/Description 关键词归类。
- `12CONNECTOR` 中混有 `VR` 电位器、CPU socket 等，已按关键词重新分到电阻/IC 插座。
- 旧数据存在 `#N/A`、`[HF]`/`[HE]` 厂商标记、重复料号；fixture 仅取代表样本并保留来源。
- `Value` 单位不统一（OHM/KOHM/UF/NF），页面参数仅作提示，选型需核对原厂 datasheet。

## 2. 分类体系

13 个一级品类，参考立创商城：

```text
电阻 / 电容 / 电感磁珠 / 连接器
分立器件 / 集成电路 / 处理器 / 芯片组PMIC
存储器 / 可编程EEPROM / 通信模块 / 系统组件 / 机械结构件
```

每类有：
- `legacy_type`：旧库 Part Type
- `subs`：立创风格子类（贴片电阻/铝电解/板对板/IC 插座等）
- `params`：阻值、容量、耐压、间距、Pin 数等参数字典
- `guide`：采购/选型提示

配置：`platform/config/category-pages.php`
样本：`platform/database/fixtures/category-samples.json`（13×8=104 条）

## 3. 页面

| 路由 | 内容 |
|---|---|
| `/` | 搜索框上方 13 品类卡 + 品类搜索 |
| `/category/{slug}` | hero、统计、子类 chips、商家/价格/交期筛选、报价卡、参数/商家侧栏 |

数据表：
- `material.category_key` / `subcategory_key`（014 迁移）
- 演示报价：104 条材料 + 104 条 listing，均标 `sample=true` / `legacy-cis`

页面价格/库存/交期为**演示参考**，不是立创或商家的真实报价。

## 4. 任务队列

`docs/CATEGORY-TASK-QUEUE.md`：500 条，覆盖 13 类 × 36 条 + 32 条全局/发布任务。

- 已形成数据/配置/仓储/页面/筛选/测试基础。
- 剩余：真机、a11y、axe/Lighthouse、API、后台、SEO、压测、最终验收等。
- 每轮只取最前 PENDING，更新状态与验收证据。

## 5. 复现

```powershell
$env:DB_USER='patent'; $env:DB_PASS='patent'; $env:DB_NAME='mats_platform_next2'
php platform/bin/seed-category-catalog.php
php platform/tests/category-pages.php
php platform/bin/build-category-queue.php
```

截图：`platform/storage/preview/category-home.png`、`category-resistor.png`。

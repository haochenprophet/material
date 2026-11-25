<?php
declare(strict_types=1);

/**
 * 生成 500 条品类页任务队列（确定性、可重复）。
 * 用法：php platform/bin/build-category-queue.php
 */
if (PHP_SAPI !== 'cli') { http_response_code(403); exit(1); }
require dirname(__DIR__) . '/app/bootstrap.php';

$categories = require PLATFORM_PATH . '/config/category-pages.php';
$tasks = [];
$doneKeys = array_flip([
    'disc-1','disc-2','disc-3','disc-4',
    'data-1','data-2','data-3','data-4',
    'be-1','be-2','be-3','be-5',
    'ui-1','ui-2','ui-3','ui-4','ui-5','ui-6','ui-7','ui-8',
    'flt-1','flt-2','flt-3','flt-4',
    'int-1','int-3',
    'test-1','test-2','doc-1',
]);
$phaseNames = [
    'disc' => '01 数据发现与分类',
    'data' => '02 样本与数据管道',
    'be' => '03 报价查询后端',
    'ui' => '04 品类页 UI',
    'flt' => '05 筛选与排序',
    'int' => '06 首页与全站集成',
    'qa' => '07 无障碍/性能/视觉',
    'test' => '08 测试与观测',
    'doc' => '09 文档/SEO/运营',
];
$templates = [
    'disc' => [
        ['1', '核对 {name} legacy Part Type={legacy} 与 {count} 行样本', 'Part Type 映射表已记录'],
        ['2', '{name} 子类、封装、商家分布统计', '子类/封装/商家分布写入分析文档'],
        ['3', '{name} 与立创类目映射及参数字典', 'LCSC 类目/参数项已确认'],
        ['4', '{name} 异常值/重复/脏数据清单', '异常样本有处置结论'],
    ],
    'data' => [
        ['1', '抽取 {name} 8 条代表性样本', '每条含料号/厂商/封装/子类'],
        ['2', '{name} 样本写入 category-samples.json', 'JSON 可重复生成'],
        ['3', 'seed {name} 演示材料与报价', '104 条中本类 8 条'],
        ['4', '校验 {name} 参数/价格/交期/库存', '字段范围与来源标记正确'],
        ['5', '{name} 去重与 data quality 标记', '无重复 code/料号，标记 sample'],
    ],
    'be' => [
        ['1', '{name} 报价仓储查询', 'PdoCategoryCatalogRepository.offers 通过'],
        ['2', '{name} 子类/商家/价格/交期过滤', 'SQL 参数化，无注入'],
        ['3', '{name} 报价/商家/最低价/交期聚合', 'stats/merchants 通过'],
        ['4', '{name} 分页/排序/最大 60 条限制', '分页或 limit 行为明确'],
        ['5', '{name} 空态/无数据库降级', '无 DB 时页面不 500'],
    ],
    'ui' => [
        ['1', '{name} hero（图标/描述/LCSC 映射）', '首屏显示品类身份'],
        ['2', '{name} 统计卡（样本/报价/商家/起价/交期）', '数字来自配置与 DB'],
        ['3', '{name} 子类 chips', '带计数、可切换'],
        ['4', '{name} 筛选栏', '商家/价格/交期/排序'],
        ['5', '{name} 报价卡（商家/价格/库存/交期/参数）', '8 项信息完整'],
        ['6', '{name} 商家/参数侧栏', '商家可跳转，参数可读'],
        ['7', '{name} 移动端 390/500 布局', '无横向滚动，CTA 可见'],
        ['8', '{name} 空态/加载/错误态', '无报价时给出 BOM CTA'],
    ],
    'flt' => [
        ['1', '{name} 子类筛选 URL 状态', '刷新/分享后保持'],
        ['2', '{name} 商家筛选 URL 状态', '重置后恢复全部'],
        ['3', '{name} 价格区间/交期筛选', '边界值 0/空值安全'],
        ['4', '{name} 排序 + 计数一致', '排序不改结果总数'],
    ],
    'int' => [
        ['1', '首页 {name} 品类卡入口', '13 卡可见、链接正确'],
        ['2', '全局搜索到 {name} 的跳转', '搜索/品类双入口'],
        ['3', '{name} 报价到详情/商家页链接', 'listing_id 链接 200'],
    ],
    'qa' => [
        ['1', '{name} a11y 语义/键盘', '标题/标签/对比度通过'],
        ['2', '{name} 性能预算', 'HTML/CSS 不超预算'],
        ['3', '{name} 视觉回归 1440/500', '截图无溢出/遮挡'],
    ],
    'test' => [
        ['1', '{name} 配置/仓储/过滤单测', 'category-pages.php PASS'],
        ['2', '{name} HTTP 渲染/链接 E2E', '路由 200，卡片可达'],
    ],
    'doc' => [
        ['1', '{name} 品类页说明与数据口径', '来源/演示价格免责声明'],
        ['2', '{name} SEO 标题/描述/运营文案', '可索引、无重复'],
    ],
];
$n = 0;
foreach ($categories as $key => $c) {
    foreach ($templates as $phase => $rows) {
        foreach ($rows as [$suffix, $title, $accept]) {
            $n++;
            $id = 'CAT-' . str_pad((string) $n, 3, '0', STR_PAD_LEFT);
            $status = isset($doneKeys[$phase . '-' . $suffix]) ? 'DONE' : 'PENDING';
            $tasks[] = [$id, $phaseNames[$phase], $c['name'], str_replace(['{name}', '{legacy}', '{count}'], [$c['name'], $c['legacy_type'], (string) $c['count']], $title), $accept, $status];
        }
    }
}
$global = [
    ['LCSC 类目树与 13 个 legacy 类型映射', '映射文档完成', 'DONE'],
    ['hardware.sql 25,270 行数据画像', 'total/part type/package 统计完成', 'DONE'],
    ['参数字典（阻值/容量/耐压/封装/交期）', '每类参数项完成', 'DONE'],
    ['Greatwall CIS 抽取脚本', 'bin/build-category-samples.php 可运行', 'DONE'],
    ['样本 fixture 校验', '13×8 样本，字段完整', 'DONE'],
    ['seed 幂等/来源标记', '重复执行不重复插入', 'DONE'],
    ['报价仓储与参数化查询', 'SQL 注入防护', 'DONE'],
    ['品类页路由 /category/{slug}', '13 个 slug 200', 'DONE'],
    ['首页品类 hub 13 卡', '搜索框上方展示', 'DONE'],
    ['品类页视觉系统（hero/cards/aside）', '1440/500 截图通过', 'DONE'],
    ['品类配置/仓储单测', '15 条 PASS', 'DONE'],
    ['品类数据文档与免责声明', 'docs/CATEGORY-PAGES.md', 'DONE'],
    ['全局搜索按品类过滤', '搜索与品类联动', 'PENDING'],
    ['品类页 API /api/v1/categories', 'API 返回品类+计数', 'PENDING'],
    ['品类页缓存/分页', '无 N+1，翻页可用', 'PENDING'],
    ['品类管理后台', '管理员可启停/排序', 'PENDING'],
    ['商家维度价格/交期看板', '按商家聚合', 'PENDING'],
    ['品类 SEO sitemap/结构化数据', '可被搜索引擎索引', 'PENDING'],
    ['移动端 390 真机复核', '真机截图/无溢出', 'PENDING'],
    ['无障碍 axe/键盘审计', '0 serious/critical', 'PENDING'],
    ['性能 Lighthouse 预算', '性能分≥80', 'PENDING'],
    ['大数据量 25,270 样本分页压测', 'P95<300ms', 'PENDING'],
    ['核心 Web Vitals 预算', 'LCP/CLS 达标', 'PENDING'],
    ['运营选品/告警规则', '缺货/异常价格告警', 'PENDING'],
    ['品类数据质量看板', '脏数据/重复率可见', 'PENDING'],
    ['价格/交期演示数据标识', '全站明确非真实报价', 'DONE'],
    ['品类页 E2E 回归', '13 页 200', 'PENDING'],
    ['全仓库 Git/文件约束', '无未追踪源码', 'DONE'],
    ['发布 tag + 回滚演练', 'tag/verify READY', 'PENDING'],
    ['500 任务队列审计', '每项有验收/状态', 'DONE'],
    ['下一轮优先级评审', 'PENDING 排序完成', 'PENDING'],
    ['最终验收/CLOSURE', 'Go/No-Go 记录', 'PENDING'],
];
foreach ($global as [$title, $accept, $status]) {
    $n++;
    $tasks[] = ['CAT-' . str_pad((string) $n, 3, '0', STR_PAD_LEFT), '10 全局/发布', '全品类', $title, $accept, $status];
}
if (count($tasks) !== 500) { fwrite(STDERR, '任务数错误: ' . count($tasks) . PHP_EOL); exit(1); }
$doneCount = count(array_filter($tasks, static fn ($t) => $t[5] === 'DONE'));
$out = "# 品类页 500 任务队列\n\n";
$out .= "> 生成：" . date('Y-m-d H:i') . " · 来源 `material/database/hardware.sql`（25,270 行 / 13 类）· 参考立创商城类目\n";
$out .= "> 状态：DONE {$doneCount} / PENDING " . (500 - $doneCount) . "；每轮取第一个 PENDING，完成并校验后更新。\n\n";
$out .= "## 队列规则\n\n";
$out .= "1. 每轮只取最早 PENDING；完成代码/数据/测试/文档任一以上。\n2. 验收必须可复现；UI 需截图或 HTTP 证据。\n3. 数据库只新增/迁移，不覆盖旧 `materiel`。\n4. 不复制 release 备份，版本用 Git commit/tag。\n5. 演示价格/库存/交期必须标注“演示参考”。\n\n";
$out .= "## 状态统计\n\n| 状态 | 数量 |\n|---|---:|\n| DONE | {$doneCount} |\n| PENDING | " . (500 - $doneCount) . " |\n| 合计 | 500 |\n\n";
$out .= "## 任务表\n\n| ID | 阶段 | 品类 | 任务 | 验收 | 状态 |\n|---|---|---|---|---|---|\n";
foreach ($tasks as $t) {
    $out .= '| ' . implode(' | ', array_map(static fn ($v) => str_replace('|', '/', (string) $v), $t)) . " |\n";
}
$path = PLATFORM_PATH . '/docs/CATEGORY-TASK-QUEUE.md';
file_put_contents($path, $out);
echo 'tasks: ' . count($tasks) . PHP_EOL;
echo 'done:  ' . $doneCount . PHP_EOL;
echo 'path:  ' . $path . PHP_EOL;

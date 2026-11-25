<?php
declare(strict_types=1);

/**
 * 首版演示数据。所有页面只读这里，后续替换为 Repository/MySQL 即可。
 * 数据与 v1.3 UI 方案中的示例保持一致。
 */
final class DemoData
{
    /** @return array<int, array<string, mixed>> */
    public static function products(): array
    {
        return [
            [
                'id' => 'p1',
                'title' => 'TE 170364-1 连接器',
                'spec' => '4Pin · 2.54mm · 直插',
                'price' => '¥18.50',
                'unit' => '个',
                'stock' => '库存 1,240',
                'location' => '深圳',
                'badge' => 'A 级质检',
                'badgeKind' => 'ok',
                'thumb' => 'TE 连接器',
                'thumbClass' => '',
            ],
            [
                'id' => 'p2',
                'title' => 'GRM188R71H104KA93D',
                'spec' => '0402 · 100nF · 50V',
                'price' => '¥0.12',
                'unit' => '个',
                'stock' => '库存 86,000',
                'location' => '苏州',
                'badge' => 'A 级质检',
                'badgeKind' => 'ok',
                'thumb' => 'Murata 电容',
                'thumbClass' => 't2',
            ],
            [
                'id' => 'p3',
                'title' => 'STM32F103C8T6',
                'spec' => 'LQFP48 · 72MHz',
                'price' => '¥9.80',
                'unit' => '个',
                'stock' => '库存 320',
                'location' => '香港',
                'badge' => 'B 级',
                'badgeKind' => 'warn',
                'thumb' => 'ST MCU',
                'thumbClass' => 't3',
            ],
            [
                'id' => 'p4',
                'title' => 'Molex 5051-04',
                'spec' => '4Pin · 2.5mm · 直插',
                'price' => '¥2.30',
                'unit' => '个',
                'stock' => '库存 5,600',
                'location' => '东莞',
                'badge' => 'A 级质检',
                'badgeKind' => 'ok',
                'thumb' => 'Molex 连接器',
                'thumbClass' => 't4',
            ],
        ];
    }

    /** @return array<string, mixed>|null */
    public static function product(string $id): ?array
    {
        foreach (self::products() as $p) {
            if ($p['id'] === $id) {
                return $p;
            }
        }
        return null;
    }

    /** @return array<string, mixed> */
    public static function detail(): array
    {
        return [
            'id' => 'p1',
            'title' => 'TE 170364-1 连接器 4Pin 2.54mm 直插',
            'crumbs' => [
                ['label' => '首页', 'href' => '/'],
                ['label' => '连接器', 'href' => '/search'],
                ['label' => 'TE 170364-1'],
            ],
            'badges' => [['A 级质检', 'ok'], ['寄售', 'blue'], ['深圳 · 1,240', '']],
            'price' => '¥18.50',
            'unit' => '个',
            'tax' => '含税',
            'tier' => [
                ['1–9', '¥18.50'],
                ['10–99', '¥17.20'],
                ['100–999', '¥15.80'],
                ['1000+', '¥14.50'],
            ],
            'stock' => '1,240（可售 1,180）',
            'lead' => '48 小时内发货',
            'warranty' => '30 天，检测报告随货',
            'package' => '直插 · 4Pin · 2.54mm',
            'seller' => '深圳华强北 · 认证卖家 ★4.8',
            'specs' => [
                ['额定电流', '3A'],
                ['额定电压', '250V'],
                ['工作温度', '-40°C ~ +105°C'],
                ['RoHS', '符合'],
                ['包装', '散新 / 原厂袋'],
            ],
        ];
    }

    /** @return array<int, array<string, mixed>> */
    public static function bomLines(): array
    {
        return [
            ['no' => 1, 'mpn' => 'TE 170364-1', 'desc' => '4Pin 连接器 · 需 200 · 寄售', 'status' => '可报价', 'kind' => 'ok', 'active' => true],
            ['no' => 2, 'mpn' => 'GRM188R71H104KA93D', 'desc' => '0402 电容 · 需 5,000 · 自有', 'status' => '可报价', 'kind' => 'ok', 'active' => false],
            ['no' => 3, 'mpn' => 'STM32F103C8T6', 'desc' => 'LQFP48 · 需 120 · 外部', 'status' => '替代', 'kind' => 'warn', 'active' => false],
            ['no' => 4, 'mpn' => 'ADUM1201ARZ', 'desc' => 'SOIC8 · 需 80 · 缺料', 'status' => '缺料', 'kind' => 'fail', 'active' => false],
        ];
    }

    /** @return array<int, array<string, mixed>> */
    public static function matches(): array
    {
        return [
            ['title' => '深圳华强北 · 寄售', 'price' => '¥18.50', 'tags' => ['A 级', '库存 1,240', '48h'], 'note' => '总成本最低，含检测报告', 'selected' => true],
            ['title' => '苏州工业园区 · 寄售', 'price' => '¥19.20', 'tags' => ['B 级', '库存 3,600', '72h'], 'note' => '价格略高，交期更稳', 'selected' => false],
            ['title' => '外部供应商 · 现货', 'price' => '¥21.80', 'tags' => ['需验货', '库存 20,000', '5 天'], 'note' => '适合大批量', 'selected' => false],
        ];
    }

    /** @return array<string, mixed> */
    public static function quote(): array
    {
        return [
            'id' => 'Q-20261124-018',
            'bom' => '智能网关 v3 · 128 行',
            'matched' => '112 / 128',
            'lead' => '7 天',
            'total' => '¥86,400',
            'valid' => '24 小时',
            'lines' => [
                ['TE 170364-1', '200', '¥18.50', '¥3,700', '可报价'],
                ['GRM188R71H104KA93D', '5,000', '¥0.12', '¥600', '可报价'],
                ['STM32F103C8T6', '120', '¥9.80', '¥1,176', '替代'],
                ['ADUM1201ARZ', '80', '—', '—', '缺料'],
            ],
        ];
    }

    /** @return array<int, array<string, mixed>> */
    public static function orders(): array
    {
        return [
            ['no' => 'SO-20261124-0081', 'buyer' => '苏州智控', 'amount' => '¥12,480', 'status' => '待发货', 'kind' => 'warn', 'seller' => '华强北-01', 'time' => '18:12'],
            ['no' => 'SO-20261124-0080', 'buyer' => '深圳创客', 'amount' => '¥3,260', 'status' => '已结算', 'kind' => 'ok', 'seller' => '东莞-03', 'time' => '17:55'],
            ['no' => 'SO-20261124-0079', 'buyer' => '杭州电子', 'amount' => '¥86,400', 'status' => '争议', 'kind' => 'fail', 'seller' => '苏州-02', 'time' => '17:31'],
            ['no' => 'SO-20261124-0078', 'buyer' => '上海维修', 'amount' => '¥1,280', 'status' => '待质检', 'kind' => 'warn', 'seller' => '深圳-05', 'time' => '17:02'],
        ];
    }

    /** @return array<string, mixed>|null */
    public static function order(string $no): ?array
    {
        foreach (self::orders() as $o) {
            if ($o['no'] === $no) {
                return $o + [
                    'lines' => [
                        ['TE 170364-1', '200', '¥18.50', '¥3,700'],
                        ['GRM188R71H104KA93D', '5,000', '¥0.12', '¥600'],
                    ],
                    'timeline' => [
                        ['下单', '2026-11-24 18:12', 'done'],
                        ['支付', '2026-11-24 18:15', 'done'],
                        ['分账', '2026-11-24 18:15', 'done'],
                        ['发货', '预计 2026-11-26', 'current'],
                        ['收货', '—', 'todo'],
                        ['结算', '收货后 T+7', 'todo'],
                    ],
                ];
            }
        }
        return null;
    }

    /** @return array<string, mixed> */
    public static function qc(): array
    {
        return [
            'id' => 'QC-20261124-018',
            'material' => 'TE 170364-1',
            'batch' => 'L-20261120',
            'qty' => '1,240',
            'due' => '剩余 2h',
            'items' => [
                ['外观 / 引脚', 'pass', '无氧化、无变形、引脚完整'],
                ['丝印 / 标签', 'fail', '丝印模糊，疑似翻新；需拍照留证'],
                ['包装 / 防潮', 'pass', ''],
                ['电性能 / 通断', 'pass', ''],
                ['X-ray / 开盖', 'na', '本批不做，按抽检规则'],
            ],
        ];
    }

    /** @return array<int, array<string, mixed>> */
    public static function pickTasks(): array
    {
        return [
            ['loc' => 'A-03-02-1', 'mpn' => 'TE 170364-1', 'desc' => '连接器 · 批号 L-20261120', 'qty' => '×200'],
            ['loc' => 'B-01-04-2', 'mpn' => 'GRM188R71H104KA93D', 'desc' => '0402 电容 · 批号 L-20261118', 'qty' => '×5,000'],
            ['loc' => 'C-02-01-3', 'mpn' => 'STM32F103C8T6', 'desc' => 'LQFP48 · 批号 L-20261110', 'qty' => '×120'],
            ['loc' => 'A-05-03-1', 'mpn' => 'Molex 5051-04', 'desc' => '连接器 · 批号 L-20261115', 'qty' => '×300'],
        ];
    }

    /** @return array<int, array<string, string>> */
    public static function settlements(): array
    {
        return [
            ['no' => 'ST-20261124-001', 'period' => '2026-11-17 ~ 11-23', 'gross' => '¥36,200', 'commission' => '-¥7,240', 'net' => '¥28,960', 'status' => '待复核'],
            ['no' => 'ST-20261117-004', 'period' => '2026-11-10 ~ 11-16', 'gross' => '¥18,600', 'commission' => '-¥3,720', 'net' => '¥14,880', 'status' => '已放款'],
        ];
    }

    /** @return array<int, array{value:string,label:string,trend?:string}> */
    public static function kpis(): array
    {
        return [
            ['value' => '¥128,640', 'label' => '今日 GMV', 'trend' => '+12.4%'],
            ['value' => '42', 'label' => '待审核', 'trend' => '8 超时'],
            ['value' => '8', 'label' => '异常订单', 'trend' => '需处理'],
            ['value' => '¥36,200', 'label' => '待结算', 'trend' => 'T+7'],
        ];
    }
}

<?php
declare(strict_types=1);

/**
 * 把目录卡片数据映射成 detail.php 需要的详情结构（DB 只读）。
 */
final class CatalogDetailPresenter
{
    /** @param array<string, mixed> $product */
    public function present(array $product): array
    {
        $price = (string) ($product['price'] ?? '¥0.00');
        $unit = (string) ($product['unit'] ?? '个');
        $badge = (string) ($product['badge'] ?? '在售');
        $badgeKind = (string) ($product['badgeKind'] ?? '');
        $spec = (string) ($product['spec'] ?? '');
        return [
            'title' => (string) ($product['title'] ?? '物料'),
            'crumbs' => [
                ['label' => '首页', 'href' => '/'],
                ['label' => '搜索', 'href' => '/search'],
                ['label' => (string) ($product['title'] ?? '物料')],
            ],
            'badges' => [
                [$badge, $badgeKind],
                ['寄售', 'blue'],
                [(string) ($product['location'] ?? ''), ''],
            ],
            'price' => $price,
            'unit' => $unit,
            'tax' => '含税',
            'tier' => [
                ['1–9', $price],
                ['10–99', $price],
                ['100+', $price],
            ],
            'stock' => preg_replace('/^库存\s*/u', '', (string) ($product['stock'] ?? '库存 0')) ?: '0',
            'lead' => '3 天',
            'warranty' => '30 天',
            'package' => $spec,
            'seller' => '平台演示 · 认证卖家',
            'specs' => [
                ['型号', (string) ($product['title'] ?? '')],
                ['规格', $spec],
                ['质检', $badge],
            ],
        ];
    }
}

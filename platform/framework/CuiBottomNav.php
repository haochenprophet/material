<?php
declare(strict_types=1);

final class CuiBottomNav
{
    /**
     * @return array<int, array{label:string, href:string, icon:string, key:string}>
     */
    public static function items(string $variant = 'buyer'): array
    {
        if ($variant === 'seller') {
            return [
                ['label' => '工作台', 'href' => '/seller', 'icon' => 'home', 'key' => 'seller'],
                ['label' => '提报', 'href' => '/seller/listings/new', 'icon' => 'upload', 'key' => 'seller-new'],
                ['label' => '商品', 'href' => '/seller/listings', 'icon' => 'box', 'key' => 'seller-listings'],
                ['label' => '订单', 'href' => '/admin/orders', 'icon' => 'list', 'key' => 'seller-orders'],
                ['label' => '我的', 'href' => '/ui', 'icon' => 'user', 'key' => 'seller-account'],
            ];
        }
        if ($variant === 'admin') {
            return [
                ['label' => '工作台', 'href' => '/admin/orders', 'icon' => 'home', 'key' => 'admin'],
                ['label' => '审核', 'href' => '/admin/listings', 'icon' => 'check', 'key' => 'admin-review'],
                ['label' => '信用', 'href' => '/admin/credits', 'icon' => 'card', 'key' => 'admin-credits'],
                ['label' => '订单', 'href' => '/admin/orders', 'icon' => 'list', 'key' => 'admin-orders'],
                ['label' => '我的', 'href' => '/ui', 'icon' => 'user', 'key' => 'admin-account'],
            ];
        }
        if ($variant === 'ops') {
            return [
                ['label' => '待检', 'href' => '/qc/QC-20261124-018', 'icon' => 'check', 'key' => 'qc'],
                ['label' => '记录', 'href' => '/ui', 'icon' => 'list', 'key' => 'qc-records'],
                ['label' => '批次', 'href' => '/ops/pick/P-1024', 'icon' => 'box', 'key' => 'qc-batch'],
                ['label' => '我的', 'href' => '/ui', 'icon' => 'user', 'key' => 'qc-account'],
            ];
        }
        return [
            ['label' => '首页', 'href' => '/', 'icon' => 'home', 'key' => 'home'],
            ['label' => '寻料', 'href' => '/search', 'icon' => 'search', 'key' => 'search'],
            ['label' => 'BOM', 'href' => '/bom', 'icon' => 'upload', 'key' => 'bom'],
            ['label' => '订单', 'href' => '/orders/SO-20261124-0081', 'icon' => 'list', 'key' => 'orders'],
            ['label' => '我的', 'href' => '/ui', 'icon' => 'user', 'key' => 'account'],
        ];
    }

    public static function render(string $active, string $variant = 'buyer'): string
    {
        $items = self::items($variant);
        $html = '<nav class="bottom-nav" aria-label="底部导航">';
        foreach ($items as $item) {
            $cls = Cui::isActive($active, $item['key']) ? 'active' : '';
            $html .= '<a class="' . $cls . '" href="' . Cui::url($item['href']) . '"' . (Cui::isActive($active, $item['key']) ? ' aria-current="page"' : '') . '>';
            $html .= Cui::icon($item['icon']) . '<span>' . CuiHtml::e($item['label']) . '</span></a>';
        }
        return $html . '</nav>';
    }
}

<?php
declare(strict_types=1);

final class CuiSidebar
{
    /**
     * @return array<int, array{label:string, href:string, icon:string, key:string}>
     */
    public static function items(string $variant = 'buyer'): array
    {
        if ($variant === 'seller') {
            return [
                ['label' => '工作台', 'href' => '/seller', 'icon' => 'home', 'key' => 'seller'],
                ['label' => '提报物料', 'href' => '/seller/listings/new', 'icon' => 'upload', 'key' => 'seller-new'],
                ['label' => '商品/库存', 'href' => '/seller/listings', 'icon' => 'box', 'key' => 'seller-listings'],
                ['label' => '商家认证', 'href' => '/seller/kyc', 'icon' => 'user', 'key' => 'seller-kyc'],
                ['label' => '物料图片', 'href' => '/seller/images', 'icon' => 'camera', 'key' => 'seller-images'],
                ['label' => '订单', 'href' => '/admin/orders', 'icon' => 'list', 'key' => 'seller-orders'],
                ['label' => '结算', 'href' => '/seller/settlements', 'icon' => 'card', 'key' => 'seller-settle'],
            ];
        }
        if ($variant === 'admin') {
            return [
                ['label' => '工作台', 'href' => '/admin/orders', 'icon' => 'home', 'key' => 'admin'],
                ['label' => '审核', 'href' => '/admin/listings', 'icon' => 'check', 'key' => 'admin-review'],
                ['label' => '信用/商家', 'href' => '/admin/credits', 'icon' => 'card', 'key' => 'admin-credits'],
                ['label' => '商家认证', 'href' => '/admin/kyc', 'icon' => 'user', 'key' => 'admin-kyc'],
                ['label' => '沙箱支付', 'href' => '/admin/payments', 'icon' => 'card', 'key' => 'admin-payments'],
                ['label' => '佣金规则', 'href' => '/admin/commission', 'icon' => 'settings', 'key' => 'admin-commission'],
                ['label' => 'API Token', 'href' => '/admin/api-tokens', 'icon' => 'scan', 'key' => 'admin-api-tokens'],
                ['label' => '订单', 'href' => '/admin/orders', 'icon' => 'list', 'key' => 'admin-orders'],
                ['label' => '物料', 'href' => '/ui', 'icon' => 'box', 'key' => 'admin-material'],
                ['label' => '结算', 'href' => '/seller/settlements', 'icon' => 'card', 'key' => 'admin-settle'],
            ];
        }
        if ($variant === 'ops') {
            return [
                ['label' => '待检任务', 'href' => '/qc/QC-20261124-018', 'icon' => 'check', 'key' => 'qc'],
                ['label' => '已检记录', 'href' => '/ui', 'icon' => 'list', 'key' => 'qc-records'],
                ['label' => '批次 / 库位', 'href' => '/ops/pick/P-1024', 'icon' => 'box', 'key' => 'qc-batch'],
            ];
        }
        return [
            ['label' => '首页', 'href' => '/', 'icon' => 'home', 'key' => 'home'],
            ['label' => '物料商城', 'href' => '/shops', 'icon' => 'box', 'key' => 'shops'],
            ['label' => '寻料 / 搜索', 'href' => '/search', 'icon' => 'search', 'key' => 'search'],
            ['label' => 'BOM 配单', 'href' => '/bom', 'icon' => 'upload', 'key' => 'bom'],
            ['label' => '我的项目', 'href' => '/bom', 'icon' => 'box', 'key' => 'projects'],
            ['label' => '订单', 'href' => '/orders/SO-20261124-0081', 'icon' => 'list', 'key' => 'orders'],
            ['label' => '企业中心', 'href' => '/ui', 'icon' => 'user', 'key' => 'account'],
        ];
    }

    public static function render(string $active, string $variant = 'buyer'): string
    {
        $html = '<aside class="app-sidebar" id="appSidebar" aria-label="主导航">';
        $html .= '<a class="brand" href="' . Cui::url('/') . '"><em></em>' . CuiHtml::e(APP_NAME) . '</a>';
        foreach (self::items($variant) as $item) {
            $cls = 'side-item' . (Cui::isActive($active, $item['key']) ? ' active' : '');
            $html .= '<a class="' . $cls . '" href="' . Cui::url($item['href']) . '"' . (Cui::isActive($active, $item['key']) ? ' aria-current="page"' : '') . '>';
            $html .= Cui::icon($item['icon']) . '<span>' . CuiHtml::e($item['label']) . '</span></a>';
        }
        $html .= '<div class="side-foot">';
        $html .= '<a href="' . Cui::url('/seller') . '">卖家中心</a><br>';
        $html .= '<a href="' . Cui::url('/admin/orders') . '">运营后台</a><br>';
        $html .= '<a href="' . Cui::url('/trace') . '">版本/回溯</a>';
        $html .= '</div></aside>';
        return $html;
    }
}

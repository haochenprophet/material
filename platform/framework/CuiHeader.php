<?php
declare(strict_types=1);

final class CuiHeader
{
    public static function render(string $query = ''): string
    {
        $user = Auth::user();
        $initial = $user !== null ? Cui::initials($user['name']) : '访';
        $role = $user !== null ? Rbac::label($user['role']) : '未登录';
        $avatarHref = $user !== null ? '/logout' : '/login';
        $avatarTitle = $user !== null ? ($user['name'] . ' · ' . $role . ' · 点击退出') : '登录';

        $html = '<header class="app-header">';
        $html .= '<button type="button" class="icon-btn only-mobile" data-action="toggle-sidebar" aria-label="打开菜单" aria-expanded="false">' . Cui::icon('menu') . '</button>';
        $html .= '<a class="app-logo" href="' . Cui::url('/') . '">' . CuiHtml::e(APP_NAME) . '</a>';
        $html .= '<form class="app-search" action="' . Cui::url('/search') . '" method="get" role="search">';
        $html .= Cui::icon('search');
        $html .= '<input type="search" name="q" value="' . CuiHtml::e($query) . '" placeholder="搜索型号 / 品牌 / 封装 / 参数" aria-label="搜索物料">';
        $html .= '<kbd>⌘K</kbd></form>';
        $html .= '<div class="app-head-actions">';
        $html .= '<a class="icon-btn only-desktop" href="' . Cui::url('/bom') . '" aria-label="BOM 配单">' . Cui::icon('upload') . '</a>';
        $html .= '<a class="avatar" href="' . Cui::url($avatarHref) . '" aria-label="' . CuiHtml::e($avatarTitle) . '" title="' . CuiHtml::e($avatarTitle) . '">' . CuiHtml::e($initial) . '</a>';
        $html .= '</div></header>';
        return $html;
    }
}

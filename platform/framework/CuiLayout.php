<?php
declare(strict_types=1);

/**
 * 页面外壳。四套布局：app（买家/卖家/后台）、pda、auth、print（预留）。
 * 只输出 HTML 骨架，具体内容由页面 view 调用组件类生成。
 */
final class CuiLayout
{
    private static string $layout = 'app';
    private static string $variant = 'buyer';
    private static string $active = '';

    /**
     * @param array{title?:string, active?:string, variant?:string, layout?:string, description?:string} $o
     */
    public static function start(array $o = []): void
    {
        $title = (string) ($o['title'] ?? APP_NAME);
        $active = (string) ($o['active'] ?? '');
        $variant = (string) ($o['variant'] ?? 'buyer');
        $layout = (string) ($o['layout'] ?? 'app');
        $description = (string) ($o['description'] ?? '物格云 · 分布式物料管理与交易平台');

        self::$layout = $layout;
        self::$variant = $variant;
        self::$active = $active;

        $bodyClass = 'layout-' . $layout . ' variant-' . $variant;
        if (!headers_sent()) {
            header('Content-Type: text/html; charset=UTF-8');
        }
        echo '<!doctype html><html lang="zh-CN"><head><meta charset="utf-8">';
        echo '<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">';
        echo '<meta name="theme-color" content="' . CuiHtml::e(CuiTheme::themeColor()) . '">';
        echo '<meta name="description" content="' . CuiHtml::e($description) . '">';
        echo '<meta name="csrf-token" content="' . CuiHtml::e(Cui::csrf()) . '">';
        echo '<meta name="app-version" content="' . CuiHtml::e(APP_VERSION) . '">';
        echo '<meta name="release-id" content="' . CuiHtml::e(APP_RELEASE_ID) . '">';
        echo '<title>' . CuiHtml::e($title) . ' · ' . CuiHtml::e(APP_NAME) . '</title>';
        echo '<link rel="stylesheet" href="' . CuiHtml::e(Cui::asset('css/app.css')) . '">';
        echo '</head><body class="' . CuiHtml::e($bodyClass) . '" data-release="' . CuiHtml::e(APP_RELEASE_ID) . '" data-version="' . CuiHtml::e(APP_VERSION) . '">';
        echo CuiIcon::sprite();
        echo '<a class="skip-link" href="#main">跳到主内容</a>';

        if ($layout === 'app') {
            echo '<div class="app-shell">';
            echo CuiSidebar::render($active, $variant);
            echo '<div class="app-main-wrap">';
            echo CuiHeader::render((string) ($_GET['q'] ?? ''));
            echo '<main id="main" class="app-main" tabindex="-1">';
        } elseif ($layout === 'pda') {
            echo '<main id="main" class="pda-main" tabindex="-1">';
        } else {
            echo '<main id="main" class="auth-main" tabindex="-1">';
        }
    }

    public static function end(): void
    {
        if (self::$layout === 'app') {
            echo '<footer class="app-footer">' . CuiHtml::e(APP_NAME) . ' · v' . CuiHtml::e(APP_VERSION) . ' · release ' . CuiHtml::e(APP_RELEASE_ID) . '</footer>';
            echo '</main>';
            echo CuiBottomNav::render(self::$active, self::$variant);
            echo '</div></div>';
        } else {
            echo '</main>';
        }
        echo '<div id="toast" class="toast" role="status" aria-live="polite"></div>';
        echo '<script src="' . CuiHtml::e(Cui::asset('js/app.js')) . '" defer></script>';
        echo '<script src="' . CuiHtml::e(Cui::asset('js/commerce.js')) . '" defer></script>';
        echo '</body></html>';
    }
}

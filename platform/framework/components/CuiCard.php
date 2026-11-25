<?php
declare(strict_types=1);

final class CuiCard
{
    public static function render(string $content, array $attrs = []): string
    {
        $attrs['class'] = trim('card ' . ($attrs['class'] ?? ''));
        return '<section' . CuiHtml::attrs($attrs) . '>' . $content . '</section>';
    }

    public static function header(string $title, string $hint = '', string $action = ''): string
    {
        $html = '<div class="section-head"><h3>' . CuiHtml::e($title) . '</h3>';
        $html .= $action !== '' ? $action : ($hint !== '' ? '<span>' . CuiHtml::e($hint) . '</span>' : '');
        return $html . '</div>';
    }
}

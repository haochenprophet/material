<?php
declare(strict_types=1);

final class CuiEmpty
{
    public static function render(string $title, string $text = '', string $action = '', string $icon = 'box'): string
    {
        return '<div class="empty">' . Cui::icon($icon, 'ic empty-icon') .
            '<h3>' . CuiHtml::e($title) . '</h3>' .
            ($text !== '' ? '<p>' . CuiHtml::e($text) . '</p>' : '') .
            ($action !== '' ? '<div class="empty-action">' . $action . '</div>' : '') .
            '</div>';
    }
}

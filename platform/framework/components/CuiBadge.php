<?php
declare(strict_types=1);

final class CuiBadge
{
    public static function render(string $text, string $kind = '', array $attrs = []): string
    {
        $attrs['class'] = trim('badge ' . ($kind !== '' ? 'badge-' . $kind : '') . ' ' . ($attrs['class'] ?? ''));
        return '<span' . CuiHtml::attrs($attrs) . '>' . CuiHtml::e($text) . '</span>';
    }
}

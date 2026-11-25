<?php
declare(strict_types=1);

final class CuiButton
{
    /**
     * @param array{
     *   label?: string, variant?: string, size?: string, icon?: string,
     *   href?: string, type?: string, block?: bool, attrs?: array<string,mixed>,
     *   data?: array<string,string>, disabled?: bool
     * } $o
     */
    public static function render(array $o): string
    {
        $label = (string) ($o['label'] ?? '');
        $variant = (string) ($o['variant'] ?? '');
        $size = (string) ($o['size'] ?? '');
        $block = (bool) ($o['block'] ?? false);
        $disabled = (bool) ($o['disabled'] ?? false);

        $class = CuiHtml::classes([
            'btn',
            $variant !== '' ? 'btn-' . $variant : '',
            $size !== '' ? 'btn-' . $size : '',
            $block ? 'block' : '',
            $disabled ? 'is-disabled' : '',
        ]);

        $attrs = $o['attrs'] ?? [];
        $attrs['class'] = trim(($attrs['class'] ?? '') . ' ' . $class);
        foreach (($o['data'] ?? []) as $k => $v) {
            $attrs['data-' . $k] = $v;
        }

        $inner = '';
        if (!empty($o['icon'])) {
            $inner .= Cui::icon((string) $o['icon']);
        }
        $inner .= $label !== '' ? '<span>' . CuiHtml::e($label) . '</span>' : '';

        if (!empty($o['href'])) {
            $attrs['href'] = Cui::url((string) $o['href']);
            return '<a' . CuiHtml::attrs($attrs) . '>' . $inner . '</a>';
        }

        $attrs['type'] = (string) ($o['type'] ?? 'button');
        if ($disabled) {
            $attrs['disabled'] = true;
            $attrs['aria-disabled'] = 'true';
        }
        return '<button' . CuiHtml::attrs($attrs) . '>' . $inner . '</button>';
    }
}

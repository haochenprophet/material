<?php
declare(strict_types=1);

final class CuiBreadcrumb
{
    /**
     * @param array<int, array{label:string, href?:string}> $items
     */
    public static function render(array $items): string
    {
        $parts = [];
        foreach ($items as $item) {
            $label = CuiHtml::e((string) $item['label']);
            $parts[] = !empty($item['href'])
                ? '<a href="' . Cui::url((string) $item['href']) . '">' . $label . '</a>'
                : '<span>' . $label . '</span>';
        }
        return '<nav class="crumb" aria-label="面包屑">' . implode('<span class="sep">/</span>', $parts) . '</nav>';
    }
}

<?php
declare(strict_types=1);

final class CuiChips
{
    /**
     * @param array<int, array{label:string, href?:string, active?:bool}> $items
     */
    public static function render(array $items, string $class = ''): string
    {
        $html = '<div class="chips ' . CuiHtml::e($class) . '">';
        foreach ($items as $item) {
            $active = !empty($item['active']);
            $cls = 'chip' . ($active ? ' active' : '');
            $label = CuiHtml::e((string) $item['label']);
            if (!empty($item['href'])) {
                $html .= '<a class="' . $cls . '" href="' . Cui::url((string) $item['href']) . '">' . $label . '</a>';
            } else {
                $html .= '<span class="' . $cls . '">' . $label . '</span>';
            }
        }
        return $html . '</div>';
    }
}

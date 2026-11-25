<?php
declare(strict_types=1);

final class CuiMatchCard
{
    /**
     * @param array{title:string, price:string, tags?:array<int,string>, note?:string, selected?:bool, name?:string} $m
     */
    public static function render(array $m): string
    {
        $selected = !empty($m['selected']);
        $name = (string) ($m['name'] ?? 'match');
        $html = '<label class="match' . ($selected ? ' sel' : '') . '">';
        $html .= '<div class="mh"><b>' . CuiHtml::e((string) $m['title']) . '</b><span>' . CuiHtml::e((string) $m['price']) . '</span></div>';
        if (!empty($m['tags'])) {
            $html .= '<p>';
            foreach ($m['tags'] as $tag) {
                $html .= '<span class="tag">' . CuiHtml::e((string) $tag) . '</span>';
            }
            $html .= !empty($m['note']) ? CuiHtml::e((string) $m['note']) : '';
            $html .= '</p>';
        } elseif (!empty($m['note'])) {
            $html .= '<p>' . CuiHtml::e((string) $m['note']) . '</p>';
        }
        $html .= '<input type="radio" name="' . CuiHtml::e($name) . '"' . ($selected ? ' checked' : '') . ' aria-label="选择 ' . CuiHtml::e((string) $m['title']) . '">';
        return $html . '</label>';
    }
}

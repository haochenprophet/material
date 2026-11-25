<?php
declare(strict_types=1);

final class CuiKpi
{
    public static function render(string $value, string $label, string $trend = ''): string
    {
        $html = '<div class="kpi"><b>' . CuiHtml::e($value) . '</b><span>' . CuiHtml::e($label) . '</span>';
        if ($trend !== '') {
            $html .= '<em>' . CuiHtml::e($trend) . '</em>';
        }
        return $html . '</div>';
    }
}

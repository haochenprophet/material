<?php
declare(strict_types=1);

final class CuiStat
{
    public static function render(string $value, string $label, string $class = ''): string
    {
        return '<div class="stat ' . CuiHtml::e($class) . '"><b>' . CuiHtml::e($value) . '</b><span>' . CuiHtml::e($label) . '</span></div>';
    }
}

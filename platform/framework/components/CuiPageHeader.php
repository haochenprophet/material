<?php
declare(strict_types=1);

final class CuiPageHeader
{
    /**
     * @param array{title:string, subtitle?:string, back?:string, actions?:array<int,string>, meta?:string} $o
     */
    public static function render(array $o): string
    {
        $html = '<div class="page-header">';
        if (!empty($o['back'])) {
            $html .= '<a class="btn btn-sm btn-ghost" href="' . Cui::url((string) $o['back']) . '" aria-label="返回">' . Cui::icon('back') . '</a>';
        }
        $html .= '<div class="page-heading"><h1>' . CuiHtml::e((string) $o['title']) . '</h1>';
        if (!empty($o['subtitle'])) {
            $html .= '<p>' . CuiHtml::e((string) $o['subtitle']) . '</p>';
        }
        if (!empty($o['meta'])) {
            $html .= '<div class="page-meta">' . $o['meta'] . '</div>';
        }
        $html .= '</div>';
        if (!empty($o['actions'])) {
            $html .= '<div class="page-actions">' . implode('', $o['actions']) . '</div>';
        }
        return $html . '</div>';
    }
}

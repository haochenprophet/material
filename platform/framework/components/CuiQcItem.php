<?php
declare(strict_types=1);

final class CuiQcItem
{
    public static function render(string $label, string $state = 'pass', string $note = ''): string
    {
        $map = ['pass' => 'on', 'fail' => 'bad', 'na' => 'on'];
        $html = '<div class="qc-item" data-qc>';
        $html .= '<div class="qh"><b>' . CuiHtml::e($label) . '</b><div class="segm" role="group" aria-label="' . CuiHtml::e($label) . '">';
        foreach ([['pass', '通过'], ['fail', '不通过'], ['na', 'N/A']] as [$value, $text]) {
            $cls = ($state === $value) ? ($map[$value] ?? '') : '';
            $html .= '<button type="button" class="seg ' . $cls . '" data-qc-state="' . $value . '" aria-pressed="' . ($state === $value ? 'true' : 'false') . '">' . CuiHtml::e($text) . '</button>';
        }
        $html .= '</div></div>';
        if ($note !== '') {
            $html .= '<small class="' . ($state === 'fail' ? 'text-danger' : 'text-muted') . '">' . CuiHtml::e($note) . '</small>';
        }
        return $html . '</div>';
    }
}

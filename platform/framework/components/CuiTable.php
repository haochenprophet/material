<?php
declare(strict_types=1);

final class CuiTable
{
    /**
     * @param array<int, string> $headers
     * @param array<int, array<int, string|array{html?:string, class?:string, label?:string}>> $rows
     * @param array{class?:string, empty?:string, caption?:string, id?:string} $opts
     */
    public static function render(array $headers, array $rows, array $opts = []): string
    {
        $class = trim('data-table ' . ($opts['class'] ?? ''));
        $html = '<div class="table-wrap">';
        if (!empty($opts['caption'])) {
            $html .= '<div class="table-caption">' . CuiHtml::e((string) $opts['caption']) . '</div>';
        }
        if ($rows === []) {
            return $html . CuiEmpty::render('暂无数据', (string) ($opts['empty'] ?? '调整筛选条件后重试。')) . '</div>';
        }
        $html .= '<table' . CuiHtml::attrs(['class' => $class, 'id' => $opts['id'] ?? null]) . '><thead><tr>';
        foreach ($headers as $h) {
            $html .= '<th scope="col">' . CuiHtml::e($h) . '</th>';
        }
        $html .= '</tr></thead><tbody>';
        foreach ($rows as $row) {
            $html .= '<tr>';
            foreach ($row as $i => $cell) {
                $label = $headers[$i] ?? '';
                if (is_array($cell)) {
                    $cellHtml = (string) ($cell['html'] ?? '');
                    $cellClass = (string) ($cell['class'] ?? '');
                    $cellLabel = (string) ($cell['label'] ?? $label);
                } else {
                    $cellHtml = (string) $cell;
                    $cellClass = '';
                    $cellLabel = $label;
                }
                $html .= '<td data-label="' . CuiHtml::e($cellLabel) . '"' . ($cellClass !== '' ? ' class="' . CuiHtml::e($cellClass) . '"' : '') . '>' . $cellHtml . '</td>';
            }
            $html .= '</tr>';
        }
        return $html . '</tbody></table></div>';
    }
}

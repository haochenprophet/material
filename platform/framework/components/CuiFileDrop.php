<?php
declare(strict_types=1);

final class CuiFileDrop
{
    /**
     * @param array{action?:string, name?:string, accept?:string, label?:string, hint?:string, button?:string} $o
     */
    public static function render(array $o = []): string
    {
        $id = 'file_' . bin2hex(random_bytes(3));
        $action = (string) ($o['action'] ?? '/bom/upload');
        $name = (string) ($o['name'] ?? 'file');
        $accept = (string) ($o['accept'] ?? '.csv,.txt,text/csv,text/plain');
        $label = (string) ($o['label'] ?? '拖入 BOM 文件，或点击上传');
        $hint = (string) ($o['hint'] ?? '支持 .csv / .txt，≤1MB、≤500 行；.xlsx 后续支持');
        $button = (string) ($o['button'] ?? '上传并解析');

        $html = '<form class="drop-form" method="post" action="' . Cui::url($action) . '" enctype="multipart/form-data">';
        $html .= Cui::csrfField();
        $html .= '<label class="drop" for="' . $id . '">' . Cui::icon('upload', 'ic drop-icon');
        $html .= '<b>' . CuiHtml::e($label) . '</b><small>' . CuiHtml::e($hint) . '</small>';
        $html .= '<input id="' . $id . '" class="sr-only" type="file" name="' . CuiHtml::e($name) . '" accept="' . CuiHtml::e($accept) . '" required>';
        $html .= '</label>';
        $html .= CuiButton::render(['label' => $button, 'variant' => 'primary', 'type' => 'submit', 'block' => true]);
        return $html . '</form>';
    }
}

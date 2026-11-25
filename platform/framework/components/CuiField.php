<?php
declare(strict_types=1);

final class CuiField
{
    /**
     * @param array{
     *   label:string, name:string, type?:string, value?:string, placeholder?:string,
     *   required?:bool, error?:string, help?:string, options?:array<string,string>,
     *   rows?:int, class?:string, id?:string
     * } $o
     */
    public static function render(array $o): string
    {
        $name = (string) $o['name'];
        $id = (string) ($o['id'] ?? 'f_' . preg_replace('/[^a-z0-9_]/i', '_', $name));
        $type = (string) ($o['type'] ?? 'text');
        $value = (string) ($o['value'] ?? '');
        $required = !empty($o['required']);
        $error = (string) ($o['error'] ?? '');
        $help = (string) ($o['help'] ?? '');
        $label = (string) $o['label'];

        $fieldClass = 'field ' . ($o['class'] ?? '') . ($error !== '' ? ' has-error' : '');
        $describedBy = $error !== '' ? $id . '-error' : ($help !== '' ? $id . '-help' : null);
        $common = [
            'id' => $id,
            'name' => $name,
            'class' => 'input',
            'required' => $required ?: null,
            'aria-invalid' => $error !== '' ? 'true' : null,
            'aria-describedby' => $describedBy,
            'placeholder' => $o['placeholder'] ?? null,
        ];

        $html = '<div class="' . CuiHtml::e(trim($fieldClass)) . '">';
        $html .= '<label for="' . CuiHtml::e($id) . '">' . CuiHtml::e($label) . ($required ? ' <em>*</em>' : '') . '</label>';

        if ($type === 'select') {
            $html .= '<select' . CuiHtml::attrs($common) . '>';
            foreach (($o['options'] ?? []) as $optValue => $optLabel) {
                $selected = (string) $optValue === $value;
                $html .= '<option value="' . CuiHtml::e((string) $optValue) . '"' . ($selected ? ' selected' : '') . '>' . CuiHtml::e((string) $optLabel) . '</option>';
            }
            $html .= '</select>';
        } elseif ($type === 'textarea') {
            unset($common['class']);
            $common['class'] = 'input textarea';
            $common['rows'] = (int) ($o['rows'] ?? 3);
            $html .= '<textarea' . CuiHtml::attrs($common) . '>' . CuiHtml::e($value) . '</textarea>';
        } else {
            $common['type'] = $type;
            $common['value'] = $value;
            $html .= '<input' . CuiHtml::attrs($common) . '>';
        }

        if ($error !== '') {
            $html .= '<div class="field-error" id="' . CuiHtml::e($id . '-error') . '">' . CuiHtml::e($error) . '</div>';
        } elseif ($help !== '') {
            $html .= '<div class="field-help" id="' . CuiHtml::e($id . '-help') . '">' . CuiHtml::e($help) . '</div>';
        }
        return $html . '</div>';
    }
}

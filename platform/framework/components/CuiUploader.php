<?php
declare(strict_types=1);

final class CuiUploader
{
    /**
     * @param array{name?:string, label?:string, accept?:string, capture?:bool, multiple?:bool, count?:int} $o
     */
    public static function render(array $o = []): string
    {
        $id = 'up_' . bin2hex(random_bytes(3));
        $name = (string) ($o['name'] ?? 'images[]');
        $accept = (string) ($o['accept'] ?? 'image/*');
        $capture = !empty($o['capture']);
        $multiple = !empty($o['multiple']);
        $count = (int) ($o['count'] ?? 0);

        $html = '<div class="uploader">';
        if (!empty($o['label'])) {
            $html .= '<div class="uploader-label">' . CuiHtml::e((string) $o['label']) . '</div>';
        }
        $html .= '<div class="upload-row">';
        if ($count > 0) {
            $html .= '<div class="utile filled">' . Cui::icon('check') . '已传 ' . $count . '</div>';
        }
        $html .= '<label class="utile" for="' . $id . '-camera">' . Cui::icon('camera') . '拍照</label>';
        $html .= '<label class="utile" for="' . $id . '-file">' . Cui::icon('upload') . '相册/文件</label>';
        $html .= '</div>';
        $html .= '<input id="' . $id . '-camera" class="sr-only" type="file" name="' . CuiHtml::e($name) . '" accept="' . CuiHtml::e($accept) . '"' . ($capture ? ' capture="environment"' : '') . '>';
        $html .= '<input id="' . $id . '-file" class="sr-only" type="file" name="' . CuiHtml::e($name) . '" accept="' . CuiHtml::e($accept) . '"' . ($multiple ? ' multiple' : '') . '>';
        $html .= '<div class="upload-hint">手机可直接拍照；建议 1600px 内、≤10MB。上传后自动压缩。</div>';
        return $html . '</div>';
    }
}

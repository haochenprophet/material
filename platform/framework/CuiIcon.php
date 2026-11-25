<?php
declare(strict_types=1);

/**
 * 内联 SVG 图标。不依赖外部图标库/字体，离线可用。
 */
final class CuiIcon
{
    /** @var array<string, string> */
    private const PATHS = [
        'search'   => '<circle cx="11" cy="11" r="7"/><path d="M20 20l-4-4"/>',
        'home'     => '<path d="M3 11l9-8 9 8"/><path d="M5 10v10h14V10"/>',
        'upload'   => '<path d="M12 16V4"/><path d="M7 9l5-5 5 5"/><path d="M4 16v4h16v-4"/>',
        'box'      => '<path d="M3 7l9-4 9 4-9 4-9-4z"/><path d="M3 7v10l9 4 9-4V7"/><path d="M12 11v10"/>',
        'list'     => '<path d="M8 6h13M8 12h13M8 18h13"/><path d="M3 6h.01M3 12h.01M3 18h.01"/>',
        'user'     => '<circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 4-6 8-6s8 2 8 6"/>',
        'check'    => '<path d="M4 12l5 5L20 6"/>',
        'back'     => '<path d="M15 5l-7 7 7 7"/>',
        'share'    => '<circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><path d="M8.6 10.6l6.8-4.2M8.6 13.4l6.8 4.2"/>',
        'camera'   => '<path d="M4 8h3l2-3h6l2 3h3v11H4z"/><circle cx="12" cy="13" r="3.5"/>',
        'file'     => '<path d="M6 3h8l4 4v14H6z"/><path d="M14 3v5h5"/>',
        'truck'    => '<path d="M3 6h11v11H3z"/><path d="M14 10h4l3 3v4h-7z"/><circle cx="7" cy="18" r="2"/><circle cx="18" cy="18" r="2"/>',
        'card'     => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 10h18"/>',
        'settings' => '<circle cx="12" cy="12" r="3"/><path d="M19 12a7 7 0 0 0-.1-1l2-1.6-2-3.4-2.4 1a7 7 0 0 0-1.7-1L14.5 3h-5l-.4 2.9a7 7 0 0 0-1.7 1l-2.4-1-2 3.4 2 1.6a7 7 0 0 0 0 2l-2 1.6 2 3.4 2.4-1a7 7 0 0 0 1.7 1L9.5 21h5l.4-2.9a7 7 0 0 0 1.7-1l2.4 1 2-3.4-2-1.6c.1-.3.1-.7.1-1z"/>',
        'scan'     => '<path d="M4 8V5h3M20 8V5h-3M4 16v3h3M20 16v3h-3"/><path d="M7 12h10"/>',
        'menu'     => '<path d="M4 7h16M4 12h16M4 17h16"/>',
        'close'    => '<path d="M6 6l12 12M18 6L6 18"/>',
        'chevron'  => '<path d="M9 5l7 7-7 7"/>',
        'filter'   => '<path d="M4 5h16l-6 8v6l-4 2v-8z"/>',
        'alert'    => '<path d="M12 3l9 17H3z"/><path d="M12 9v5M12 17h.01"/>',
        'plus'     => '<path d="M12 5v14M5 12h14"/>',
    ];

    public static function render(string $name, string $class = 'ic'): string
    {
        $id = preg_match('/^[a-z0-9-]+$/', $name) === 1 ? $name : 'box';
        return '<svg class="' . CuiHtml::e($class) . '" aria-hidden="true" focusable="false"><use href="#i-' . $id . '"/></svg>';
    }

    public static function sprite(): string
    {
        $symbols = '';
        foreach (self::PATHS as $name => $path) {
            $symbols .= '<symbol id="i-' . $name . '" viewBox="0 0 24 24">' . $path . '</symbol>';
        }
        return '<svg width="0" height="0" style="position:absolute" aria-hidden="true"><defs>' . $symbols . '</defs></svg>';
    }
}

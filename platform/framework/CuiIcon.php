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
        'resistor' => '<path d="M3 12h4l1.5-4 3 8 2-4H21"/><path d="M4 7v10M20 7v10"/>',
        'capacitor'=> '<path d="M4 12h6M14 12h6"/><path d="M10 7v10M14 7v10"/>',
        'inductor' => '<path d="M3 14c2-6 4-6 6 0s4 6 6 0 4-6 6 0"/><path d="M3 18h18"/>',
        'connector'=> '<path d="M4 7h16v10H4z"/><path d="M8 7V4M12 7V4M16 7V4M8 17v3M12 17v3M16 17v3"/>',
        'discrete' => '<path d="M4 12h5l3-6 4 12 2-6h2"/><circle cx="5" cy="18" r="1"/>',
        'chip'     => '<rect x="7" y="7" width="10" height="10" rx="2"/><path d="M9 3v4M12 3v4M15 3v4M9 17v4M12 17v4M15 17v4M3 9h4M3 12h4M3 15h4M17 9h4M17 12h4M17 15h4"/>',
        'cpu'      => '<rect x="5" y="5" width="14" height="14" rx="2"/><rect x="9" y="9" width="6" height="6"/><path d="M9 2v3M15 2v3M9 19v3M15 19v3M2 9h3M2 15h3M19 9h3M19 15h3"/>',
        'power'    => '<path d="M13 2L4 14h7l-1 8 9-12h-7z"/>',
        'memory'   => '<rect x="4" y="6" width="16" height="12" rx="2"/><path d="M8 6V3M12 6V3M16 6V3M8 18v3M12 18v3M16 18v3"/>',
        'programmable' => '<rect x="5" y="5" width="14" height="14" rx="2"/><path d="M9 9h6v6H9zM9 2v3M15 2v3M9 19v3M15 19v3M2 9h3M2 15h3M19 9h3M19 15h3"/>',
        'wireless' => '<path d="M5 12a7 7 0 0 1 14 0"/><path d="M8 15a4 4 0 0 1 8 0"/><circle cx="12" cy="19" r="1"/><path d="M2 9a10 10 0 0 1 20 0"/>',
        'motor'    => '<circle cx="12" cy="12" r="6"/><path d="M12 6V3M12 21v-3M6 12H3M21 12h-3M7.8 7.8L5.6 5.6M18.4 18.4l-2.2-2.2"/>',
        'mechanical' => '<circle cx="12" cy="12" r="3"/><path d="M12 2v4M12 18v4M2 12h4M18 12h4M4.9 4.9l2.8 2.8M16.3 16.3l2.8 2.8M19.1 4.9l-2.8 2.8M7.7 16.3l-2.8 2.8"/>',
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

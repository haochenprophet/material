<?php
declare(strict_types=1);

/**
 * 设计令牌。CSS 中的 :root 变量必须与这里保持一致；
 * 修改颜色/间距时先改这里，再改 assets/css/app.css。
 */
final class CuiTheme
{
    /** @return array<string, string> */
    public static function tokens(): array
    {
        return [
            'brand'      => '#1e3a8a',
            'brand-2'    => '#0d9488',
            'ink'        => '#0f172a',
            'slate'      => '#334155',
            'muted'      => '#64748b',
            'line'       => '#e2e8f0',
            'bg'         => '#f6f8fb',
            'card'       => '#ffffff',
            'ok'         => '#16a34a',
            'warn'       => '#d97706',
            'danger'     => '#b91c1c',
        ];
    }

    public static function color(string $key): string
    {
        return self::tokens()[$key] ?? '#000000';
    }

    public static function themeColor(): string
    {
        return self::color('brand');
    }
}

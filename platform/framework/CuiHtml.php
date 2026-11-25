<?php
declare(strict_types=1);

/**
 * 低层 HTML 输出工具。所有 UI class 最终都通过它拼装，避免散落 echo。
 */
final class CuiHtml
{
    public static function e(mixed $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    /**
     * @param array<string, mixed> $attrs
     */
    public static function attrs(array $attrs): string
    {
        $out = [];
        foreach ($attrs as $key => $value) {
            if ($value === null || $value === false) {
                continue;
            }
            if ($value === true) {
                $out[] = self::e($key);
                continue;
            }
            if ($key === 'class' && is_array($value)) {
                $value = implode(' ', array_filter(array_map('strval', $value)));
            }
            if ($key === 'style' && is_array($value)) {
                $pairs = [];
                foreach ($value as $k => $v) {
                    $pairs[] = $k . ':' . $v;
                }
                $value = implode(';', $pairs);
            }
            $out[] = self::e($key) . '="' . self::e($value) . '"';
        }
        return $out ? ' ' . implode(' ', $out) : '';
    }

    /**
     * @param array<string, mixed> $attrs
     */
    public static function tag(string $tag, array $attrs = [], ?string $content = null, bool $void = false): string
    {
        $html = '<' . $tag . self::attrs($attrs);
        if ($void) {
            return $html . '>';
        }
        return $html . '>' . ($content ?? '') . '</' . $tag . '>';
    }

    /**
     * @param array<int, string|null|false> $classes
     */
    public static function classes(array $classes): string
    {
        return implode(' ', array_filter(array_map(static fn ($c) => $c === null || $c === false ? '' : (string) $c, $classes)));
    }

    public static function raw(string $html): string
    {
        return $html;
    }
}

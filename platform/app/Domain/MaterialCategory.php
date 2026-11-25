<?php
declare(strict_types=1);

/**
 * 品类配置读取。配置来源：config/category-pages.php。
 */
final class MaterialCategory
{
    /** @var array<string, array<string, mixed>>|null */
    private static ?array $cache = null;

    /** @return array<string, array<string, mixed>> */
    public static function all(): array
    {
        if (self::$cache === null) {
            $data = require PLATFORM_PATH . '/config/category-pages.php';
            self::$cache = is_array($data) ? $data : [];
        }
        return self::$cache;
    }

    public static function find(string $key): ?array
    {
        $all = self::all();
        return $all[$key] ?? null;
    }

    public static function defaultKey(): string
    {
        $all = self::all();
        return (string) (array_key_first($all) ?? 'resistor');
    }

    /** @return array<int, string> */
    public static function slugs(): array
    {
        return array_keys(self::all());
    }

    /** @param array<string, mixed> $meta */
    public static function subName(array $meta, ?string $subKey): string
    {
        if ($subKey === null || $subKey === '') {
            return '全部';
        }
        return (string) (($meta['subs'][$subKey] ?? $subKey));
    }
}

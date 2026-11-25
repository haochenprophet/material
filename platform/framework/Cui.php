<?php
declare(strict_types=1);

/**
 * 全局 UI 门面：URL、资源版本、图标、CSRF、金额格式。
 * 页面/组件只调用这里，不直接拼路径。
 */
final class Cui
{
    public static function version(): string
    {
        return APP_VERSION;
    }

    public static function release(): string
    {
        return APP_RELEASE_ID;
    }

    public static function url(string $path = '/'): string
    {
        if (preg_match('#^https?://#i', $path)) {
            return $path;
        }
        $path = '/' . ltrim($path, '/');
        return APP_BASE_URL . $path;
    }

    public static function asset(string $path): string
    {
        return self::url('/assets/' . ltrim($path, '/')) . '?v=' . rawurlencode(APP_VERSION);
    }

    public static function icon(string $name, string $class = 'ic'): string
    {
        return CuiIcon::render($name, $class);
    }

    public static function csrf(): string
    {
        if (empty($_SESSION['_csrf'])) {
            $_SESSION['_csrf'] = bin2hex(random_bytes(32));
        }
        return (string) $_SESSION['_csrf'];
    }

    public static function csrfField(): string
    {
        return '<input type="hidden" name="_csrf" value="' . CuiHtml::e(self::csrf()) . '">';
    }

    public static function money(float|int|string $amount, string $unit = ''): string
    {
        $value = is_numeric($amount) ? (float) $amount : 0.0;
        $formatted = number_format($value, $value >= 100 ? 0 : 2);
        return '¥' . $formatted . ($unit !== '' ? ' <small>/' . CuiHtml::e($unit) . '</small>' : '');
    }

    public static function initials(string $name): string
    {
        $name = trim($name);
        if ($name === '') {
            return '?';
        }
        return mb_substr($name, 0, 1);
    }

    public static function isActive(string $active, string $key): bool
    {
        return $active === $key;
    }

    public static function flash(string $key, ?string $message = null): ?string
    {
        if ($message !== null) {
            $_SESSION['_flash'][$key] = $message;
            return null;
        }
        $value = $_SESSION['_flash'][$key] ?? null;
        unset($_SESSION['_flash'][$key]);
        return is_string($value) ? $value : null;
    }

    public static function json(mixed $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    }
}

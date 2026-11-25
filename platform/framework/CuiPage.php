<?php
declare(strict_types=1);

final class CuiPage
{
    /**
     * @param array<string, mixed> $data
     * @param array<string, mixed> $layout
     */
    public static function render(string $view, array $data = [], array $layout = []): void
    {
        $file = VIEW_PATH . '/pages/' . $view . '.php';
        if (!is_file($file)) {
            throw new RuntimeException('view not found: ' . $view);
        }
        CuiLayout::start($layout);
        extract($data, EXTR_SKIP);
        require $file;
        CuiLayout::end();
    }

    public static function json(mixed $data, int $status = 200): void
    {
        Cui::json($data, $status);
    }

    public static function notFound(string $message = '页面不存在'): void
    {
        if (!headers_sent()) {
            http_response_code(404);
        }
        self::render('error', [
            'code' => 404,
            'title' => $message,
            'message' => '请检查地址，或回到首页继续。',
        ], ['title' => $message, 'active' => '']);
    }
}

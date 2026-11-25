<?php
declare(strict_types=1);

/**
 * PDO 懒连接。未配置账号时不连接，避免演示环境误报。
 */
final class Database
{
    private static ?PDO $pdo = null;

    /** @return array{dsn:string,user:string,pass:string,options:array<int,mixed>} */
    private static function config(): array
    {
        return require PLATFORM_PATH . '/config/database.php';
    }

    public static function dsn(): string
    {
        return (string) self::config()['dsn'];
    }

    public static function configured(): bool
    {
        $cfg = self::config();
        return $cfg['user'] !== '';
    }

    public static function pdo(): PDO
    {
        if (self::$pdo instanceof PDO) {
            return self::$pdo;
        }
        $cfg = self::config();
        if ($cfg['user'] === '') {
            throw new RuntimeException('数据库未配置：请设置 DB_DSN/DB_USER/DB_PASS');
        }
        self::$pdo = new PDO($cfg['dsn'], $cfg['user'], $cfg['pass'], $cfg['options']);
        return self::$pdo;
    }
}

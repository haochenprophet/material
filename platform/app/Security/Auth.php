<?php
declare(strict_types=1);

/**
 * 演示账号/会话认证。生产必须改为 users 表 + Argon2id + 登录限流 + MFA。
 * 密码哈希为 demo123 的 bcrypt，仅用于本地演示。
 */
final class Auth
{
    private const DEMO_HASH = '$2y$12$FFJ/MRmox79HrNMdK37pKuZF0glhaUgCtNDNU7yGqW4hrteYh7M9y';

    /** @var array<string, array{name:string, role:string}> */
    private const USERS = [
        'buyer@demo' => ['name' => '李采购', 'role' => 'buyer'],
        'seller@demo' => ['name' => '华强北-01', 'role' => 'seller'],
        'qc@demo' => ['name' => '王质检', 'role' => 'qc'],
        'finance@demo' => ['name' => '陈财务', 'role' => 'finance'],
        'admin@demo' => ['name' => '平台运营', 'role' => 'admin'],
    ];

    public static function attempt(string $account, string $password): bool
    {
        if (self::source() === 'db') {
            if (!Database::configured()) {
                throw new RuntimeException('AUTH_SOURCE=db 但 DB_USER 未配置');
            }
            $user = (new PdoAuthRepository(Database::pdo()))->attempt($account, $password);
            if ($user === null) {
                return false;
            }
            if (session_status() === PHP_SESSION_ACTIVE && !headers_sent()) {
                session_regenerate_id(true);
            }
            $_SESSION['auth_user'] = [
                'id' => (int) $user['id'],
                'account' => $user['account'],
                'name' => $user['name'],
                'role' => $user['role'],
            ];
            return true;
        }

        $account = strtolower(trim($account));
        $user = self::USERS[$account] ?? null;
        if ($user === null || !password_verify($password, self::DEMO_HASH)) {
            return false;
        }
        if (session_status() === PHP_SESSION_ACTIVE && !headers_sent()) {
            session_regenerate_id(true);
        }
        $_SESSION['auth_user'] = [
            'id' => null,
            'account' => $account,
            'name' => $user['name'],
            'role' => $user['role'],
        ];
        return true;
    }

    /** @return array{id:int|null,account:string,name:string,role:string}|null */
    public static function user(): ?array
    {
        $user = $_SESSION['auth_user'] ?? null;
        if (!is_array($user) || !isset($user['account'], $user['name'], $user['role'])) {
            return null;
        }
        return [
            'id' => isset($user['id']) ? (int) $user['id'] : null,
            'account' => (string) $user['account'],
            'name' => (string) $user['name'],
            'role' => (string) $user['role'],
        ];
    }

    public static function id(): ?int
    {
        return self::user()['id'] ?? null;
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    public static function role(): string
    {
        return self::user()['role'] ?? 'guest';
    }

    public static function can(string $permission): bool
    {
        return self::check() && Rbac::can(self::role(), $permission);
    }

    public static function logout(): void
    {
        unset($_SESSION['auth_user']);
        if (session_status() === PHP_SESSION_ACTIVE && !headers_sent()) {
            session_regenerate_id(true);
        }
    }

    /** @return array<string, array{name:string, role:string}> */
    public static function demoAccounts(): array
    {
        return self::USERS;
    }

    public static function source(): string
    {
        return getenv('AUTH_SOURCE') === 'db' ? 'db' : 'demo';
    }
}

<?php
declare(strict_types=1);

/**
 * 角色-权限矩阵。页面/路由只问 can()，不判断 role 字符串。
 */
final class Rbac
{
    /** @var array<string, array<int, string>> */
    private const PERMISSIONS = [
        'buyer' => ['catalog.view', 'bom.upload', 'quote.create', 'order.view', 'trace.view'],
        'seller' => ['catalog.view', 'listing.create', 'listing.view', 'order.view', 'settlement.view', 'bom.upload', 'trace.view'],
        'qc' => ['catalog.view', 'qc.report', 'order.view', 'trace.view'],
        'finance' => ['order.view', 'settlement.view', 'settlement.approve', 'trace.view'],
        'admin' => ['*'],
    ];

    /** @var array<string, string> */
    private const LABELS = [
        'buyer' => '买家',
        'seller' => '卖家',
        'qc' => '质检',
        'finance' => '财务',
        'admin' => '管理员',
    ];

    public static function can(string $role, string $permission): bool
    {
        $perms = self::PERMISSIONS[$role] ?? [];
        return in_array('*', $perms, true) || in_array($permission, $perms, true);
    }

    public static function label(string $role): string
    {
        return self::LABELS[$role] ?? $role;
    }

    /** @return array<int, string> */
    public static function roles(): array
    {
        return array_keys(self::PERMISSIONS);
    }

    /** @return array<string, array<int, string>> */
    public static function matrix(): array
    {
        return self::PERMISSIONS;
    }
}

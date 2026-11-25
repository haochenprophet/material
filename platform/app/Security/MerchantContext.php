<?php
declare(strict_types=1);

/**
 * 卖家店铺上下文：DB 用户按 org_member→store 解析；demo seller 固定测试店 6。
 * admin 返回 null（后台跨店）；buyer 返回 null。
 */
final class MerchantContext
{
    public const DEMO_SELLER_STORE = 6;

    public static function storeId(): ?int
    {
        if (Auth::role() !== 'seller') {
            return null;
        }
        $userId = Auth::id();
        if ($userId !== null && $userId > 0 && Database::configured()) {
            $stmt = Database::pdo()->prepare(
                "SELECT s.id FROM org_member om
                 JOIN store s ON s.org_id = om.org_id
                 WHERE om.user_id=? AND om.status='active' AND s.status='active'
                 ORDER BY s.id LIMIT 1"
            );
            $stmt->execute([$userId]);
            $id = $stmt->fetchColumn();
            if ($id !== false) {
                return (int) $id;
            }
        }
        $u = Auth::user();
        return ($u !== null && (string) ($u['account'] ?? '') === 'seller@demo') ? self::DEMO_SELLER_STORE : null;
    }

    public static function isAdmin(): bool
    {
        return Auth::can('admin.view');
    }

    public static function canAccessStore(int $storeId): bool
    {
        return self::isAdmin() || ($storeId > 0 && self::storeId() === $storeId);
    }
}

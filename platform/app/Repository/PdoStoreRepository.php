<?php
declare(strict_types=1);

/**
 * 商家店铺/商城只读查询 + 状态管理。
 */
final class PdoStoreRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /** @return array<int, array<string, mixed>> */
    public function all(bool $onlyActive = false): array
    {
        $where = $onlyActive ? "WHERE s.status='active'" : '';
        $sql = "SELECT s.*, mp.merchant_code, mp.contact_name, mp.phone, mp.address, mp.banner,
                       mc.score, mc.grade, mc.review_count, mc.avg_rating, mc.listed_count,
                       (SELECT COUNT(*) FROM listing l WHERE l.store_id=s.id AND l.status='listed' AND l.deleted_at IS NULL) AS live_listings
                FROM store s
                LEFT JOIN merchant_profile mp ON mp.store_id=s.id
                LEFT JOIN merchant_credit mc ON mc.store_id=s.id
                {$where}
                ORDER BY COALESCE(mc.score,0) DESC, s.id ASC";
        return $this->pdo->query($sql)->fetchAll() ?: [];
    }

    public function findBySlug(string $slug): ?array
    {
        $stmt = $this->pdo->prepare(
            "SELECT s.*, mp.merchant_code, mp.contact_name, mp.phone, mp.email, mp.address, mp.banner, mp.description AS profile_description,
                    mc.score, mc.grade, mc.review_count, mc.avg_rating, mc.listed_count, mc.material_count
             FROM store s
             LEFT JOIN merchant_profile mp ON mp.store_id=s.id
             LEFT JOIN merchant_credit mc ON mc.store_id=s.id
             WHERE s.slug=:slug LIMIT 1"
        );
        $stmt->execute(['slug' => $slug]);
        $row = $stmt->fetch();
        return is_array($row) ? $row : null;
    }

    public function findById(int $storeId): ?array
    {
        $stmt = $this->pdo->prepare(
            "SELECT s.*, mp.merchant_code, mp.contact_name, mp.phone, mp.email, mp.address, mp.banner, mp.description AS profile_description,
                    mc.score, mc.grade, mc.review_count, mc.avg_rating, mc.listed_count, mc.material_count
             FROM store s
             LEFT JOIN merchant_profile mp ON mp.store_id=s.id
             LEFT JOIN merchant_credit mc ON mc.store_id=s.id
             WHERE s.id=:id LIMIT 1"
        );
        $stmt->execute(['id' => $storeId]);
        $row = $stmt->fetch();
        return is_array($row) ? $row : null;
    }

    /** @return array<int, array<string, mixed>> */
    public function listings(int $storeId): array
    {
        $sql = "SELECT l.id, l.title, l.price, l.quantity, l.grade, l.material_id,
                       m.part_number, m.name AS material_name,
                       mc.score AS material_score, mc.grade AS material_grade, mc.avg_rating,
                       (SELECT ma.path FROM material_attachment ma WHERE ma.material_id=m.id AND ma.type='image' AND ma.deleted_at IS NULL ORDER BY ma.is_primary DESC, ma.sort ASC, ma.id ASC LIMIT 1) AS image_path
                FROM listing l
                JOIN material m ON m.id=l.material_id
                LEFT JOIN material_credit mc ON mc.material_id=m.id
                WHERE l.store_id=? AND l.status='listed' AND l.deleted_at IS NULL AND m.deleted_at IS NULL
                ORDER BY l.id DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$storeId]);
        return $stmt->fetchAll() ?: [];
    }

    public function setStatus(int $storeId, string $status): bool
    {
        if (!in_array($status, ['active', 'paused', 'closed'], true)) {
            throw new InvalidArgumentException('非法商家状态');
        }
        $stmt = $this->pdo->prepare('UPDATE store SET status=? WHERE id=?');
        $stmt->execute([$status, $storeId]);
        return $stmt->rowCount() > 0;
    }
}

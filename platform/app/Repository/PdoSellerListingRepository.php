<?php
declare(strict_types=1);

/**
 * 卖家视角的提报列表（只读，包含所有状态）。
 */
final class PdoSellerListingRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /** @return array<int, array<string, mixed>> */
    public function all(?int $storeId = null, int $limit = 50, int $offset = 0): array
    {
        $limit = max(1, min(200, $limit));
        $offset = max(0, $offset);
        if ($storeId !== null) {
            return $this->queryByStore($storeId, $limit, $offset);
        }
        return $this->query($limit, $offset);
    }

    /** @return array<int, array<string, mixed>> */
    private function queryByStore(int $storeId, int $limit, int $offset): array
    {
        $sql = "SELECT l.id, l.title, l.status, l.price, l.quantity, l.created_at, m.part_number,
                       (SELECT lr.reason FROM listing_review lr
                        WHERE lr.listing_id = l.id ORDER BY lr.id DESC LIMIT 1) AS review_reason
                FROM listing l
                JOIN material m ON m.id = l.material_id
                WHERE l.store_id=? AND l.deleted_at IS NULL
                ORDER BY l.id DESC
                LIMIT {$limit} OFFSET {$offset}";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$storeId]);
        return $stmt->fetchAll() ?: [];
    }

    /** @return array<int, array<string, mixed>> */
    private function query(int $limit, int $offset): array
    {
        $sql = "SELECT l.id, l.title, l.status, l.price, l.quantity, l.created_at, m.part_number,
                       (SELECT lr.reason FROM listing_review lr
                        WHERE lr.listing_id = l.id ORDER BY lr.id DESC LIMIT 1) AS review_reason
                FROM listing l
                JOIN material m ON m.id = l.material_id
                WHERE l.deleted_at IS NULL
                ORDER BY l.id DESC
                LIMIT {$limit} OFFSET {$offset}";
        return $this->pdo->query($sql)->fetchAll() ?: [];
    }

    public static function label(string $status): string
    {
        return match ($status) {
            'pending_review' => '待审核',
            'listed' => '已上架',
            'rejected' => '已驳回',
            'offline' => '已下架',
            default => $status,
        };
    }

    public static function kind(string $status): string
    {
        return match ($status) {
            'listed' => 'ok',
            'pending_review' => 'warn',
            'rejected' => 'danger',
            default => '',
        };
    }
}

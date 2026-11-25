<?php
declare(strict_types=1);

/**
 * 真实 listing + material 的只读仓储。
 * 只读、参数绑定；不修改数据库。
 */
final class PdoListingRepository implements MaterialRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function all(): array
    {
        $sql = 'SELECT l.id, l.material_id, l.store_id, l.title, l.grade, l.price, l.quantity, l.lead_time_days,
                       m.part_number, m.description,
                       w.name AS warehouse_name, loc.code AS location_code
                FROM listing l
                JOIN material m ON m.id = l.material_id
                LEFT JOIN warehouse w ON w.id = l.warehouse_id
                LEFT JOIN location loc ON loc.id = l.location_id
                WHERE l.status = \'listed\' AND l.deleted_at IS NULL AND m.deleted_at IS NULL
                ORDER BY l.id DESC';
        return array_map([$this, 'map'], $this->pdo->query($sql)->fetchAll() ?: []);
    }

    public function find(string $id): ?array
    {
        $id = ltrim($id, 'pP');
        if ($id === '' || !ctype_digit($id)) {
            return null;
        }
        $sql = 'SELECT l.id, l.material_id, l.store_id, l.title, l.grade, l.price, l.quantity, l.lead_time_days,
                       m.part_number, m.description,
                       w.name AS warehouse_name, loc.code AS location_code
                FROM listing l
                JOIN material m ON m.id = l.material_id
                LEFT JOIN warehouse w ON w.id = l.warehouse_id
                LEFT JOIN location loc ON loc.id = l.location_id
                WHERE l.id = :id AND l.status = \'listed\' AND l.deleted_at IS NULL AND m.deleted_at IS NULL
                LIMIT 1';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => (int) $id]);
        $row = $stmt->fetch();
        return is_array($row) ? $this->map($row) : null;
    }

    /** @param array<string, mixed> $row */
    private function map(array $row): array
    {
        $grade = (string) ($row['grade'] ?? 'NA');
        $price = (float) ($row['price'] ?? 0);
        $qty = (float) ($row['quantity'] ?? 0);
        $location = trim((string) ($row['warehouse_name'] ?? '') . ' ' . (string) ($row['location_code'] ?? ''));
        return [
            'id' => 'p' . (string) $row['id'],
            'listing_id' => (int) $row['id'],
            'material_id' => (int) ($row['material_id'] ?? 0),
            'store_id' => (int) ($row['store_id'] ?? 0),
            'title' => (string) ($row['title'] ?? $row['part_number'] ?? '物料'),
            'spec' => trim((string) ($row['part_number'] ?? '') . ' · ' . $grade . ' 级'),
            'price' => '¥' . number_format($price, 2),
            'unit' => '个',
            'stock' => '库存 ' . number_format($qty, 0),
            'location' => $location,
            'badge' => $grade === 'NA' ? '待质检' : $grade . ' 级质检',
            'badgeKind' => $grade === 'A' ? 'ok' : ($grade === 'NA' ? 'warn' : ''),
            'thumb' => (string) ($row['part_number'] ?? '物料'),
            'thumbClass' => '',
        ];
    }
}

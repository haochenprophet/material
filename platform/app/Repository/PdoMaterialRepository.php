<?php
declare(strict_types=1);

/**
 * PDO 物料仓储（开发表 materials）。
 * 生产 45 表 schema 需另做映射；这里证明 Repository 可替换、SQL 可测试。
 */
final class PdoMaterialRepository implements MaterialRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function all(): array
    {
        $stmt = $this->pdo->query('SELECT id, title, spec, price, unit, stock, location, badge, badge_kind, thumb, thumb_class FROM materials ORDER BY id');
        $rows = $stmt->fetchAll();
        return array_map([$this, 'map'], is_array($rows) ? $rows : []);
    }

    public function find(string $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT id, title, spec, price, unit, stock, location, badge, badge_kind, thumb, thumb_class FROM materials WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return is_array($row) ? $this->map($row) : null;
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private function map(array $row): array
    {
        return [
            'id' => (string) $row['id'],
            'title' => (string) $row['title'],
            'spec' => (string) $row['spec'],
            'price' => (string) $row['price'],
            'unit' => (string) ($row['unit'] ?? '个'),
            'stock' => (string) $row['stock'],
            'location' => (string) ($row['location'] ?? ''),
            'badge' => (string) ($row['badge'] ?? ''),
            'badgeKind' => (string) ($row['badge_kind'] ?? ''),
            'thumb' => (string) ($row['thumb'] ?? $row['title']),
            'thumbClass' => (string) ($row['thumb_class'] ?? ''),
        ];
    }
}

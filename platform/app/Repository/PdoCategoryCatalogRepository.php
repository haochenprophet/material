<?php
declare(strict_types=1);

/**
 * 品类页报价查询：只读，按 material.category_key 聚合 listing/store。
 */
final class PdoCategoryCatalogRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /** @param array{sub?:string,merchant?:string,min_price?:float,max_price?:float,lead?:int,sort?:string} $filters
     *  @return array<int, array<string, mixed>> */
    public function offers(string $categoryKey, array $filters = [], int $limit = 24): array
    {
        $where = ["l.status='listed'", 'l.deleted_at IS NULL', 'm.deleted_at IS NULL', 'm.category_key=?'];
        $args = [$categoryKey];
        if (($filters['sub'] ?? '') !== '') {
            $where[] = 'm.subcategory_key=?';
            $args[] = $filters['sub'];
        }
        if (($filters['merchant'] ?? '') !== '') {
            $where[] = 's.slug=?';
            $args[] = $filters['merchant'];
        }
        if (isset($filters['min_price']) && (float) $filters['min_price'] > 0) {
            $where[] = 'l.price>=?';
            $args[] = (float) $filters['min_price'];
        }
        if (isset($filters['max_price']) && (float) $filters['max_price'] > 0) {
            $where[] = 'l.price<=?';
            $args[] = (float) $filters['max_price'];
        }
        if (isset($filters['lead']) && (int) $filters['lead'] > 0) {
            $where[] = 'l.lead_time_days<=?';
            $args[] = (int) $filters['lead'];
        }
        $order = match ($filters['sort'] ?? 'recommended') {
            'price_asc' => 'l.price ASC, l.id DESC',
            'price_desc' => 'l.price DESC, l.id DESC',
            'lead_asc' => 'l.lead_time_days ASC, l.price ASC',
            'newest' => 'l.id DESC',
            default => 's.rating DESC, l.price ASC, l.id DESC',
        };
        $limit = max(1, min(60, $limit));
        $sql = "SELECT l.id listing_id,l.title,l.price,l.currency,l.quantity,l.moq,l.lead_time_days,l.grade,l.item_condition,l.warranty_days,
                       m.id material_id,m.part_number,m.name,m.description,m.attributes_summary,m.subcategory_key,
                       s.id store_id,s.name store_name,s.slug store_slug,s.rating store_rating,s.rating_count store_rating_count
                FROM listing l JOIN material m ON m.id=l.material_id JOIN store s ON s.id=l.store_id
                WHERE " . implode(' AND ', $where) . " ORDER BY {$order} LIMIT {$limit}";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($args);
        $rows = $stmt->fetchAll() ?: [];
        foreach ($rows as &$row) {
            $attrs = json_decode((string) ($row['attributes_summary'] ?? ''), true);
            $row['attrs'] = is_array($attrs) ? $attrs : [];
        }
        return $rows;
    }

    /** @return array{offers:int,merchants:int,min_price:?float,avg_lead:float,subs:array<string,int>} */
    public function stats(string $categoryKey): array
    {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) offers, COUNT(DISTINCT l.store_id) merchants, MIN(l.price) min_price, AVG(l.lead_time_days) avg_lead
            FROM listing l JOIN material m ON m.id=l.material_id JOIN store s ON s.id=l.store_id
            WHERE l.status='listed' AND l.deleted_at IS NULL AND m.deleted_at IS NULL AND m.category_key=?");
        $stmt->execute([$categoryKey]);
        $row = $stmt->fetch() ?: [];
        $subs = [];
        $subStmt = $this->pdo->prepare("SELECT m.subcategory_key, COUNT(*) c FROM listing l JOIN material m ON m.id=l.material_id
            WHERE l.status='listed' AND l.deleted_at IS NULL AND m.deleted_at IS NULL AND m.category_key=? GROUP BY m.subcategory_key");
        $subStmt->execute([$categoryKey]);
        foreach ($subStmt->fetchAll() ?: [] as $s) {
            if (is_string($s['subcategory_key'] ?? null) && $s['subcategory_key'] !== '') {
                $subs[$s['subcategory_key']] = (int) $s['c'];
            }
        }
        return [
            'offers' => (int) ($row['offers'] ?? 0),
            'merchants' => (int) ($row['merchants'] ?? 0),
            'min_price' => $row['min_price'] !== null ? (float) $row['min_price'] : null,
            'avg_lead' => round((float) ($row['avg_lead'] ?? 0), 1),
            'subs' => $subs,
        ];
    }

    /** @return array<int, array{slug:string,name:string,count:int}> */
    public function merchants(string $categoryKey): array
    {
        $stmt = $this->pdo->prepare("SELECT s.slug,s.name,COUNT(*) c,MAX(s.rating) rating FROM listing l
            JOIN material m ON m.id=l.material_id JOIN store s ON s.id=l.store_id
            WHERE l.status='listed' AND l.deleted_at IS NULL AND m.deleted_at IS NULL AND m.category_key=?
            GROUP BY s.id,s.slug,s.name ORDER BY c DESC");
        $stmt->execute([$categoryKey]);
        return array_map(static fn (array $r): array => ['slug' => (string) $r['slug'], 'name' => (string) $r['name'], 'count' => (int) $r['c']], $stmt->fetchAll() ?: []);
    }
}

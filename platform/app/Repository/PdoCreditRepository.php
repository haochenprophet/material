<?php
declare(strict_types=1);

/**
 * 信用聚合：物料分 / 商家分 + credit_event 审计。
 * 仅 MySQL 8（ON DUPLICATE KEY）；单元测试用 CreditScoring。
 */
final class PdoCreditRepository
{
    private CreditScoring $scoring;

    public function __construct(private readonly PDO $pdo)
    {
        $this->scoring = new CreditScoring();
    }

    public function material(int $materialId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM material_credit WHERE material_id=?');
        $stmt->execute([$materialId]);
        $row = $stmt->fetch();
        return is_array($row) ? $row : null;
    }

    public function merchant(int $storeId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM merchant_credit WHERE store_id=?');
        $stmt->execute([$storeId]);
        $row = $stmt->fetch();
        return is_array($row) ? $row : null;
    }

    /** @return array<int, array<string, mixed>> */
    public function history(string $subjectType, int $subjectId, int $limit = 20): array
    {
        if (!in_array($subjectType, ['material', 'merchant'], true)) {
            throw new InvalidArgumentException('非法信用主体');
        }
        $limit = max(1, min(100, $limit));
        $stmt = $this->pdo->prepare("SELECT * FROM credit_event WHERE subject_type=? AND subject_id=? ORDER BY id DESC LIMIT {$limit}");
        $stmt->execute([$subjectType, $subjectId]);
        return $stmt->fetchAll() ?: [];
    }

    /** @return array<int, array<string, mixed>> */
    public function merchants(): array
    {
        $sql = 'SELECT s.*, mc.score, mc.grade, mc.review_count, mc.avg_rating, mc.material_count, mc.listed_count,
                       mp.merchant_code, mp.contact_name, mp.phone, mp.address
                FROM store s
                LEFT JOIN merchant_credit mc ON mc.store_id = s.id
                LEFT JOIN merchant_profile mp ON mp.store_id = s.id
                ORDER BY mc.score DESC, s.id ASC';
        return $this->pdo->query($sql)->fetchAll() ?: [];
    }

    /** @return array<int, array<string, mixed>> */
    public function materials(): array
    {
        $sql = 'SELECT mc.*, m.part_number, m.name
                FROM material_credit mc
                JOIN material m ON m.id = mc.material_id
                ORDER BY mc.score DESC, mc.material_id ASC';
        return $this->pdo->query($sql)->fetchAll() ?: [];
    }

    /** @return array<int, array<string, mixed>> */
    public function recentEvents(int $limit = 20): array
    {
        $limit = max(1, min(100, $limit));
        return $this->pdo->query("SELECT * FROM credit_event ORDER BY id DESC LIMIT {$limit}")->fetchAll() ?: [];
    }

    public function recalculateMaterial(int $materialId): array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM material_review WHERE material_id=? AND status='visible'");
        $stmt->execute([$materialId]);
        $reviews = $stmt->fetchAll() ?: [];
        $grade = (string) ($this->pdo->query('SELECT COALESCE(MAX(grade),\'NA\') FROM listing WHERE material_id=' . $materialId)->fetchColumn() ?: 'NA');
        $credit = $this->scoring->material($reviews, ['grade' => $grade]);
        $before = $this->material($materialId);
        $this->upsertMaterial($materialId, $credit);
        $this->event('material', $materialId, 'review_recalc', (int) ($before['score'] ?? 0), (int) $credit['score'], ['reviews' => count($reviews)]);
        return $credit;
    }

    public function recalculateMerchant(int $storeId): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT DISTINCT mc.*, l.grade AS material_grade
             FROM listing l
             JOIN material_credit mc ON mc.material_id = l.material_id
             JOIN material m ON m.id = l.material_id
             WHERE l.store_id=? AND l.deleted_at IS NULL"
        );
        $stmt->execute([$storeId]);
        $rows = $stmt->fetchAll() ?: [];
        $materials = array_map(static fn ($r) => ['score' => (int) $r['score'], 'review_count' => (int) $r['review_count'], 'verified_count' => (int) $r['verified_count']], $rows);
        $store = $this->pdo->prepare('SELECT * FROM store WHERE id=?');
        $store->execute([$storeId]);
        $s = $store->fetch() ?: [];
        $listed = (int) ($this->pdo->query('SELECT COUNT(*) FROM listing WHERE store_id=' . $storeId . " AND status='listed' AND deleted_at IS NULL")->fetchColumn() ?: 0);
        $materialsCount = (int) ($this->pdo->query('SELECT COUNT(DISTINCT material_id) FROM listing WHERE store_id=' . $storeId . ' AND deleted_at IS NULL')->fetchColumn() ?: 0);
        $reviews = (int) ($this->pdo->query('SELECT COUNT(*) FROM material_review WHERE store_id=' . $storeId . " AND status='visible'")->fetchColumn() ?: 0);
        $verified = (int) ($this->pdo->query('SELECT COUNT(*) FROM material_review WHERE store_id=' . $storeId . " AND status='visible' AND verified_purchase=1")->fetchColumn() ?: 0);
        $avg = (float) ($this->pdo->query('SELECT COALESCE(AVG(rating),0) FROM material_review WHERE store_id=' . $storeId . " AND status='visible'")->fetchColumn() ?: 0);
        $positive = (int) ($this->pdo->query('SELECT COUNT(*) FROM material_review WHERE store_id=' . $storeId . " AND status='visible' AND rating>=4")->fetchColumn() ?: 0);
        $verifiedOrg = (int) ($this->pdo->query('SELECT COUNT(*) FROM org WHERE id=' . (int) ($s['org_id'] ?? 0) . " AND status='active'")->fetchColumn() ?: 0) === 1;
        $credit = $this->scoring->merchant($materials, [
            'store_rating' => (float) ($s['rating'] ?? 0),
            'listed_count' => $listed,
            'verified_merchant' => $verifiedOrg ? 1 : 0,
            'material_count' => $materialsCount,
            'review_count' => $reviews,
            'verified_count' => $verified,
            'avg_rating' => $avg,
            'positive_rate' => $reviews > 0 ? $positive / $reviews * 100 : 0,
        ]);
        $before = $this->merchant($storeId);
        $this->upsertMerchant($storeId, $credit);
        $this->event('merchant', $storeId, 'review_recalc', (int) ($before['score'] ?? 0), (int) $credit['score'], ['reviews' => $reviews, 'materials' => $materialsCount]);
        return $credit;
    }

    public function recalculateAll(): array
    {
        $materials = $this->pdo->query('SELECT id FROM material WHERE deleted_at IS NULL')->fetchAll() ?: [];
        foreach ($materials as $m) {
            $this->recalculateMaterial((int) $m['id']);
        }
        $stores = $this->pdo->query("SELECT id FROM store WHERE status IN ('active','paused')")->fetchAll() ?: [];
        $out = [];
        foreach ($stores as $s) {
            $out[(int) $s['id']] = $this->recalculateMerchant((int) $s['id']);
        }
        return $out;
    }

    private function upsertMaterial(int $id, array $c): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO material_credit (material_id, review_count, avg_rating, score, grade, quality_avg, price_avg, delivery_avg, service_avg, positive_rate, verified_count)
             VALUES (?,?,?,?,?,?,?,?,?,?,?)
             ON DUPLICATE KEY UPDATE review_count=VALUES(review_count), avg_rating=VALUES(avg_rating), score=VALUES(score), grade=VALUES(grade),
             quality_avg=VALUES(quality_avg), price_avg=VALUES(price_avg), delivery_avg=VALUES(delivery_avg), service_avg=VALUES(service_avg),
             positive_rate=VALUES(positive_rate), verified_count=VALUES(verified_count)'
        );
        $stmt->execute([$id, $c['review_count'], $c['avg_rating'], $c['score'], $c['grade'], $c['quality_avg'], $c['price_avg'], $c['delivery_avg'], $c['service_avg'], $c['positive_rate'], $c['verified_count']]);
    }

    private function upsertMerchant(int $id, array $c): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO merchant_credit (store_id, review_count, avg_rating, score, grade, material_count, listed_count, verified_count, positive_rate)
             VALUES (?,?,?,?,?,?,?,?,?)
             ON DUPLICATE KEY UPDATE review_count=VALUES(review_count), avg_rating=VALUES(avg_rating), score=VALUES(score), grade=VALUES(grade),
             material_count=VALUES(material_count), listed_count=VALUES(listed_count), verified_count=VALUES(verified_count), positive_rate=VALUES(positive_rate)'
        );
        $stmt->execute([$id, $c['review_count'], $c['avg_rating'], $c['score'], $c['grade'], $c['material_count'], $c['listed_count'], $c['verified_count'], $c['positive_rate']]);
    }

    private function event(string $type, int $id, string $event, int $before, int $after, array $meta): void
    {
        $stmt = $this->pdo->prepare('INSERT INTO credit_event (subject_type, subject_id, event_type, delta, score_before, score_after, metadata) VALUES (?,?,?,?,?,?,?)');
        $stmt->execute([$type, $id, $event, $after - $before, $before, $after, json_encode($meta, JSON_UNESCAPED_UNICODE)]);
    }
}

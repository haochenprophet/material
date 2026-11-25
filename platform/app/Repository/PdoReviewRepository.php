<?php
declare(strict_types=1);

/**
 * 物料点评仓储。只做点评读取/写入/隐藏；信用聚合由 PdoCreditRepository 负责。
 */
final class PdoReviewRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /** @return array<int, array<string, mixed>> */
    public function forMaterial(int $materialId, int $limit = 20): array
    {
        $limit = max(1, min(100, $limit));
        $stmt = $this->pdo->prepare(
            "SELECT r.*, (SELECT COUNT(*) FROM review_reply rr WHERE rr.review_id = r.id) AS reply_count
             FROM material_review r
             WHERE r.material_id = :mid AND r.status = 'visible'
             ORDER BY r.created_at DESC, r.id DESC LIMIT {$limit}"
        );
        $stmt->execute(['mid' => $materialId]);
        return $stmt->fetchAll() ?: [];
    }

    /** @return array<int, array<string, mixed>> */
    public function forStore(int $storeId, int $limit = 20): array
    {
        $limit = max(1, min(100, $limit));
        $stmt = $this->pdo->prepare(
            "SELECT r.*, m.part_number, m.name AS material_name
             FROM material_review r
             JOIN material m ON m.id = r.material_id
             WHERE r.store_id = :sid AND r.status = 'visible'
             ORDER BY r.created_at DESC, r.id DESC LIMIT {$limit}"
        );
        $stmt->execute(['sid' => $storeId]);
        return $stmt->fetchAll() ?: [];
    }

    /** @return array<int, array<string, mixed>> */
    public function pending(int $limit = 50): array
    {
        $limit = max(1, min(100, $limit));
        $sql = "SELECT r.*, m.part_number, m.name AS material_name, s.name AS store_name
                FROM material_review r
                JOIN material m ON m.id = r.material_id
                LEFT JOIN store s ON s.id = r.store_id
                WHERE r.status = 'pending'
                ORDER BY r.id DESC LIMIT {$limit}";
        return $this->pdo->query($sql)->fetchAll() ?: [];
    }

    public function reviewerKey(?int $userId, string $account): string
    {
        return ReviewPolicy::key($userId, $account);
    }

    public function hasReviewed(string $reviewerKey, int $listingId): bool
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM material_review WHERE reviewer_key=? AND listing_id=?');
        $stmt->execute([$reviewerKey, $listingId]);
        return (int) $stmt->fetchColumn() > 0;
    }

    public function recentCount(string $reviewerKey, int $minutes = 60): int
    {
        $minutes = max(1, min(1440, $minutes));
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM material_review WHERE reviewer_key=? AND created_at >= DATE_SUB(NOW(), INTERVAL {$minutes} MINUTE)");
        $stmt->execute([$reviewerKey]);
        return (int) $stmt->fetchColumn();
    }

    /** @param array<string, mixed> $data */
    public function create(array $data): int
    {
        $materialId = (int) ($data['material_id'] ?? 0);
        $rating = (int) ($data['rating'] ?? 0);
        $content = trim((string) ($data['content'] ?? ''));
        if ($materialId <= 0 || $rating < 1 || $rating > 5 || mb_strlen($content) < 2) {
            throw new InvalidArgumentException('点评参数不合法');
        }
        $uuid = $this->uuid();
        $stmt = $this->pdo->prepare(
            'INSERT INTO material_review
             (uuid, material_id, listing_id, store_id, user_id, reviewer_key, author_name, author_role,
              rating, quality, price, delivery, service, content, images, verified_purchase, status)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,\'visible\')'
        );
        $stmt->execute([
            $uuid,
            $materialId,
            $data['listing_id'] ?? null,
            $data['store_id'] ?? null,
            $data['user_id'] ?? null,
            (string) ($data['reviewer_key'] ?? ''),
            mb_substr((string) ($data['author_name'] ?? '匿名买家'), 0, 100),
            mb_substr((string) ($data['author_role'] ?? 'buyer'), 0, 32),
            $rating,
            $this->dim((int) ($data['quality'] ?? $rating)),
            $this->dim((int) ($data['price'] ?? $rating)),
            $this->dim((int) ($data['delivery'] ?? $rating)),
            $this->dim((int) ($data['service'] ?? $rating)),
            mb_substr($content, 0, 2000),
            isset($data['images']) ? json_encode($data['images'], JSON_UNESCAPED_UNICODE) : null,
            (int) ($data['verified_purchase'] ?? 0),
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    /** @return array<int, array<string, mixed>> */
    public function replies(int $reviewId): array
    {
        $stmt = $this->pdo->prepare('SELECT id,author_name,content,created_at FROM review_reply WHERE review_id=? ORDER BY id ASC');
        $stmt->execute([$reviewId]);
        return $stmt->fetchAll() ?: [];
    }

    /** @return array<int, int> rating => count, keys 1..5 */
    public function distribution(int $materialId): array
    {
        return $this->dist("material_id = ?", [$materialId]);
    }

    /** @return array<int, int> */
    public function distributionForStore(int $storeId): array
    {
        return $this->dist('store_id = ?', [$storeId]);
    }

    /** @param array<int, mixed> $params */
    private function dist(string $where, array $params): array
    {
        $stmt = $this->pdo->prepare("SELECT rating,COUNT(*) AS c FROM material_review WHERE {$where} AND status='visible' GROUP BY rating");
        $stmt->execute($params);
        $out = [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0];
        foreach ($stmt->fetchAll() ?: [] as $row) {
            $out[(int) $row['rating']] = (int) $row['c'];
        }
        return $out;
    }

    public function reply(int $reviewId, array $user, string $content): int
    {
        $content = trim($content);
        if (mb_strlen($content) < 2 || mb_strlen($content) > 500) {
            throw new InvalidArgumentException('回复内容需 2–500 字');
        }
        $stmt = $this->pdo->prepare("SELECT store_id FROM material_review WHERE id=? AND status='visible'");
        $stmt->execute([$reviewId]);
        $review = $stmt->fetch();
        if (!is_array($review)) {
            throw new InvalidArgumentException('点评不存在');
        }
        $ins = $this->pdo->prepare('INSERT INTO review_reply (review_id,store_id,author_name,content) VALUES (?,?,?,?)');
        $ins->execute([$reviewId, $review['store_id'] ?? null, mb_substr((string) ($user['name'] ?? '商家'), 0, 100), $content]);
        return (int) $this->pdo->lastInsertId();
    }

    public function hide(int $reviewId, ?int $actorId = null, string $note = ''): bool
    {
        return $this->moderate($reviewId, 'hidden', 'review.hide', $actorId, $note);
    }

    public function approve(int $reviewId, ?int $actorId = null, string $note = ''): bool
    {
        return $this->moderate($reviewId, 'visible', 'review.approve', $actorId, $note);
    }

    public function reject(int $reviewId, ?int $actorId = null, string $note = ''): bool
    {
        return $this->moderate($reviewId, 'hidden', 'review.reject', $actorId, $note);
    }

    private function moderate(int $reviewId, string $to, string $action, ?int $actorId, string $note): bool
    {
        $stmt = $this->pdo->prepare('SELECT status FROM material_review WHERE id=?');
        $stmt->execute([$reviewId]);
        $before = $stmt->fetchColumn();
        if (!is_string($before) || $before === $to) {
            return false;
        }
        $allowed = ($before === 'pending' && in_array($to, ['visible', 'hidden'], true))
            || ($before === 'visible' && $to === 'hidden');
        if (!$allowed) {
            return false;
        }
        $upd = $this->pdo->prepare('UPDATE material_review SET status=?, moderation_note=?, moderated_by=?, moderated_at=CURRENT_TIMESTAMP WHERE id=?');
        $upd->execute([$to, $note !== '' ? mb_substr($note, 0, 500) : null, $actorId, $reviewId]);
        try {
            $audit = $this->pdo->prepare('INSERT INTO audit_log (actor_id,actor_type,action,object_type,object_id,before_json,after_json,request_id) VALUES (?,?,?,?,?,?,?,?)');
            $audit->execute([$actorId, $actorId === null ? 'system' : 'user', $action, 'material_review', (string) $reviewId, json_encode(['status' => $before], JSON_UNESCAPED_UNICODE), json_encode(['status' => $to, 'note' => $note], JSON_UNESCAPED_UNICODE), defined('APP_REQUEST_ID') ? APP_REQUEST_ID : null]);
        } catch (Throwable) {
            // audit_log 在部分开发库可能不存在；状态更新仍生效，避免阻塞审核。
        }
        return $upd->rowCount() > 0;
    }

    public function appeal(int $reviewId, string $reason): int
    {
        $reason = trim($reason);
        if (mb_strlen($reason) < 5 || mb_strlen($reason) > 1000) {
            throw new InvalidArgumentException('申诉理由需 5–1000 字');
        }
        $stmt = $this->pdo->prepare("SELECT store_id FROM material_review WHERE id=? AND status='hidden'");
        $stmt->execute([$reviewId]);
        $storeId = $stmt->fetchColumn();
        if ($storeId === false) {
            throw new InvalidArgumentException('仅可对已隐藏点评申诉');
        }
        $ins = $this->pdo->prepare("INSERT INTO review_appeal (review_id,store_id,reason,status) VALUES (?,?,?,'pending')");
        $ins->execute([$reviewId, $storeId ?: null, $reason]);
        return (int) $this->pdo->lastInsertId();
    }

    /** @return array<int, array<string, mixed>> */
    public function appeals(): array
    {
        $sql = "SELECT a.*, r.content, r.author_name, s.name AS store_name
                FROM review_appeal a
                JOIN material_review r ON r.id=a.review_id
                LEFT JOIN store s ON s.id=a.store_id
                ORDER BY a.status='pending' DESC, a.id DESC";
        return $this->pdo->query($sql)->fetchAll() ?: [];
    }

    public function resolveAppeal(int $appealId, string $status, ?int $actorId = null): bool
    {
        if (!in_array($status, ['accepted', 'rejected'], true)) {
            throw new InvalidArgumentException('非法申诉状态');
        }
        $stmt = $this->pdo->prepare("UPDATE review_appeal SET status=?,resolver_id=?,resolved_at=CURRENT_TIMESTAMP WHERE id=? AND status='pending'");
        $stmt->execute([$status, $actorId, $appealId]);
        return $stmt->rowCount() > 0;
    }

    private function dim(int $v): int
    {
        return max(1, min(5, $v));
    }

    private function uuid(): string
    {
        $b = random_bytes(16);
        $b[6] = chr((ord($b[6]) & 0x0f) | 0x40);
        $b[8] = chr((ord($b[8]) & 0x3f) | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($b), 4));
    }
}

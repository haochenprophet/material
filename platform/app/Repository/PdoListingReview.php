<?php
declare(strict_types=1);

/**
 * 运营审核：pending_review → listed / rejected。
 * 每次审核写 listing_review + audit_log；只更新状态，不删除数据。
 */
final class PdoListingReview
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /** @return array<int, array<string, mixed>> */
    public function pending(): array
    {
        $sql = 'SELECT l.id, l.title, l.price, l.quantity, l.created_at,
                       m.part_number
                FROM listing l
                JOIN material m ON m.id = l.material_id
                WHERE l.status = \'pending_review\' AND l.deleted_at IS NULL
                ORDER BY l.id DESC';
        return $this->pdo->query($sql)->fetchAll() ?: [];
    }

    public function approve(int $id, ?int $reviewerId = null, string $reason = ''): bool
    {
        return $this->decide($id, 'listed', 'approve', $reviewerId, $reason);
    }

    public function reject(int $id, ?int $reviewerId = null, string $reason = ''): bool
    {
        return $this->decide($id, 'rejected', 'reject', $reviewerId, $reason);
    }

    private function decide(int $id, string $status, string $action, ?int $reviewerId, string $reason): bool
    {
        $ownTransaction = !$this->pdo->inTransaction();
        if ($ownTransaction) {
            $this->pdo->beginTransaction();
        }
        try {
            $stmt = $this->pdo->prepare(
                "UPDATE listing SET status=:status, published_at=CASE WHEN :status2='listed' THEN CURRENT_TIMESTAMP ELSE published_at END, updated_at=CURRENT_TIMESTAMP
                 WHERE id=:id AND status='pending_review'"
            );
            $stmt->execute(['status' => $status, 'status2' => $status, 'id' => $id]);
            if ($stmt->rowCount() === 0) {
                if ($ownTransaction) {
                    $this->pdo->rollBack();
                }
                return false;
            }
            $review = $this->pdo->prepare('INSERT INTO listing_review (listing_id, reviewer_id, action, reason) VALUES (:lid,:rid,:action,:reason)');
            $review->execute([
                'lid' => $id,
                'rid' => $reviewerId,
                'action' => $action,
                'reason' => $reason !== '' ? mb_substr($reason, 0, 500) : null,
            ]);
            $audit = $this->pdo->prepare('INSERT INTO audit_log (actor_id, actor_type, action, object_type, object_id, after_json, request_id) VALUES (:actor,:type,:action,\'listing\',:oid,:after,:req)');
            $audit->execute([
                'actor' => $reviewerId,
                'type' => $reviewerId === null ? 'system' : 'user',
                'action' => 'listing.' . $action,
                'oid' => (string) $id,
                'after' => json_encode(['status' => $status], JSON_UNESCAPED_UNICODE),
                'req' => defined('APP_REQUEST_ID') ? APP_REQUEST_ID : null,
            ]);
            if ($ownTransaction) {
                $this->pdo->commit();
            }
            return true;
        } catch (Throwable $e) {
            if ($ownTransaction && $this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }
}

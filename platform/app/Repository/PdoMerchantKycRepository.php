<?php
declare(strict_types=1);

/**
 * 商家 KYC / 入驻资料。
 */
final class PdoMerchantKycRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function find(int $storeId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM merchant_kyc WHERE store_id=? LIMIT 1');
        $stmt->execute([$storeId]);
        $row = $stmt->fetch();
        return is_array($row) ? $row : null;
    }

    /** @return array<int, array<string, mixed>> */
    public function all(): array
    {
        $sql = 'SELECT k.*, s.name AS store_name, s.slug, s.status AS store_status
                FROM merchant_kyc k JOIN store s ON s.id=k.store_id
                ORDER BY k.status=\'pending\' DESC, k.id ASC';
        return $this->pdo->query($sql)->fetchAll() ?: [];
    }

    /** @param array<string, mixed> $data */
    public function submit(int $storeId, array $data): int
    {
        $legal = trim((string) ($data['legal_name'] ?? ''));
        $license = trim((string) ($data['license_no'] ?? ''));
        $agreement = trim((string) ($data['agreement_version'] ?? ''));
        $deposit = (float) ($data['deposit_amount'] ?? 0);
        if ($storeId <= 0 || $legal === '' || $license === '' || $agreement === '') {
            throw new InvalidArgumentException('企业名称/证件号/协议版本必填');
        }
        if ($deposit < 0 || $deposit > 100000000) {
            throw new InvalidArgumentException('保证金金额不合法');
        }
        $existing = $this->find($storeId);
        $signed = (string) ($data['agreement_signed_at'] ?? date('Y-m-d H:i:s'));
        $docs = isset($data['documents']) ? json_encode($data['documents'], JSON_UNESCAPED_UNICODE) : null;
        if ($existing === null) {
            $stmt = $this->pdo->prepare("INSERT INTO merchant_kyc (store_id,legal_name,license_no,tax_no,legal_person,contact_name,contact_phone,documents,deposit_amount,agreement_version,agreement_signed_at,status) VALUES (?,?,?,?,?,?,?,?,?,?,?,'pending')");
            $stmt->execute([$storeId, $legal, $license, $data['tax_no'] ?? null, $data['legal_person'] ?? null, $data['contact_name'] ?? null, $data['contact_phone'] ?? null, $docs, $deposit, $agreement, $signed]);
            return (int) $this->pdo->lastInsertId();
        }
        $stmt = $this->pdo->prepare("UPDATE merchant_kyc SET legal_name=?,license_no=?,tax_no=?,legal_person=?,contact_name=?,contact_phone=?,documents=?,deposit_amount=?,agreement_version=?,agreement_signed_at=?,status='pending',review_note=NULL,reviewed_by=NULL,reviewed_at=NULL WHERE store_id=?");
        $stmt->execute([$legal, $license, $data['tax_no'] ?? null, $data['legal_person'] ?? null, $data['contact_name'] ?? null, $data['contact_phone'] ?? null, $docs, $deposit, $agreement, $signed, $storeId]);
        return (int) $existing['id'];
    }

    public function review(int $id, string $status, ?int $actorId = null, string $note = ''): bool
    {
        if (!in_array($status, ['verified', 'rejected', 'expired'], true)) {
            throw new InvalidArgumentException('非法认证状态');
        }
        $stmt = $this->pdo->prepare("UPDATE merchant_kyc SET status=?,review_note=?,reviewed_by=?,reviewed_at=CURRENT_TIMESTAMP WHERE id=? AND status IN ('pending','rejected')");
        $stmt->execute([$status, $note !== '' ? mb_substr($note, 0, 500) : null, $actorId, $id]);
        return $stmt->rowCount() > 0;
    }
}

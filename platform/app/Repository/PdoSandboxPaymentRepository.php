<?php
declare(strict_types=1);

/**
 * 沙箱支付/分账/退款/对账（不接真实资金）。
 */
final class PdoSandboxPaymentRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function create(string $orderNo, float $amount, string $idempotencyKey, string $channel = 'wechat', int $sellerAccountId = 6): array
    {
        $orderNo = trim($orderNo);
        $idempotencyKey = trim($idempotencyKey);
        if ($orderNo === '' || $idempotencyKey === '' || $amount <= 0) {
            throw new InvalidArgumentException('订单号/金额/幂等键不合法');
        }
        if (!in_array($channel, ['wechat', 'alipay', 'unionpay', 'bank', 'offline'], true)) {
            throw new InvalidArgumentException('支付渠道不合法');
        }
        $existing = $this->findByKey($idempotencyKey);
        if ($existing !== null) {
            return $existing + ['idempotent' => true];
        }
        $no = 'SBX-' . date('YmdHis') . '-' . strtoupper(bin2hex(random_bytes(2)));
        $stmt = $this->pdo->prepare("INSERT INTO sandbox_payment (payment_no,order_no,channel,amount,status,idempotency_key) VALUES (?,?,?,?,'created',?)");
        $stmt->execute([$no, $orderNo, $channel, round($amount, 2), $idempotencyKey]);
        return ['id' => (int) $this->pdo->lastInsertId(), 'payment_no' => $no, 'order_no' => $orderNo, 'amount' => round($amount, 2), 'status' => 'created', 'channel' => $channel, 'seller_account_id' => $sellerAccountId, 'idempotent' => false];
    }

    public function capture(string $paymentNo): array
    {
        $p = $this->find($paymentNo);
        if ($p === null) {
            throw new InvalidArgumentException('支付单不存在');
        }
        if ($p['status'] === 'succeeded') {
            return $p;
        }
        if ($p['status'] !== 'created') {
            throw new RuntimeException('当前状态不可捕获');
        }
        $own = !$this->pdo->inTransaction();
        if ($own) { $this->pdo->beginTransaction(); }
        try {
            $ext = 'SBX-EXT-' . strtoupper(bin2hex(random_bytes(4)));
            $this->pdo->prepare("UPDATE sandbox_payment SET status='succeeded',external_id=?,paid_at=CURRENT_TIMESTAMP WHERE payment_no=?")->execute([$ext, $paymentNo]);
            $amount = (float) $p['amount'];
            $platform = round($amount * 0.10, 2);
            $seller = round($amount - $platform, 2);
            $split = $this->pdo->prepare("INSERT INTO sandbox_payment_split (payment_id,account_type,account_id,amount,status) VALUES (?,?,?,?,'succeeded')");
            $split->execute([(int) $p['id'], 'seller', 6, $seller]);
            $split->execute([(int) $p['id'], 'platform_commission', null, $platform]);
            if ($own) { $this->pdo->commit(); }
            return $this->find($paymentNo) ?? $p;
        } catch (Throwable $e) {
            if ($own && $this->pdo->inTransaction()) { $this->pdo->rollBack(); }
            throw $e;
        }
    }

    public function refund(string $paymentNo, float $amount, string $reason = ''): array
    {
        $p = $this->find($paymentNo);
        if ($p === null || !in_array($p['status'], ['succeeded', 'partial_refunded'], true)) {
            throw new RuntimeException('该支付单不可退款');
        }
        $refunded = $this->refundedAmount((int) $p['id']);
        if ($amount <= 0 || $refunded + $amount > (float) $p['amount'] + 0.001) {
            throw new InvalidArgumentException('退款金额超出可退金额');
        }
        $own = !$this->pdo->inTransaction();
        if ($own) { $this->pdo->beginTransaction(); }
        try {
            $no = 'RF-' . date('YmdHis') . '-' . strtoupper(bin2hex(random_bytes(2)));
            $this->pdo->prepare("INSERT INTO sandbox_refund (refund_no,payment_id,amount,reason,status,refunded_at) VALUES (?,?,?,?,'succeeded',CURRENT_TIMESTAMP)")->execute([$no, (int) $p['id'], round($amount, 2), $reason !== '' ? mb_substr($reason, 0, 255) : null]);
            $total = $refunded + $amount;
            $status = $total >= (float) $p['amount'] - 0.001 ? 'refunded' : 'partial_refunded';
            $this->pdo->prepare('UPDATE sandbox_payment SET status=? WHERE id=?')->execute([$status, (int) $p['id']]);
            if ($own) { $this->pdo->commit(); }
            return ['refund_no' => $no, 'amount' => round($amount, 2), 'status' => $status];
        } catch (Throwable $e) {
            if ($own && $this->pdo->inTransaction()) { $this->pdo->rollBack(); }
            throw $e;
        }
    }

    public function find(string $paymentNo): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM sandbox_payment WHERE payment_no=? LIMIT 1');
        $stmt->execute([$paymentNo]);
        $row = $stmt->fetch();
        return is_array($row) ? $row : null;
    }

    public function findByKey(string $key): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM sandbox_payment WHERE idempotency_key=? LIMIT 1');
        $stmt->execute([$key]);
        $row = $stmt->fetch();
        return is_array($row) ? $row : null;
    }

    /** @return array<int, array<string, mixed>> */
    public function payments(int $limit = 50): array
    {
        $limit = max(1, min(200, $limit));
        return $this->pdo->query("SELECT * FROM sandbox_payment ORDER BY id DESC LIMIT {$limit}")->fetchAll() ?: [];
    }

    /** @return array<int, array<string, mixed>> */
    public function splits(int $paymentId): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM sandbox_payment_split WHERE payment_id=? ORDER BY id ASC');
        $stmt->execute([$paymentId]);
        return $stmt->fetchAll() ?: [];
    }

    /** @return array<int, array<string, mixed>> */
    public function refunds(int $paymentId): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM sandbox_refund WHERE payment_id=? ORDER BY id ASC');
        $stmt->execute([$paymentId]);
        return $stmt->fetchAll() ?: [];
    }

    public function refundedAmount(int $paymentId): float
    {
        $stmt = $this->pdo->prepare("SELECT COALESCE(SUM(amount),0) FROM sandbox_refund WHERE payment_id=? AND status='succeeded'");
        $stmt->execute([$paymentId]);
        return (float) $stmt->fetchColumn();
    }

    /** @return array{payments:float,splits:float,diff:float} */
    public function reconcile(): array
    {
        $payments = (float) $this->pdo->query("SELECT COALESCE(SUM(amount),0) FROM sandbox_payment WHERE status IN ('succeeded','partial_refunded','refunded')")->fetchColumn();
        $splits = (float) $this->pdo->query("SELECT COALESCE(SUM(amount),0) FROM sandbox_payment_split WHERE status='succeeded'")->fetchColumn();
        return ['payments' => round($payments, 2), 'splits' => round($splits, 2), 'diff' => round($payments - $splits, 2)];
    }
}

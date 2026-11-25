<?php
declare(strict_types=1);

/**
 * 佣金规则：版本化创建/停用/按类目+卖家类型+日期解析。
 */
final class PdoCommissionRuleRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /** @return array<int, array<string, mixed>> */
    public function rules(): array
    {
        return $this->pdo->query('SELECT * FROM commission_rule ORDER BY rule_key ASC, version DESC, id DESC')->fetchAll() ?: [];
    }

    public function resolve(?int $categoryId, string $sellerType, string $date): array
    {
        if (!in_array($sellerType, ['individual', 'company', 'school', 'any'], true)) {
            throw new InvalidArgumentException('非法卖家类型');
        }
        $sql = "SELECT * FROM commission_rule
                WHERE status='active' AND effective_from<=? AND (effective_to IS NULL OR effective_to>=?)
                  AND (category_id IS NULL OR category_id=?)
                  AND seller_type IN (?, 'any')
                ORDER BY effective_from DESC, version DESC, id DESC LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$date, $date, $categoryId, $sellerType]);
        $row = $stmt->fetch();
        if (is_array($row)) {
            return $row + ['fallback' => false];
        }
        return ['id' => null, 'rule_key' => 'fallback', 'version' => 0, 'rate' => 0.20, 'min_fee' => 0, 'max_fee' => null, 'fallback' => true];
    }

    public function create(array $data): int
    {
        $key = trim((string) ($data['rule_key'] ?? ''));
        $rate = (float) ($data['rate'] ?? -1);
        if ($key === '' || !preg_match('/^[a-z0-9_\-]{2,64}$/i', $key)) {
            throw new InvalidArgumentException('规则 key 不合法');
        }
        if ($rate < 0 || $rate > 1) {
            throw new InvalidArgumentException('佣金比例必须在 0–1');
        }
        $stmt = $this->pdo->prepare('SELECT COALESCE(MAX(version),0)+1 FROM commission_rule WHERE rule_key=?');
        $stmt->execute([$key]);
        $version = (int) $stmt->fetchColumn();
        $ins = $this->pdo->prepare('INSERT INTO commission_rule (rule_key,version,category_id,seller_type,rate,min_fee,max_fee,effective_from,effective_to,status,note) VALUES (?,?,?,?,?,?,?,?,?,?,?)');
        $ins->execute([
            $key, $version,
            $data['category_id'] ?? null,
            (string) ($data['seller_type'] ?? 'any'),
            $rate,
            (float) ($data['min_fee'] ?? 0),
            isset($data['max_fee']) && $data['max_fee'] !== '' ? (float) $data['max_fee'] : null,
            (string) ($data['effective_from'] ?? date('Y-m-d')),
            isset($data['effective_to']) && $data['effective_to'] !== '' ? (string) $data['effective_to'] : null,
            'active',
            (string) ($data['note'] ?? ''),
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    public function disable(int $id): bool
    {
        $stmt = $this->pdo->prepare("UPDATE commission_rule SET status='disabled' WHERE id=? AND status='active'");
        $stmt->execute([$id]);
        return $stmt->rowCount() > 0;
    }
}

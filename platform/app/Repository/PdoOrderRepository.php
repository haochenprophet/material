<?php
declare(strict_types=1);

/**
 * PDO 订单仓储（开发表 orders/order_lines）。
 * 保存使用 upsert，不删除历史行；同一订单重复保存会更新状态/金额与行。
 */
final class PdoOrderRepository implements OrderRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function save(array $order): void
    {
        $id = (string) ($order['id'] ?? '');
        if ($id === '') {
            throw new InvalidArgumentException('order.id 必填');
        }
        $stmt = $this->pdo->prepare(
            'INSERT INTO orders (id,status,goods,subtotal,total,created_at,updated_at)
             VALUES (:id,:status,:goods,:subtotal,:total,:created_at,:updated_at)
             ON CONFLICT(id) DO UPDATE SET status=excluded.status,goods=excluded.goods,subtotal=excluded.subtotal,total=excluded.total,updated_at=excluded.updated_at'
        );
        $pricing = is_array($order['pricing'] ?? null) ? $order['pricing'] : [];
        $stmt->execute([
            'id' => $id,
            'status' => (string) ($order['status'] ?? 'pending_payment'),
            'goods' => (float) ($pricing['goods'] ?? 0),
            'subtotal' => (float) ($pricing['subtotal'] ?? 0),
            'total' => (float) ($pricing['total'] ?? ($pricing['subtotal'] ?? 0)),
            'created_at' => (string) ($order['created_at'] ?? date('c')),
            'updated_at' => (string) ($order['updated_at'] ?? date('c')),
        ]);

        $lineStmt = $this->pdo->prepare(
            'INSERT INTO order_lines (order_id,line_no,mpn,qty,unit)
             VALUES (:order_id,:line_no,:mpn,:qty,:unit)
             ON CONFLICT(order_id,line_no) DO UPDATE SET mpn=excluded.mpn,qty=excluded.qty,unit=excluded.unit'
        );
        foreach (array_values($order['lines'] ?? []) as $i => $line) {
            $lineStmt->execute([
                'order_id' => $id,
                'line_no' => $i + 1,
                'mpn' => (string) ($line['mpn'] ?? ''),
                'qty' => (float) ($line['qty'] ?? 0),
                'unit' => (float) ($line['unit'] ?? 0),
            ]);
        }
    }

    public function find(string $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT id,status,goods,subtotal,total,created_at,updated_at FROM orders WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        if (!is_array($row)) {
            return null;
        }
        $lines = $this->pdo->prepare('SELECT line_no,mpn,qty,unit FROM order_lines WHERE order_id = :id ORDER BY line_no');
        $lines->execute(['id' => $id]);
        return [
            'id' => (string) $row['id'],
            'status' => (string) $row['status'],
            'lines' => array_map(static fn (array $l): array => [
                'mpn' => (string) $l['mpn'],
                'qty' => (float) $l['qty'],
                'unit' => (float) $l['unit'],
            ], $lines->fetchAll() ?: []),
            'pricing' => [
                'goods' => (float) $row['goods'],
                'subtotal' => (float) $row['subtotal'],
                'total' => (float) $row['total'],
            ],
            'created_at' => (string) $row['created_at'],
            'updated_at' => (string) $row['updated_at'],
        ];
    }

    public function all(): array
    {
        $stmt = $this->pdo->query('SELECT id FROM orders ORDER BY created_at DESC, id');
        $out = [];
        foreach ($stmt->fetchAll() ?: [] as $row) {
            $found = $this->find((string) $row['id']);
            if ($found !== null) {
                $out[] = $found;
            }
        }
        return $out;
    }
}

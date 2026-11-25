<?php
declare(strict_types=1);

/**
 * 卖家提报写入：material + listing（pending_review）。
 * 支持事务 + 可选幂等键（scope=listing.create）。
 */
final class PdoListingWriter
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /** @param array<string, mixed> $data */
    public function create(array $data, ?string $idempotencyKey = null): int
    {
        $part = trim((string) ($data['part_number'] ?? ''));
        $title = trim((string) ($data['title'] ?? ''));
        $qty = (float) ($data['qty'] ?? 0);
        $price = (float) ($data['price'] ?? 0);
        if ($part === '' || $title === '') {
            throw new InvalidArgumentException('part_number/title 必填');
        }
        if ($qty <= 0 || $price <= 0) {
            throw new InvalidArgumentException('数量/价格必须 >0');
        }
        $category = (int) ($data['category_id'] ?? 11);
        $store = (int) ($data['store_id'] ?? 1);
        $warehouse = (int) ($data['warehouse_id'] ?? 1);
        $location = (int) ($data['location_id'] ?? 1);

        $own = !$this->pdo->inTransaction();
        if ($own) {
            $this->pdo->beginTransaction();
        }
        try {
            if ($idempotencyKey !== null && $idempotencyKey !== '') {
                $existing = $this->existing($idempotencyKey);
                if ($existing !== null) {
                    if ($own) {
                        $this->pdo->commit();
                    }
                    return $existing;
                }
            }

            $uuid = $this->uuid();
            $material = $this->pdo->prepare(
                'INSERT INTO material (uuid, code, part_number, name, description, category_id, uom_id, brand_id, manufacturer_id, package_id, lifecycle_status)
                 VALUES (:uuid,:code,:part,:name,:desc,:cat,1,1,1,1,\'active\')'
            );
            $material->execute([
                'uuid' => $uuid,
                'code' => 'SEL-' . strtoupper(bin2hex(random_bytes(4))),
                'part' => $part,
                'name' => mb_substr($title, 0, 200),
                'desc' => (string) ($data['spec'] ?? ''),
                'cat' => $category,
            ]);
            $materialId = (int) $this->pdo->lastInsertId();

            $listing = $this->pdo->prepare(
                'INSERT INTO listing (uuid, store_id, material_id, category_id, title, description, item_condition, grade, qc_status, status, price_mode, price, currency, quantity, moq, uom_id, warehouse_id, location_id, lead_time_days, warranty_days)
                 VALUES (:uuid,:store,:material,:cat,:title,:desc,\'surplus\',\'NA\',\'pending\',\'pending_review\',\'fixed\',:price,\'CNY\',:qty,1,1,:warehouse,:location,3,30)'
            );
            $listing->execute([
                'uuid' => $this->uuid(),
                'store' => $store,
                'material' => $materialId,
                'cat' => $category,
                'title' => $title,
                'desc' => (string) ($data['spec'] ?? ''),
                'price' => $price,
                'qty' => $qty,
                'warehouse' => $warehouse,
                'location' => $location,
            ]);
            $listingId = (int) $this->pdo->lastInsertId();

            $review = $this->pdo->prepare('INSERT INTO listing_review (listing_id, reviewer_id, action, reason) VALUES (:lid,NULL,\'submit\',NULL)');
            $review->execute(['lid' => $listingId]);
            $audit = $this->pdo->prepare('INSERT INTO audit_log (actor_id, actor_type, action, object_type, object_id, after_json, request_id) VALUES (NULL,\'system\',\'listing.submit\',\'listing\',:oid,:after,:req)');
            $audit->execute([
                'oid' => (string) $listingId,
                'after' => json_encode(['status' => 'pending_review'], JSON_UNESCAPED_UNICODE),
                'req' => defined('APP_REQUEST_ID') ? APP_REQUEST_ID : null,
            ]);

            if ($idempotencyKey !== null && $idempotencyKey !== '') {
                $stmt = $this->pdo->prepare('INSERT INTO idempotency_key (`key`, `scope`, response, expires_at) VALUES (:k,:s,:r,:e)');
                $stmt->execute([
                    'k' => mb_substr($idempotencyKey, 0, 128),
                    's' => 'listing.create',
                    'r' => json_encode(['listing_id' => $listingId], JSON_UNESCAPED_UNICODE),
                    'e' => date('Y-m-d H:i:s', time() + 604800),
                ]);
            }
            if ($own) {
                $this->pdo->commit();
            }
            return $listingId;
        } catch (Throwable $e) {
            if ($own && $this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            if ($idempotencyKey !== null && $idempotencyKey !== '') {
                $existing = $this->existing($idempotencyKey);
                if ($existing !== null) {
                    return $existing;
                }
            }
            throw $e;
        }
    }

    private function existing(string $key): ?int
    {
        $stmt = $this->pdo->prepare("SELECT response FROM idempotency_key WHERE `key`=:k AND scope='listing.create' LIMIT 1");
        $stmt->execute(['k' => mb_substr($key, 0, 128)]);
        $raw = $stmt->fetchColumn();
        if (!is_string($raw)) {
            return null;
        }
        $data = json_decode($raw, true);
        return is_array($data) && isset($data['listing_id']) ? (int) $data['listing_id'] : null;
    }

    private function uuid(): string
    {
        $bytes = random_bytes(16);
        $bytes[6] = chr((ord($bytes[6]) & 0x0f) | 0x40);
        $bytes[8] = chr((ord($bytes[8]) & 0x3f) | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($bytes), 4));
    }
}

<?php
declare(strict_types=1);

/**
 * 物料图片仓储：主图、排序、软删。不删除文件。
 */
final class PdoMaterialImageRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /** @return array<int, array<string, mixed>> */
    public function forMaterial(int $materialId): array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM material_attachment WHERE material_id=? AND type='image' AND deleted_at IS NULL ORDER BY is_primary DESC, sort ASC, id ASC");
        $stmt->execute([$materialId]);
        return $stmt->fetchAll() ?: [];
    }

    /** @return array<int, array<string, mixed>> */
    public function materials(?int $storeId = null): array
    {
        if ($storeId === null) {
            return $this->pdo->query('SELECT id,code,part_number,name FROM material WHERE deleted_at IS NULL ORDER BY id ASC')->fetchAll() ?: [];
        }
        $stmt = $this->pdo->prepare('SELECT DISTINCT m.id,m.code,m.part_number,m.name FROM material m JOIN listing l ON l.material_id=m.id WHERE l.store_id=? AND l.deleted_at IS NULL AND m.deleted_at IS NULL ORDER BY m.id ASC');
        $stmt->execute([$storeId]);
        return $stmt->fetchAll() ?: [];
    }

    public function storeIdForMaterial(int $materialId): ?int
    {
        $stmt = $this->pdo->prepare('SELECT store_id FROM listing WHERE material_id=? AND deleted_at IS NULL ORDER BY id ASC LIMIT 1');
        $stmt->execute([$materialId]);
        $id = $stmt->fetchColumn();
        return $id === false ? null : (int) $id;
    }

    public function add(int $materialId, string $path, string $mime, int $size, string $sha256, bool $primary): int
    {
        $own = !$this->pdo->inTransaction();
        if ($own) {
            $this->pdo->beginTransaction();
        }
        try {
            if ($primary) {
                $this->pdo->prepare("UPDATE material_attachment SET is_primary=0 WHERE material_id=? AND type='image' AND deleted_at IS NULL")->execute([$materialId]);
            }
            $sort = (int) $this->pdo->query('SELECT COALESCE(MAX(sort),-1)+1 FROM material_attachment WHERE material_id=' . $materialId . " AND type='image'")->fetchColumn();
            $stmt = $this->pdo->prepare("INSERT INTO material_attachment (material_id,type,path,mime,size,sha256,is_primary,sort) VALUES (?,'image',?,?,?,?,?,?)");
            $stmt->execute([$materialId, $path, $mime, $size, $sha256, $primary ? 1 : 0, $sort]);
            $id = (int) $this->pdo->lastInsertId();
            if ($own) {
                $this->pdo->commit();
            }
            return $id;
        } catch (Throwable $e) {
            if ($own && $this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    public function setPrimary(int $materialId, int $imageId): bool
    {
        $stmt = $this->pdo->prepare('SELECT id FROM material_attachment WHERE id=? AND material_id=?');
        $stmt->execute([$imageId, $materialId]);
        if ($stmt->fetchColumn() === false) {
            return false;
        }
        $this->pdo->prepare("UPDATE material_attachment SET is_primary=0 WHERE material_id=? AND type='image' AND deleted_at IS NULL")->execute([$materialId]);
        $this->pdo->prepare('UPDATE material_attachment SET is_primary=1 WHERE id=?')->execute([$imageId]);
        return true;
    }

    public function softDelete(int $materialId, int $imageId): bool
    {
        $stmt = $this->pdo->prepare("SELECT is_primary FROM material_attachment WHERE id=? AND material_id=? AND deleted_at IS NULL");
        $stmt->execute([$imageId, $materialId]);
        $wasPrimary = $stmt->fetchColumn();
        if ($wasPrimary === false) {
            return false;
        }
        $this->pdo->prepare('UPDATE material_attachment SET deleted_at=CURRENT_TIMESTAMP, is_primary=0 WHERE id=?')->execute([$imageId]);
        if ((int) $wasPrimary === 1) {
            $next = $this->pdo->prepare("SELECT id FROM material_attachment WHERE material_id=? AND type='image' AND deleted_at IS NULL ORDER BY sort ASC,id ASC LIMIT 1");
            $next->execute([$materialId]);
            $nextId = (int) $next->fetchColumn();
            if ($nextId > 0) {
                $this->pdo->prepare('UPDATE material_attachment SET is_primary=1 WHERE id=?')->execute([$nextId]);
            }
        }
        return true;
    }
}

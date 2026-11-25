<?php
declare(strict_types=1);

/**
 * 物料图片 / 溯源 / 评论树。
 * 图片复用 material_attachment；不重复建图片表。
 */
final class PdoCommerceRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /** @return array<int, array<string, mixed>> */
    public function images(int $materialId): array
    {
        $stmt = $this->pdo->prepare("SELECT id, path, mime, is_primary, sort FROM material_attachment WHERE material_id=? AND type='image' AND deleted_at IS NULL ORDER BY is_primary DESC, sort ASC, id ASC");
        $stmt->execute([$materialId]);
        return $stmt->fetchAll() ?: [];
    }

    public function provenance(int $materialId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM material_provenance WHERE material_id=? LIMIT 1');
        $stmt->execute([$materialId]);
        $row = $stmt->fetch();
        return is_array($row) ? $row : null;
    }

    /** @return array<int, array<string, mixed>> */
    public function commentsTree(int $materialId): array
    {
        $stmt = $this->pdo->prepare("SELECT id, parent_id, author_name, author_role, content, depth, path, created_at FROM material_comment WHERE material_id=? AND status='visible' ORDER BY created_at ASC, id ASC");
        $stmt->execute([$materialId]);
        $rows = $stmt->fetchAll() ?: [];
        $byId = [];
        foreach ($rows as $r) {
            $r['replies'] = [];
            $byId[(int) $r['id']] = $r;
        }
        $roots = [];
        foreach ($byId as $id => &$r) {
            $parent = (int) ($r['parent_id'] ?? 0);
            if ($parent > 0 && isset($byId[$parent])) {
                $byId[$parent]['replies'][] = &$r;
            } else {
                $roots[] = &$r;
            }
        }
        unset($r);
        return $roots;
    }

    public function addComment(int $materialId, ?int $parentId, array $user, string $content): int
    {
        $content = trim($content);
        if ($materialId <= 0 || mb_strlen($content) < 2 || mb_strlen($content) > 1000) {
            throw new InvalidArgumentException('评论内容需 2–1000 字');
        }
        $parent = null;
        if ($parentId !== null && $parentId > 0) {
            $stmt = $this->pdo->prepare("SELECT id,material_id,depth,path FROM material_comment WHERE id=? AND material_id=? AND status='visible'");
            $stmt->execute([$parentId, $materialId]);
            $parent = $stmt->fetch();
            if (!is_array($parent)) {
                throw new InvalidArgumentException('父评论不存在');
            }
            if ((int) $parent['depth'] >= 3) {
                throw new InvalidArgumentException('评论最多 3 层');
            }
        }
        $depth = $parent === null ? 0 : (int) $parent['depth'] + 1;
        $uuid = $this->uuid();
        $stmt = $this->pdo->prepare('INSERT INTO material_comment (uuid,material_id,parent_id,user_id,author_name,author_role,content,status,depth,path) VALUES (?,?,?,?,?,?,?,?,?,?)');
        $stmt->execute([$uuid, $materialId, $parent === null ? null : (int) $parent['id'], $user['id'] ?? null, mb_substr((string) ($user['name'] ?? '匿名'), 0, 100), (string) ($user['role'] ?? 'buyer'), $content, 'visible', $depth, '']);
        $id = (int) $this->pdo->lastInsertId();
        $path = $parent === null ? (string) $id : ($parent['path'] . '/' . $id);
        $this->pdo->prepare('UPDATE material_comment SET path=? WHERE id=?')->execute([$path, $id]);
        return $id;
    }

    private function uuid(): string
    {
        $b = random_bytes(16);
        $b[6] = chr((ord($b[6]) & 0x0f) | 0x40);
        $b[8] = chr((ord($b[8]) & 0x3f) | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($b), 4));
    }
}

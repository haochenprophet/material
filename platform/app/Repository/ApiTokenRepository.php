<?php
declare(strict_types=1);

/**
 * API token 生成/校验/吊销；明文只在创建时返回一次。
 */
final class ApiTokenRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /** @return array{id:int,token:string,client_name:string,scopes:array<int,string>,rate_limit:int} */
    public function create(string $clientName, array $scopes, int $rateLimit = 60): array
    {
        $clientName = trim($clientName);
        $scopes = array_values(array_unique(array_intersect($scopes, ['read', 'write', 'admin'])));
        if ($clientName === '' || $scopes === []) {
            throw new InvalidArgumentException('client_name/scopes 必填');
        }
        $rateLimit = max(1, min(10000, $rateLimit));
        $raw = 'sk_' . bin2hex(random_bytes(24));
        $stmt = $this->pdo->prepare('INSERT INTO api_token (token_hash,client_name,scopes_json,rate_limit,status) VALUES (?,?,?,?,\'active\')');
        $stmt->execute([hash('sha256', $raw), $clientName, json_encode($scopes, JSON_UNESCAPED_UNICODE), $rateLimit]);
        return ['id' => (int) $this->pdo->lastInsertId(), 'token' => $raw, 'client_name' => $clientName, 'scopes' => $scopes, 'rate_limit' => $rateLimit];
    }

    public function findByToken(string $raw): ?array
    {
        $raw = trim($raw);
        if ($raw === '') {
            return null;
        }
        $stmt = $this->pdo->prepare("SELECT * FROM api_token WHERE token_hash=? AND status='active' LIMIT 1");
        $stmt->execute([hash('sha256', $raw)]);
        $row = $stmt->fetch();
        if (!is_array($row)) {
            return null;
        }
        $this->pdo->prepare('UPDATE api_token SET last_used_at=CURRENT_TIMESTAMP WHERE id=?')->execute([(int) $row['id']]);
        $row['scopes'] = json_decode((string) $row['scopes_json'], true) ?: [];
        return $row;
    }

    /** @return array<int, array<string, mixed>> */
    public function all(): array
    {
        return $this->pdo->query('SELECT id,client_name,scopes_json,rate_limit,status,created_at,last_used_at FROM api_token ORDER BY id DESC')->fetchAll() ?: [];
    }

    public function revoke(int $id): bool
    {
        $stmt = $this->pdo->prepare("UPDATE api_token SET status='revoked' WHERE id=? AND status='active'");
        $stmt->execute([$id]);
        return $stmt->rowCount() > 0;
    }
}

<?php
declare(strict_types=1);

/**
 * DB 认证仓储：user + org_member + role。
 * 只读；密码用 password_verify。角色 code 映射到平台 RBAC。
 */
final class PdoAuthRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /** @return array{id:string,account:string,name:string,role:string}|null */
    public function attempt(string $account, string $password): ?array
    {
        $account = trim($account);
        if ($account === '' || $password === '') {
            return null;
        }
        $sql = 'SELECT u.id, u.email, u.phone, u.display_name, u.password_hash, r.code AS role_code
                FROM `user` u
                JOIN org_member om ON om.user_id = u.id AND om.status = \'active\'
                JOIN role r ON r.id = om.role_id
                WHERE (u.email = :email OR u.phone = :phone)
                  AND u.status = \'active\'
                  AND u.deleted_at IS NULL
                LIMIT 1';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['email' => $account, 'phone' => $account]);
        $row = $stmt->fetch();
        if (!is_array($row) || !password_verify($password, (string) $row['password_hash'])) {
            return null;
        }
        return [
            'id' => (string) $row['id'],
            'account' => (string) ($row['email'] ?: $row['phone']),
            'name' => (string) $row['display_name'],
            'role' => $this->mapRole((string) ($row['role_code'] ?? '')),
        ];
    }

    public function mapRole(string $roleCode): string
    {
        return match ($roleCode) {
            'admin', 'ops' => 'admin',
            'finance' => 'finance',
            'qc', 'warehouse' => 'qc',
            'seller_admin', 'seller_ops' => 'seller',
            'buyer_admin', 'buyer', 'viewer' => 'buyer',
            default => 'buyer',
        };
    }
}

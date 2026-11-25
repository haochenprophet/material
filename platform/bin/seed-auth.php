<?php
declare(strict_types=1);

/**
 * 创建 DB 测试管理员（仅 mats_platform_* 测试库，不碰旧库）。
 * 用法：DB_USER=... DB_PASS=... DB_NAME=mats_platform_next2 php platform/bin/seed-auth.php
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    echo 'CLI only';
    exit(1);
}

require dirname(__DIR__) . '/app/bootstrap.php';

$name = getenv('DB_NAME') ?: 'mats_platform_next2';
if (!preg_match('/^mats_platform_[a-z0-9_]{2,48}$/', $name)) {
    fwrite(STDERR, "拒绝数据库名: {$name}\n");
    exit(1);
}
$user = (string) getenv('DB_USER');
$pass = (string) getenv('DB_PASS');
if ($user === '') {
    fwrite(STDERR, "DB_USER 未设置\n");
    exit(2);
}
$host = getenv('DB_HOST') ?: '127.0.0.1';
$port = (int) (getenv('DB_PORT') ?: 3306);
try {
    $pdo = new PDO("mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    $pdo->exec("INSERT INTO org (uuid,name,type,status)
                SELECT UUID(),'DB Demo Org','company','active'
                WHERE NOT EXISTS (SELECT 1 FROM org WHERE name='DB Demo Org')");
    $stmt = $pdo->prepare("INSERT INTO `user` (uuid,email,password_hash,display_name,status)
                           SELECT UUID(),'dbadmin@demo',:hash,'DB Admin','active'
                           WHERE NOT EXISTS (SELECT 1 FROM `user` WHERE email='dbadmin@demo')");
    $stmt->execute(['hash' => password_hash('demo123', PASSWORD_DEFAULT)]);
    $pdo->exec("INSERT INTO org_member (org_id,user_id,role_id,status)
                SELECT o.id,u.id,1,'active'
                FROM org o JOIN `user` u
                WHERE o.name='DB Demo Org' AND u.email='dbadmin@demo'
                ON DUPLICATE KEY UPDATE status='active'");
    $count = (int) $pdo->query("SELECT COUNT(*) FROM org_member om JOIN `user` u ON u.id=om.user_id WHERE u.email='dbadmin@demo'")->fetchColumn();
    echo 'db: ' . $name . PHP_EOL;
    echo 'dbadmin@demo memberships: ' . $count . PHP_EOL;
    exit($count > 0 ? 0 : 1);
} catch (Throwable $e) {
    fwrite(STDERR, 'FAIL: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}

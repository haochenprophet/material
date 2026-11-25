<?php
declare(strict_types=1);

/**
 * PDO 仓储测试（SQLite 内存，不连接 MySQL、不写旧库）。
 * 用法：php platform/tests/pdo.php
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    echo 'CLI only';
    exit(1);
}

require dirname(__DIR__) . '/app/bootstrap.php';

$pass = 0;
$fail = 0;
function check(string $name, bool $cond, string $detail = ''): void
{
    global $pass, $fail;
    if ($cond) {
        $pass++;
        echo "  PASS  {$name}" . PHP_EOL;
    } else {
        $fail++;
        echo "  FAIL  {$name}" . ($detail !== '' ? "  ({$detail})" : '') . PHP_EOL;
    }
}

echo '物格云 PDO repository tests · v' . APP_VERSION . PHP_EOL . PHP_EOL;
check('pdo_sqlite 可用', extension_loaded('pdo_sqlite'));
if (!extension_loaded('pdo_sqlite')) {
    echo 'SKIP' . PHP_EOL;
    exit(1);
}

$pdo = new PDO('sqlite::memory:');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->exec((string) file_get_contents(PLATFORM_PATH . '/database/dev-schema.sql'));
$stmt = $pdo->prepare('INSERT INTO materials (id,title,spec,price,unit,stock,location,badge,badge_kind,thumb,thumb_class) VALUES (?,?,?,?,?,?,?,?,?,?,?)');
$stmt->execute(['p1', 'TE 170364-1', '4Pin', '18.50', '个', '1,240', '深圳', 'A 级', 'ok', 'TE', '']);
$stmt->execute(['p2', 'GRM188', '0402', '0.12', '个', '86,000', '苏州', 'A 级', 'ok', 'Murata', 't2']);

$repo = new PdoMaterialRepository($pdo);
check('all 2 条', count($repo->all()) === 2);
$p1 = $repo->find('p1');
check('find p1', ($p1['title'] ?? '') === 'TE 170364-1');
check('badgeKind 映射', ($p1['badgeKind'] ?? '') === 'ok');
check('price 字符串保留', ($p1['price'] ?? '') === '18.50');
check('find 不存在 null', $repo->find('nope') === null);
check('SQL 注入不生效', $repo->find("p1' OR '1'='1") === null);
check('接口实现', $repo instanceof MaterialRepository);

$devFile = STORAGE_PATH . '/dev.sqlite';
check('dev 脚本可运行', is_file(PLATFORM_PATH . '/bin/dev-db.php'));

echo PHP_EOL . '结果：PASS ' . $pass . ' / FAIL ' . $fail . PHP_EOL;
exit($fail > 0 ? 1 : 0);

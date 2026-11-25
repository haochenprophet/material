<?php
declare(strict_types=1);

/**
 * 旧 materiel 33 列映射审计（只读）。
 * 用法：DB_USER=... DB_PASS=... DB_NAME=mats_platform_next2 php platform/bin/mapping-audit.php
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    echo 'CLI only';
    exit(1);
}

require dirname(__DIR__) . '/app/bootstrap.php';

$user = (string) getenv('DB_USER');
if ($user === '') {
    echo "SKIP: DB_USER 未设置\n";
    exit(0);
}
$name = getenv('DB_NAME') ?: 'mats_platform_next2';
$pdo = Database::pdo();
$old = $pdo->query("SELECT COLUMN_NAME FROM information_schema.columns WHERE table_schema='materiel' AND table_name='materiel' ORDER BY ORDINAL_POSITION")->fetchAll(PDO::FETCH_COLUMN) ?: [];
$new = $pdo->query("SELECT CONCAT(TABLE_NAME,'.',COLUMN_NAME) FROM information_schema.columns WHERE table_schema=" . $pdo->quote($name))->fetchAll(PDO::FETCH_COLUMN) ?: [];
$newMap = array_fill_keys($new, true);
$mapped = FieldMapping::all();
$missing = [];
$legacySet = array_flip($old);
foreach ($mapped as $m) {
    $parts = explode('.', $m['target']);
    $baseTarget = count($parts) >= 2 ? $parts[0] . '.' . $parts[1] : $m['target'];
    if (!isset($newMap[$baseTarget])) {
        $missing[] = $m['legacy'] . ' → ' . $m['target'];
    }
    if (!isset($legacySet[$m['legacy']])) {
        echo 'WARN legacy column missing: ' . $m['legacy'] . PHP_EOL;
    }
}
$coverage = FieldMapping::coverage();
echo 'target: ' . $name . PHP_EOL;
echo 'legacy: ' . count($old) . ' columns / mapping: ' . count($mapped) . PHP_EOL;
echo 'mapped: ' . $coverage['mapped'] . ' · derived: ' . $coverage['derived'] . ' · unmapped: ' . $coverage['unmapped'] . PHP_EOL;
echo 'target tables: ' . implode(', ', FieldMapping::targetTables()) . PHP_EOL;
echo 'missing targets: ' . count($missing) . PHP_EOL;
foreach ($missing as $m) {
    echo ' - ' . $m . PHP_EOL;
}
foreach ($mapped as $m) {
    printf(
        "%-16s %-44s %-8s %s\n",
        $m['legacy'],
        $m['target'],
        $m['status'],
        $m['note']
    );
}
exit($missing === [] && count($old) >= 33 ? 0 : 2);

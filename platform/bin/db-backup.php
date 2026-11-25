<?php
declare(strict_types=1);

/**
 * 数据库备份（默认 dry-run；--execute 才写文件）。
 * 只允许 mats_platform_*；不删除、不覆盖同名文件。
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
$execute = in_array('--execute', $argv, true);
$bin = getenv('DB_DUMP_BIN') ?: 'C:/Program Files/MySQL/MySQL Server 8.0/bin/mysqldump.exe';
if (!is_file($bin)) {
    $bin = 'mysqldump';
}
$dir = STORAGE_PATH . '/backups';
if (!is_dir($dir) && !mkdir($dir, 0777, true) && !is_dir($dir)) {
    fwrite(STDERR, "无法创建备份目录\n");
    exit(1);
}
$file = $dir . '/' . $name . '-' . date('Ymd-His') . '.sql';
echo 'db: ' . $name . PHP_EOL;
echo 'dump: ' . $bin . PHP_EOL;
echo 'out: ' . $file . PHP_EOL;
echo 'mode: ' . ($execute ? 'EXECUTE' : 'DRY-RUN') . PHP_EOL;
if (!$execute) {
    echo "OK: dry-run; add --execute\n";
    exit(0);
}
if (file_exists($file)) {
    fwrite(STDERR, "拒绝覆盖已存在备份\n");
    exit(1);
}
$cmd = escapeshellarg($bin)
    . ' --single-transaction --no-tablespaces --skip-lock-tables'
    . ' --user=' . escapeshellarg($user)
    . ' --host=' . escapeshellarg(getenv('DB_HOST') ?: '127.0.0.1')
    . ' --port=' . (int) (getenv('DB_PORT') ?: 3306)
    . ' --result-file=' . escapeshellarg($file)
    . ' ' . escapeshellarg($name);
putenv('MYSQL_PWD=' . $pass);
exec($cmd . ' 2>&1', $out, $code);
if ($code !== 0) {
    fwrite(STDERR, 'FAIL: mysqldump ' . implode("\n", $out) . PHP_EOL);
    exit(1);
}
echo 'backup: ' . $file . ' (' . filesize($file) . " bytes)\n";
exit(0);

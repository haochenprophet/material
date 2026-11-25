<?php
declare(strict_types=1);

/**
 * 迁移计划器测试：只重写/计划，不执行 SQL。
 * 用法：php platform/tests/migration-runner.php
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

echo '物格云 migration runner tests · v' . APP_VERSION . PHP_EOL . PHP_EOL;
$runner = new MigrationRunner();
$sql = "CREATE DATABASE IF NOT EXISTS `mats`\n  DEFAULT CHARACTER SET utf8mb4;\nUSE `mats`;\nCREATE TABLE a (id INT);";
$rewritten = $runner->rewrite($sql, 'mats_platform_next');
check('CREATE DATABASE 改名', str_contains($rewritten, 'CREATE DATABASE IF NOT EXISTS `mats_platform_next`'));
check('USE 改名', str_contains($rewritten, 'USE `mats_platform_next`;'));
check('旧库名不再出现', !str_contains($rewritten, '`mats`'));
$bomSql = "\xEF\xBB\xBFCREATE DATABASE `mats`;\nUSE `mats`;";
$bomRewritten = $runner->rewrite($bomSql, 'mats_platform_next');
check('UTF-8 BOM 去除', !str_starts_with($bomRewritten, "\xEF\xBB\xBF"));
check('拒绝 materiel', (function () use ($runner): bool {
    try {
        $runner->validateTarget('materiel');
        return false;
    } catch (Throwable) {
        return true;
    }
})());
check('拒绝非法库名', (function () use ($runner): bool {
    try {
        $runner->validateTarget('bad-name');
        return false;
    } catch (Throwable) {
        return true;
    }
})());
check('允许 mats', (function () use ($runner): bool {
    try {
        $runner->validateTarget('mats');
        return true;
    } catch (Throwable) {
        return false;
    }
})());
$plan = $runner->plan([PLATFORM_PATH . '/database/generated/mats_platform_next/001_master.sql'], 'mats_platform_next');
check('plan 27 表', $plan['total_tables'] === 27);
check('plan 有语句数', $plan['statements'] > 0);
check('plan 文件哈希', strlen($plan['files']['001_master.sql']['sha256']) === 64);

echo PHP_EOL . '结果：PASS ' . $pass . ' / FAIL ' . $fail . PHP_EOL;
exit($fail > 0 ? 1 : 0);

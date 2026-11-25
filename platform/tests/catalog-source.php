<?php
declare(strict_types=1);

/**
 * 目录数据源开关测试（不连接 DB）。
 * 用法：php platform/tests/catalog-source.php
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

echo '物格云 catalog source tests · v' . APP_VERSION . PHP_EOL . PHP_EOL;
putenv('CATALOG_SOURCE');
putenv('DB_USER');
check('默认 demo', CatalogRepositoryFactory::source() === 'demo');
check('默认返回 Demo 仓储', CatalogRepositoryFactory::make() instanceof DemoMaterialRepository);

$override = new DemoMaterialRepository();
check('可注入覆盖', CatalogRepositoryFactory::make($override) === $override);

putenv('CATALOG_SOURCE=db');
check('db 开关生效', CatalogRepositoryFactory::source() === 'db');
$threw = false;
try {
    CatalogRepositoryFactory::make();
} catch (RuntimeException) {
    $threw = true;
}
check('db 但无 DB_USER 报错', $threw);

putenv('CATALOG_SOURCE');
check('复位 demo', CatalogRepositoryFactory::source() === 'demo');

echo PHP_EOL . '结果：PASS ' . $pass . ' / FAIL ' . $fail . PHP_EOL;
exit($fail > 0 ? 1 : 0);

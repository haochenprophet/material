<?php
declare(strict_types=1);

/**
 * 目录数据源开关：
 *   CATALOG_SOURCE=db    → PdoListingRepository（需要 DB_USER/DB_NAME）
 *   其他/未设置          → DemoMaterialRepository
 * 页面只依赖 MaterialRepository，不关心来源。
 */
final class CatalogRepositoryFactory
{
    public static function source(): string
    {
        return getenv('CATALOG_SOURCE') === 'db' ? 'db' : 'demo';
    }

    public static function make(?MaterialRepository $override = null): MaterialRepository
    {
        if ($override !== null) {
            return $override;
        }
        if (self::source() === 'db') {
            if (!Database::configured()) {
                throw new RuntimeException('CATALOG_SOURCE=db 但 DB_USER 未配置');
            }
            return new PdoListingRepository(Database::pdo());
        }
        return new DemoMaterialRepository();
    }
}

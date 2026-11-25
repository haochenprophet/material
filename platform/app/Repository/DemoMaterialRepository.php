<?php
declare(strict_types=1);

/**
 * 演示实现。未来 PdoMaterialRepository 实现同一接口即可替换，页面不改。
 */
final class DemoMaterialRepository implements MaterialRepository
{
    public function all(): array
    {
        return DemoData::products();
    }

    public function find(string $id): ?array
    {
        return DemoData::product($id);
    }
}

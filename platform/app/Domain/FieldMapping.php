<?php
declare(strict_types=1);

/**
 * 旧 materiel 33 列 → 新 material/listing/stock/provenance 的字段映射。
 * 只做映射定义与缺口统计，不执行数据搬迁。
 */
final class FieldMapping
{
    public const LEGACY_TABLE = 'materiel';
    public const LEGACY_COLUMNS = 33;

    /** @return array<int, array{legacy:string,target:string,status:string,note:string}> */
    public static function all(): array
    {
        $raw = [
            ['idmateriel', 'material.legacy_id', 'mapped', '旧主键保留，唯一索引'],
            ['uuid', 'material.uuid', 'mapped', 'UUID 直接迁移'],
            ['time', 'material.created_at', 'mapped', '旧 time → 创建时间'],
            ['name', 'material.name', 'mapped', '物料名称'],
            ['type', 'material.attributes_summary.type', 'derived', '旧自由文本类型 → JSON 属性'],
            ['barcode', 'material.barcode', 'mapped', '条码'],
            ['serial_number', 'material.attributes_summary.serial_number', 'derived', '序列号入 JSON'],
            ['part_number', 'material.part_number', 'mapped', 'MPN'],
            ['date_code', 'material.attributes_summary.date_code', 'derived', '生产周期入 JSON'],
            ['inbound_quantity', 'listing.quantity', 'mapped', '入库数量 → 可售数量'],
            ['cost_price', 'stock_lot.unit_cost', 'derived', '成本 → 库存批次单位成本'],
            ['total_cost', 'stock_lot.unit_cost', 'derived', '总成本保留在迁移日志，批次按单价+数量计算'],
            ['price', 'listing.price', 'mapped', '售价'],
            ['manufacturer', 'material.manufacturer_id', 'derived', '厂商名 → manufacturer 维表'],
            ['manufacture_date', 'stock_lot.manufacture_date', 'derived', '生产日期 → 批次'],
            ['life_date', 'stock_lot.expiry_date', 'derived', '有效期 → 批次'],
            ['location', 'listing.location_id', 'derived', '库位文本 → location 维表'],
            ['owner', 'listing.store_id', 'derived', '货主 → 店铺（需商家映射）'],
            ['from', 'material_provenance.source_name', 'derived', '来源'],
            ['to', 'listing.warehouse_id', 'derived', '去向 → 仓库'],
            ['how', 'material.attributes_summary.how', 'derived', '获取方式入 JSON'],
            ['who', 'material.created_by', 'mapped', '创建人'],
            ['what', 'material.description', 'mapped', '描述'],
            ['when', 'material.updated_at', 'mapped', '旧 when → 更新时间'],
            ['where', 'listing.location_id', 'derived', '地点 → 库位'],
            ['why', 'material.attributes_summary.why', 'derived', '原因入 JSON'],
            ['action', 'listing.status', 'derived', '动作 → 状态（待业务确认）'],
            ['country', 'material_provenance.country', 'mapped', '原产国'],
            ['url', 'material.datasheet_url', 'mapped', '资料链接'],
            ['status', 'material.lifecycle_status', 'mapped', '状态枚举映射'],
            ['remark', 'material.description', 'derived', '备注并入描述/JSON'],
            ['priority', 'material.attributes_summary.priority', 'derived', '优先级入 JSON'],
            ['partial', 'material.attributes_summary.partial', 'derived', '部分/拆零入 JSON'],
        ];
        return array_map(static fn (array $r): array => [
            'legacy' => (string) $r[0],
            'target' => (string) $r[1],
            'status' => (string) $r[2],
            'note' => (string) $r[3],
        ], $raw);
    }

    /** @return array{total:int,mapped:int,derived:int,unmapped:int,targets:int} */
    public static function coverage(): array
    {
        $rows = self::all();
        $targets = [];
        $out = ['total' => count($rows), 'mapped' => 0, 'derived' => 0, 'unmapped' => 0, 'targets' => 0];
        foreach ($rows as $r) {
            $out[$r['status']] = ($out[$r['status']] ?? 0) + 1;
            $targets[$r['target']] = true;
        }
        $out['targets'] = count($targets);
        return $out;
    }

    /** @return array<int, string> */
    public static function targetTables(): array
    {
        $tables = [];
        foreach (self::all() as $r) {
            $tables[explode('.', $r['target'], 2)[0]] = true;
        }
        return array_keys($tables);
    }
}

<?php
declare(strict_types=1);

/**
 * SQL 迁移审计：解析 CREATE TABLE、检查文件顺序/重复表。
 * 只读，不连接数据库、不执行 SQL。
 */
final class SqlMigrationInspector
{
    /** @var array<int, string> */
    public const REQUIRED_FILES = [
        '001_master.sql',
        '002_inventory.sql',
        '003_listing.sql',
        '004_bom_trade.sql',
        '005_migration_legacy.sql',
        '005b_migration_templates.sql',
        '006_seed.sql',
    ];

    /**
     * @return array<int, string>
     */
    public function tables(string $sql): array
    {
        preg_match_all('/CREATE\s+TABLE\s+(?:IF\s+NOT\s+EXISTS\s+)?`?([a-zA-Z0-9_]+)`?/i', $sql, $m);
        return array_values(array_unique($m[1] ?? []));
    }

    /**
     * @param array<int, string> $files absolute paths
     * @return array{files:array<string,int>, tables:array<int,string>, duplicates:array<int,string>, missing:array<int,string>, total:int}
     */
    public function audit(array $files): array
    {
        $byName = [];
        foreach ($files as $path) {
            $byName[basename($path)] = $path;
        }
        $missing = [];
        foreach (self::REQUIRED_FILES as $name) {
            if (!isset($byName[$name])) {
                $missing[] = $name;
            }
        }
        $counts = [];
        $all = [];
        foreach (self::REQUIRED_FILES as $name) {
            if (!isset($byName[$name])) {
                continue;
            }
            $tables = $this->tables((string) file_get_contents($byName[$name]));
            $counts[$name] = count($tables);
            foreach ($tables as $table) {
                $all[] = $table;
            }
        }
        $dupes = array_values(array_unique(array_diff_assoc($all, array_unique($all))));
        sort($dupes);
        return [
            'files' => $counts,
            'tables' => array_values(array_unique($all)),
            'duplicates' => $dupes,
            'missing' => $missing,
            'total' => count($all),
        ];
    }
}

<?php
declare(strict_types=1);

/**
 * 迁移计划器：只重写库名/生成计划，不执行 SQL。
 * 目的：修复 001 里硬编码 `CREATE DATABASE mats; USE mats;` 的可移植性问题。
 */
final class MigrationRunner
{
    public function validateTarget(string $target): void
    {
        if (!preg_match('/^[a-zA-Z][a-zA-Z0-9_]{2,63}$/', $target)) {
            throw new InvalidArgumentException('目标库名不合法');
        }
        if ($target === 'materiel' || !str_starts_with($target, 'mats')) {
            throw new InvalidArgumentException('拒绝目标：只允许 mats / mats_*，禁止 materiel/旧库');
        }
    }

    public function rewrite(string $sql, string $target): string
    {
        $this->validateTarget($target);
        $sql = preg_replace('/^\xEF\xBB\xBF/', '', $sql) ?? $sql;
        $sql = preg_replace(
            '/(CREATE\s+DATABASE\s+(?:IF\s+NOT\s+EXISTS\s+)?)`?[a-zA-Z0-9_]+`?/i',
            '$1`' . $target . '`',
            $sql
        ) ?? $sql;
        $sql = preg_replace(
            '/^\s*USE\s+`?[a-zA-Z0-9_]+`?\s*;/im',
            'USE `' . $target . '`;',
            $sql
        ) ?? $sql;
        return $sql;
    }

    /**
     * @param array<int, string> $files absolute paths
     * @return array{target:string, files:array<string,array{sha256:string,tables:array<int,string>,statements:int}>, tables:array<int,string>, total_tables:int, statements:int}
     */
    public function plan(array $files, string $target): array
    {
        $this->validateTarget($target);
        $inspector = new SqlMigrationInspector();
        $out = ['target' => $target, 'files' => [], 'tables' => [], 'total_tables' => 0, 'statements' => 0];
        foreach ($files as $file) {
            $sql = $this->rewrite((string) file_get_contents($file), $target);
            $tables = $inspector->tables($sql);
            $statements = substr_count($sql, ';');
            $out['files'][basename($file)] = [
                'sha256' => hash('sha256', $sql),
                'tables' => $tables,
                'statements' => $statements,
            ];
            $out['tables'] = array_values(array_unique(array_merge($out['tables'], $tables)));
            $out['statements'] += $statements;
        }
        $out['total_tables'] = count($out['tables']);
        return $out;
    }
}

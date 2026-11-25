<?php
declare(strict_types=1);

/**
 * BOM CSV 解析器（首版，不依赖 PhpSpreadsheet）。
 * 支持：逗号/制表符/分号；UTF-8 BOM；中英文表头；无表头三列。
 * 限制：≤500 行、≤1MB；超过返回 errors，不抛异常。
 */
final class BomCsvParser
{
    public const MAX_ROWS = 500;
    public const MAX_BYTES = 1048576;

    /** @var array<int, string> */
    private const MPN_ALIASES = ['mpn', 'part', 'partno', 'part_number', 'part number', 'model', '型号', '料号', '物料编码', '物料号'];
    /** @var array<int, string> */
    private const QTY_ALIASES = ['qty', "q'ty", 'quantity', '数量', '需求数量'];
    /** @var array<int, string> */
    private const DESC_ALIASES = ['desc', 'description', '描述', '规格', '名称'];

    /**
     * @return array{lines:array<int, array{mpn:string,qty:float,desc:string,line:int}>, errors:array<int, array{line:int,message:string}>, count:int, delimiter:string}
     */
    public static function parse(string $csv): array
    {
        $errors = [];
        $lines = [];
        $delimiter = ',';

        if (strlen($csv) > self::MAX_BYTES) {
            return ['lines' => [], 'errors' => [['line' => 0, 'message' => '文件超过 1MB']], 'count' => 0, 'delimiter' => ','];
        }
        $csv = str_replace("\r\n", "\n", str_replace("\r", "\n", $csv));
        if (str_starts_with($csv, "\xEF\xBB\xBF")) {
            $csv = substr($csv, 3);
        }
        $rawLines = explode("\n", $csv);
        $rows = [];
        foreach ($rawLines as $i => $raw) {
            if (trim($raw) === '') {
                continue;
            }
            $rows[] = ['line' => $i + 1, 'raw' => $raw];
        }
        if ($rows === []) {
            return ['lines' => [], 'errors' => [['line' => 0, 'message' => '文件为空']], 'count' => 0, 'delimiter' => ','];
        }

        $delimiter = self::detectDelimiter($rows[0]['raw']);
        $first = self::split($rows[0]['raw'], $delimiter);
        $header = self::mapHeader($first);
        $hasHeader = $header['mpn'] !== null || $header['qty'] !== null;
        if (!$hasHeader) {
            $header = ['mpn' => 0, 'qty' => 1, 'desc' => 2];
        }
        $dataRows = $hasHeader ? array_slice($rows, 1) : $rows;

        if (count($dataRows) > self::MAX_ROWS) {
            $errors[] = ['line' => 0, 'message' => '超过 ' . self::MAX_ROWS . ' 行，请拆分后上传'];
            $dataRows = array_slice($dataRows, 0, self::MAX_ROWS);
        }

        foreach ($dataRows as $row) {
            $cells = self::split($row['raw'], $delimiter);
            $mpn = trim((string) ($cells[$header['mpn']] ?? ''));
            $qtyRaw = trim((string) ($cells[$header['qty']] ?? ''));
            $desc = trim((string) ($cells[$header['desc']] ?? ''));
            if ($mpn === '' && $qtyRaw === '' && $desc === '') {
                continue;
            }
            if ($mpn === '') {
                $errors[] = ['line' => $row['line'], 'message' => '缺少型号/MPN'];
                continue;
            }
            $qtyRaw = str_replace([',', ' '], '', $qtyRaw);
            if ($qtyRaw === '' || !is_numeric($qtyRaw)) {
                $errors[] = ['line' => $row['line'], 'message' => '数量不是数字'];
                continue;
            }
            $qty = (float) $qtyRaw;
            if ($qty <= 0) {
                $errors[] = ['line' => $row['line'], 'message' => '数量必须大于 0'];
                continue;
            }
            $lines[] = ['mpn' => $mpn, 'qty' => $qty, 'desc' => $desc, 'line' => $row['line']];
        }

        return ['lines' => $lines, 'errors' => $errors, 'count' => count($lines), 'delimiter' => $delimiter === "\t" ? 'tab' : $delimiter];
    }

    private static function detectDelimiter(string $line): string
    {
        $candidates = [',' => substr_count($line, ','), "\t" => substr_count($line, "\t"), ';' => substr_count($line, ';')];
        arsort($candidates);
        $key = array_key_first($candidates);
        return (string) ($key ?? ',');
    }

    /** @return array<int, string> */
    private static function split(string $line, string $delimiter): array
    {
        return array_map(static fn ($v) => trim($v, " \t\n\r\0\x0B\""), str_getcsv($line, $delimiter, '"', '\\'));
    }

    /**
     * @param array<int, string> $cells
     * @return array{mpn:int|null, qty:int|null, desc:int|null}
     */
    private static function mapHeader(array $cells): array
    {
        $map = ['mpn' => null, 'qty' => null, 'desc' => null];
        foreach ($cells as $i => $cell) {
            $key = strtolower(trim((string) $cell));
            if ($map['mpn'] === null && in_array($key, self::MPN_ALIASES, true)) {
                $map['mpn'] = $i;
            } elseif ($map['qty'] === null && in_array($key, self::QTY_ALIASES, true)) {
                $map['qty'] = $i;
            } elseif ($map['desc'] === null && in_array($key, self::DESC_ALIASES, true)) {
                $map['desc'] = $i;
            }
        }
        return $map;
    }
}

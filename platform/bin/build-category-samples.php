<?php
declare(strict_types=1);

/**
 * 从旧 hardware.sql 的 Greatwall CIS 数据抽取品类样本，生成 fixture。
 * 这是开发期工具；运行时不依赖 hardware 库。
 * 用法：DB_USER=... DB_PASS=... php platform/bin/build-category-samples.php
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    echo 'CLI only';
    exit(1);
}
require dirname(__DIR__) . '/app/bootstrap.php';

$db = getenv('HARDWARE_DB') ?: 'hardware_learning';
if (!preg_match('/^hardware(_[a-z0-9_]+)?$/', $db)) {
    fwrite(STDERR, "HARDWARE_DB 不合法\n");
    exit(1);
}
$user = (string) getenv('DB_USER');
$pass = (string) getenv('DB_PASS');
if ($user === '') { fwrite(STDERR, "DB_USER 未设置\n"); exit(2); }
$host = getenv('DB_HOST') ?: '127.0.0.1';
$port = (int) (getenv('DB_PORT') ?: 3306);
$pdo = new PDO("mysql:host={$host};port={$port};dbname={$db};charset=utf8mb4", $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$rows = $pdo->query("SELECT `Part Type`, `Part Number`, `Component_Name`, `Description`, `Value`, `Package_Type` FROM `Greatwall CIS` WHERE `Component_Name` IS NOT NULL AND `Component_Name`<>''")->fetchAll(PDO::FETCH_ASSOC);

$categories = require PLATFORM_PATH . '/config/category-pages.php';
$typeMap = [];
foreach ($categories as $key => $c) {
    $typeMap[$c['legacy_type']] = $key;
}
$subNames = [];
foreach ($categories as $key => $c) {
    foreach ($c['subs'] as $sk => $sn) { $subNames[$key][$sk] = $sn; }
}
function classifySub(string $category, string $text): string
{
    $t = strtoupper($text);
    return match ($category) {
        'resistor' => preg_match('/\b(VR|POT|TRIMMER|POTENTIOMETER)\b/', $t) ? 'potentiometer' : (preg_match('/THERMISTOR|NTC|PTC/', $t) ? 'thermistor' : (preg_match('/NETWORK|ARRAY/', $t) ? 'network' : (preg_match('/SHUNT|CURRENT SENSE/', $t) ? 'shunt' : (preg_match('/DIP|AXIAL|THROUGH/', $t) ? 'through_hole' : 'chip_resistor')))),
        'capacitor' => preg_match('/CAP\s*EL|ELECTROLYTIC|ALUMIN/', $t) ? 'aluminum' : (preg_match('/TANT|POSCAP|POLYMER/', $t) ? 'tantalum' : (preg_match('/FILM|MKP|MKT/', $t) ? 'film' : (preg_match('/SUPERCAP|EDLC/', $t) ? 'supercap' : (preg_match('/SAFETY|X2|Y2/', $t) ? 'safety' : 'mlcc')))),
        'inductor' => preg_match('/FERRITE\s*BEAD|\bBEAD\b/', $t) ? 'ferrite' : (preg_match('/TRANSFORMER/', $t) ? 'transformer' : (preg_match('/COMMON\s*MODE/', $t) ? 'common_mode' : (preg_match('/POWER/', $t) ? 'power' : 'chip_inductor'))),
        'connector' => preg_match('/SOCKET|PGA|BGA/', $t) ? 'ic_socket' : (preg_match('/BOARD\s*TO\s*BOARD|BTB/', $t) ? 'board_to_board' : (preg_match('/WAFER|HOUSING|WIRE\s*TO/', $t) ? 'wire_to_board' : (preg_match('/PIN\s*HEADER|HEADER/', $t) ? 'header' : (preg_match('/TERMINAL/', $t) ? 'terminal' : (preg_match('/USB/', $t) ? 'usb' : (preg_match('/RF|COAX|SMA/', $t) ? 'rf' : (preg_match('/FPC|FFC/', $t) ? 'fpc' : 'other'))))))),
        'discrete' => preg_match('/TVS|ESD/', $t) ? 'tvs' : (preg_match('/MOSFET|\bMOS\b/', $t) ? 'mosfet' : (preg_match('/IGBT/', $t) ? 'igbt' : (preg_match('/THYRISTOR|SCR|TRIAC/', $t) ? 'thyristor' : (preg_match('/DIODE|RECTIFIER|SCHOTTKY|ZENER/', $t) ? 'diode' : (preg_match('/TRANSISTOR|BJT/', $t) ? 'bjt' : 'other'))))),
        'ic' => preg_match('/POWER|PMIC|LDO|REGULATOR|DC-DC|CHARGER/', $t) ? 'pmic' : (preg_match('/OPAMP|OP\s*AMP|AMPLIFIER|COMPARATOR/', $t) ? 'amplifier' : (preg_match('/LOGIC|GATE|FLIP/', $t) ? 'logic' : (preg_match('/USB|UART|RS232|RS485|I2C|SPI|HDMI|INTERFACE|CAN|ETH/', $t) ? 'interface' : (preg_match('/RTC|CLOCK|OSC|CRYSTAL|TIMING/', $t) ? 'clock' : (preg_match('/DRIVER/', $t) ? 'driver' : (preg_match('/AUDIO|AMP/', $t) ? 'audio' : (preg_match('/RF|WIFI|BLUETOOTH/', $t) ? 'rf' : (preg_match('/SENSOR/', $t) ? 'sensor' : 'general_ic')))))))),
        'cpu' => preg_match('/XEON|SERVER/', $t) ? 'server' : (preg_match('/ATOM|CORE|PENWELL|IVY|HASWELL|BAY|Z37|MOBILE|BGA/', $t) ? 'mobile' : 'embedded'),
        'chipset' => preg_match('/PMIC|POWER/', $t) ? 'pmic' : (preg_match('/CLOCK|RTC/', $t) ? 'clock' : (preg_match('/USB|INTERFACE/', $t) ? 'interface' : 'chipset')),
        'memory' => preg_match('/DDR|SDRAM|DRAM|LPDDR/', $t) ? 'dram' : (preg_match('/NAND|FLASH/', $t) ? 'flash' : (preg_match('/EEPROM/', $t) ? 'eeprom' : (preg_match('/SRAM/', $t) ? 'sram' : 'memory'))),
        'programmable' => preg_match('/EEPROM/', $t) ? 'eeprom' : (preg_match('/FPGA/', $t) ? 'fpga' : (preg_match('/CPLD/', $t) ? 'cpld' : (preg_match('/MCU|MICRO/', $t) ? 'mcu' : 'programmable'))),
        'communication' => preg_match('/WIFI|WLAN|802\.11/', $t) ? 'wifi' : (preg_match('/BLUETOOTH|\bBT\b/', $t) ? 'bluetooth' : (preg_match('/LTE|GSM|3G|4G|5G|CELL/', $t) ? 'cellular' : (preg_match('/GPS|GNSS/', $t) ? 'gps' : (preg_match('/NFC/', $t) ? 'nfc' : 'communication')))),
        'system' => preg_match('/VIBRAT/', $t) ? 'vibrator' : (preg_match('/BUZZER|SPEAKER/', $t) ? 'buzzer' : (preg_match('/FAN/', $t) ? 'fan' : (preg_match('/BATTERY/', $t) ? 'battery' : (preg_match('/ANTENNA/', $t) ? 'antenna' : 'module')))),
        'mechanical' => preg_match('/\bNUT\b/', $t) ? 'nut' : (preg_match('/\bSCREW\b/', $t) ? 'screw' : (preg_match('/STANDOFF|SPACER/', $t) ? 'standoff' : (preg_match('/HEATSINK|COOLER/', $t) ? 'heatsink' : (preg_match('/SHIELD/', $t) ? 'shield' : 'mechanical')))),
        default => 'other',
    };
}
$out = ['source' => 'material/database/hardware.sql', 'source_table' => 'Greatwall CIS', 'generated_at' => date('c'), 'total_rows' => count($rows), 'categories' => []];
$seen = [];
foreach ($categories as $key => $meta) {
    $candidates = [];
    foreach ($rows as $r) {
        $type = (string) $r['Part Type'];
        if (($typeMap[$type] ?? null) !== $key) { continue; }
        $pn = trim((string) $r['Part Number']);
        $cn = trim((string) $r['Component_Name']);
        if ($pn === '' || $cn === '') { continue; }
        if (isset($seen[$key . ':' . $pn])) { continue; }
        $text = $cn . ' ' . (string) $r['Description'] . ' ' . (string) $r['Value'];
        $sub = classifySub($key, $text);
        if (!isset($subNames[$key][$sub])) { $sub = array_key_first($meta['subs']); }
        $candidates[$sub][] = [
            'part_number' => mb_substr($pn, 0, 120),
            'component_name' => mb_substr($cn, 0, 180),
            'description' => mb_substr(trim((string) $r['Description']), 0, 220),
            'value' => mb_substr(trim((string) $r['Value']), 0, 120),
            'package' => mb_substr(trim((string) $r['Package_Type']), 0, 40),
            'subcategory_key' => $sub,
            'subcategory_name' => $subNames[$key][$sub],
        ];
    }
    $samples = [];
    foreach ($candidates as $sub => $items) { $samples[] = $items[0]; }
    foreach ($candidates as $sub => $items) {
        foreach (array_slice($items, 1) as $item) {
            if (count($samples) >= 8) { break 2; }
            $samples[] = $item;
        }
    }
    $samples = array_slice($samples, 0, 8);
    foreach ($samples as $s) { $seen[$key . ':' . $s['part_number']] = true; }
    $out['categories'][$key] = ['key' => $key, 'legacy_count' => (int) $meta['count'], 'samples' => $samples];
}
$dir = PLATFORM_PATH . '/database/fixtures';
if (!is_dir($dir) && !mkdir($dir, 0777, true) && !is_dir($dir)) { fwrite(STDERR, "无法创建 fixtures\n"); exit(1); }
$json = json_encode($out, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
file_put_contents($dir . '/category-samples.json', $json);
echo 'categories: ' . count($out['categories']) . PHP_EOL;
foreach ($out['categories'] as $k => $c) { echo str_pad($k, 14) . count($c['samples']) . ' samples / ' . $c['legacy_count'] . ' rows' . PHP_EOL; }
echo 'fixture: ' . $dir . '/category-samples.json' . PHP_EOL;

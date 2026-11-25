<?php
declare(strict_types=1);

/**
 * 平台配置。所有路径/开关集中在这里，便于回溯。
 * 不读取 .env 之外的外部服务；首版为纯 UI 演示，不连数据库。
 */
return [
    'name'      => '物格云',
    'short'     => 'MaterialGrid',
    'version'   => trim((string) file_get_contents(PLATFORM_PATH . '/VERSION')),
    'base_url'  => '', // 部署在域名根目录时留空；子目录部署填 /material/platform/public
    'debug'     => true,
    'session'   => [
        'name'     => 'wuge_sid',
        'httponly' => true,
        'samesite' => 'Lax',
        'secure'   => false, // 生产 HTTPS 置 true
    ],
    'demo'      => true,
];

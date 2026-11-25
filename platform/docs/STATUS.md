# 平台状态快照

## 命令

```powershell
$env:DB_USER='patent'; $env:DB_PASS='patent'; $env:DB_NAME='mats_platform_next2'
php platform/bin/status.php
```

## 说明

- 只读：版本/Release/manifest 文件数/SHA-256
- release 目录数
- 测试文件数
- 旧代码关键文件存在性
- 检查点数量与最新路径
- 有 DB_USER 时输出表数/role/listing/material 行数

## 用途

- 10 小时窗口的每小时巡检
- 发布前确认“当前版本/快照/旧代码”三项
- 不修改任何文件、不连接写库

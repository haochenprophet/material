# 健康检查 / 监控 / 备份

## 健康检查

```powershell
$env:DB_USER='patent'; $env:DB_PASS='patent'; $env:DB_NAME='mats_platform_next2'
php platform/bin/healthcheck.php --url=http://127.0.0.1:8091/health
```

检查：

- manifest 存在且有效
- storage 可写
- DB 可连接（配置时）
- HTTP `/health` 返回 `"status":"ok"`（可选）

退出码 0 = OK；1 = FAIL。

## 备份

```powershell
php platform/bin/db-backup.php            # dry-run
php platform/bin/db-backup.php --execute  # 实际备份
```

- 只允许 `mats_platform_*`
- 不覆盖同名文件
- 输出 `storage/backups/<db>-YYYYmmdd-His.sql`
- 使用 `mysqldump --single-transaction --no-tablespaces`
- 不删除旧备份

实测：

```text
mats_platform_next2-20261125-101906.sql (162073 bytes)
```

## 监控建议

- 外部探针 1 分钟请求 `/health`
- 失败告警：HTTP != 200 或 `status != ok`
- DB/磁盘/错误日志独立告警
- 备份任务每日执行 + 恢复演练
- 当前未接 Prometheus/Sentry/PagerDuty

## 恢复演练

1. 新建 `mats_platform_restore_*` 库
2. 导入最近备份
3. 对比表数/关键行数
4. 用 release 副本旁路启动连接该库
5. 通过后才允许切生产；旧库不删除

## 生产缺口

- 无自动定时备份（cron/systemd timer 未配置）
- 无异地/对象存储备份
- 无恢复演练记录
- 无监控告警接入

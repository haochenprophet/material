# 生产部署（示例，不自动执行）

> 本目录只是配置样例；没有连接/修改任何生产服务器。
> 旧 `material/` 站点保持只读，新平台独立 vhost。

## 1. 目录

```text
/var/www/material/
  platform/                 # 当前 release（可指向 releases/<id>）
  platform/releases/        # 不可变发布副本
  platform/storage/         # 可写：logs/uploads/checkpoints
```

- Nginx `root` 只指向 `platform/public`。
- `storage/` 不在 public 下；Nginx 额外 deny。
- 不覆盖旧站点 DocumentRoot。

## 2. 环境变量

```text
APP_ENV=production
DB_DSN=mysql:host=...;dbname=mats_platform;charset=utf8mb4
DB_USER=...
DB_PASS=...
CATALOG_SOURCE=db
```

- 不把密码写入仓库/镜像层。
- 用 systemd EnvironmentFile 或容器 secret。

## 3. 部署

1. 上传/解压 release 到 `platform/releases/<id>`。
2. `chown -R www-data:www-data platform/storage`
3. `chmod -R 750 platform/storage`
4. 校验：
   ```bash
   php platform/bin/verify.php
   php platform/bin/release-check.php
   ```
5. 切换 `platform` 符号链接到新 release（或改 vhost root）。
6. `nginx -t && systemctl reload nginx`
7. `curl -f https://material.example.com/health`

## 4. 回滚

- 切回上一个 release 符号链接/目录。
- **不删除**失败 release；保留日志与 manifest。
- 数据库不删除；必要时切回旧 DSN。

## 5. 健康检查 / 备份

```bash
php platform/bin/healthcheck.php --url=https://material.example.com/health
php platform/bin/db-backup.php --execute
```

- 备份到 `platform/storage/backups/`，不覆盖旧文件。
- 健康检查失败立即告警；备份需定期恢复演练。
- 详见 `docs/BACKUP.md`。

## 6. 禁止

- 不用 `mod_php` 上公网。
- 不把 `platform/storage` 放进 public。
- 不在生产执行 `git reset --hard`。
- 不在未备份时跑迁移。
- 不删除旧 `material/`。

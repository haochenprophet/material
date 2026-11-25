# 文件上传 / 对象存储抽象

> 首版只支持 BOM 的 CSV/TXT，本地磁盘存储；**未接对象存储/CDN/病毒扫描**。
> 上传接口目前无鉴权（仅本地演示），生产必须加登录 + 限流 + 病毒扫描。

## 1. UI

`/bom` 页面：

- 选择 `.csv/.txt`
- `POST /bom/upload`（带 CSRF）
- 解析成功：闪存“已解析 N 行 + 原件 key”
- 失败：显示第一条错误（CSRF/权限/格式/数量）

只有 `bom.upload` 权限（buyer/seller/admin）可提交；未登录跳登录。

## 2. API

`POST /api/v1/bom/upload`，`multipart/form-data`，字段 `file`：

```powershell
curl.exe -X POST -F "file=@bom.csv;type=text/csv" `
  http://127.0.0.1:8091/api/v1/bom/upload
```

成功：

```json
{"data":{"ok":true,"count":2,"key":"2026/11/xxxx.csv","errors":[]}}
```

失败：422，`error.code` = `BAD_EXTENSION` / `FILE_TOO_LARGE` / `NO_FILE` / `PARSE_FAILED` / `UPLOAD_ERROR`。

## 3. 限制与存储

| 项 | 规则 |
|---|---|
| 大小 | ≤1MB |
| 扩展 | `.csv` / `.txt` |
| 行数 | ≤500 |
| 存储 key | `YYYY/MM/<16字节随机>.csv` |
| 存储位置 | `platform/storage/uploads/` |
| 覆盖 | 不覆盖；随机 key |
| 删除 | 首版不提供删除 |

## 4. 代码

- `app/Storage/Storage.php`（接口）
- `app/Storage/LocalStorage.php`（本地实现）
- `app/Storage/BomUploadService.php`（校验/解析/存储）
- `framework/components/CuiFileDrop.php`（上传 UI）
- `tests/upload.php`（10 条）

## 5. 生产 TODO

1. 对象存储（S3/OSS）预签名直传；服务端只接收 key。
2. 病毒扫描（ClamAV）与 MIME/魔数双校验；禁止 SVG/HTML。
3. 登录 + 租户隔离 + 上传限流（每用户/每 IP）。
4. `.xlsx` 用 PhpSpreadsheet，异步队列；限制 10MB/500 行。
5. 原文件哈希 + 解析结果一起存，支持追溯/回滚。
6. 定期清理孤儿文件（单独任务，不在请求内删除）。

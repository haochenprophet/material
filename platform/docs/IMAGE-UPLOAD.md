# 物料图片上传

## 能力

- 上传 JPG/PNG/WebP，≤5MB，最小 16×16
- MIME 白名单 + `getimagesize` 校验
- 本地存储：`public/uploads/materials/YYYY/MM/<random>.<ext>`
- 写入 `material_attachment`（type=image）
- 主图切换、排序、软删 `deleted_at`
- 软删不删文件；主图被删后自动选剩余第一张

## 接口

- `GET /seller/images?material_id=1`
- `POST /seller/images/{materialId}/upload`（multipart 字段 image，可选 is_primary）
- `POST /seller/images/{materialId}/{imageId}/primary`
- `POST /seller/images/{materialId}/{imageId}/delete`

## 代码

- `ImageStorage` / `LocalImageStorage`
- `ImageUploadService`
- `PdoMaterialImageRepository`
- `views/pages/seller-images.php`

## 测试

- `tests/image-upload.php` 8 条（上传/主图/软删/非图片拒绝）
- 实测 8095：上传 PNG → `/uploads/materials/...` 200；软删后主图回退

## 边界

- 未接对象存储；`ImageStorage` 可替换为 S3/OSS 实现
- 未接病毒扫描；生产需 ClamAV/对象存储回调
- 未做图片裁剪/压缩；5MB 原图直存

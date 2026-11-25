-- 010 material image upload support
-- Additive: soft delete for material_attachment.
-- Caller selects the target database.

ALTER TABLE material_attachment
  ADD COLUMN deleted_at DATETIME(3) NULL AFTER created_at;

ALTER TABLE material_attachment
  ADD KEY idx_attachment_live (material_id, type, deleted_at);

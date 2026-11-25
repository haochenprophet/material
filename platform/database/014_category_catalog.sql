-- 014 category pages: link material to taxonomy keys (additive)
-- Caller selects target DB. No table count change.

ALTER TABLE material
  ADD COLUMN category_key VARCHAR(64) NULL AFTER category_id,
  ADD COLUMN subcategory_key VARCHAR(64) NULL AFTER category_key,
  ADD KEY idx_material_category_key (category_key),
  ADD KEY idx_material_subcategory_key (subcategory_key);

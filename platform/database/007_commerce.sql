-- 007 commerce: material provenance, comments, merchant profiles
-- Additive only. Target DB must be selected by the caller (mats_platform_next2).
-- No DROP / DELETE. Re-runnable via CREATE TABLE IF NOT EXISTS.

CREATE TABLE IF NOT EXISTS material_provenance (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  material_id BIGINT UNSIGNED NOT NULL,
  trace_code VARCHAR(64) NOT NULL COMMENT 'public trace code',
  source_type ENUM('manufacturer','authorized','broker','surplus','recycle') NOT NULL DEFAULT 'surplus',
  source_name VARCHAR(200) NOT NULL,
  supplier_org_id BIGINT UNSIGNED NULL,
  supplier_name VARCHAR(200) NULL,
  batch_no VARCHAR(100) NULL,
  country VARCHAR(64) NULL,
  purchase_date DATE NULL,
  warranty_days INT NOT NULL DEFAULT 30,
  certifications JSON NULL,
  metadata JSON NULL,
  created_at DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
  updated_at DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3) ON UPDATE CURRENT_TIMESTAMP(3),
  PRIMARY KEY (id),
  UNIQUE KEY uk_prov_material (material_id),
  UNIQUE KEY uk_prov_trace (trace_code),
  KEY idx_prov_source (source_type, source_name),
  CONSTRAINT fk_prov_material FOREIGN KEY (material_id) REFERENCES material (id) ON DELETE CASCADE,
  CONSTRAINT fk_prov_org FOREIGN KEY (supplier_org_id) REFERENCES org (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='material provenance';

CREATE TABLE IF NOT EXISTS material_comment (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  uuid CHAR(36) NOT NULL,
  material_id BIGINT UNSIGNED NOT NULL,
  listing_id BIGINT UNSIGNED NULL,
  parent_id BIGINT UNSIGNED NULL,
  user_id BIGINT UNSIGNED NULL,
  author_name VARCHAR(100) NOT NULL,
  author_role VARCHAR(32) NOT NULL DEFAULT 'buyer',
  content VARCHAR(2000) NOT NULL,
  status ENUM('visible','hidden','pending') NOT NULL DEFAULT 'visible',
  depth SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  path VARCHAR(255) NOT NULL DEFAULT '',
  created_at DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
  updated_at DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3) ON UPDATE CURRENT_TIMESTAMP(3),
  PRIMARY KEY (id),
  UNIQUE KEY uk_comment_uuid (uuid),
  KEY idx_comment_material (material_id, status, created_at),
  KEY idx_comment_parent (parent_id),
  KEY idx_comment_listing (listing_id),
  CONSTRAINT fk_comment_material FOREIGN KEY (material_id) REFERENCES material (id) ON DELETE CASCADE,
  CONSTRAINT fk_comment_parent FOREIGN KEY (parent_id) REFERENCES material_comment (id) ON DELETE RESTRICT,
  CONSTRAINT fk_comment_listing FOREIGN KEY (listing_id) REFERENCES listing (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='material comment tree';

CREATE TABLE IF NOT EXISTS merchant_profile (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  store_id BIGINT UNSIGNED NOT NULL,
  merchant_code VARCHAR(64) NOT NULL,
  contact_name VARCHAR(100) NULL,
  phone VARCHAR(32) NULL,
  email VARCHAR(191) NULL,
  address VARCHAR(255) NULL,
  banner VARCHAR(500) NULL,
  description TEXT NULL,
  status ENUM('active','paused','closed') NOT NULL DEFAULT 'active',
  created_at DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
  updated_at DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3) ON UPDATE CURRENT_TIMESTAMP(3),
  PRIMARY KEY (id),
  UNIQUE KEY uk_merchant_store (store_id),
  UNIQUE KEY uk_merchant_code (merchant_code),
  KEY idx_merchant_status (status),
  CONSTRAINT fk_merchant_store FOREIGN KEY (store_id) REFERENCES store (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='merchant profile for storefront';

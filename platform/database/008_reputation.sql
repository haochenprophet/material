-- 008 reputation: material reviews, merchant credit, credit events
-- Additive. Caller selects target database.

CREATE TABLE IF NOT EXISTS material_review (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  uuid CHAR(36) NOT NULL,
  material_id BIGINT UNSIGNED NOT NULL,
  listing_id BIGINT UNSIGNED NULL,
  store_id BIGINT UNSIGNED NULL,
  user_id BIGINT UNSIGNED NULL,
  reviewer_key VARCHAR(64) NULL,
  author_name VARCHAR(100) NOT NULL,
  author_role VARCHAR(32) NOT NULL DEFAULT 'buyer',
  rating TINYINT UNSIGNED NOT NULL,
  quality TINYINT UNSIGNED NOT NULL DEFAULT 5,
  price TINYINT UNSIGNED NOT NULL DEFAULT 5,
  delivery TINYINT UNSIGNED NOT NULL DEFAULT 5,
  service TINYINT UNSIGNED NOT NULL DEFAULT 5,
  content VARCHAR(2000) NOT NULL,
  images JSON NULL,
  verified_purchase TINYINT(1) NOT NULL DEFAULT 0,
  status ENUM('visible','hidden','pending') NOT NULL DEFAULT 'visible',
  moderation_note VARCHAR(500) NULL,
  moderated_by BIGINT UNSIGNED NULL,
  moderated_at DATETIME(3) NULL,
  helpful_count INT UNSIGNED NOT NULL DEFAULT 0,
  created_at DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
  updated_at DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3) ON UPDATE CURRENT_TIMESTAMP(3),
  PRIMARY KEY (id),
  UNIQUE KEY uk_review_uuid (uuid),
  UNIQUE KEY uk_review_user_listing (user_id, listing_id),
  UNIQUE KEY uk_review_reviewer_listing (reviewer_key, listing_id),
  KEY idx_review_material (material_id, status, created_at),
  KEY idx_review_store (store_id, status, created_at),
  KEY idx_review_listing (listing_id),
  CONSTRAINT fk_review_material FOREIGN KEY (material_id) REFERENCES material (id) ON DELETE CASCADE,
  CONSTRAINT fk_review_listing FOREIGN KEY (listing_id) REFERENCES listing (id) ON DELETE SET NULL,
  CONSTRAINT fk_review_store FOREIGN KEY (store_id) REFERENCES store (id) ON DELETE SET NULL,
  CONSTRAINT fk_review_user FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE SET NULL,
  CONSTRAINT chk_review_rating CHECK (rating BETWEEN 1 AND 5),
  CONSTRAINT chk_review_quality CHECK (quality BETWEEN 1 AND 5),
  CONSTRAINT chk_review_price CHECK (price BETWEEN 1 AND 5),
  CONSTRAINT chk_review_delivery CHECK (delivery BETWEEN 1 AND 5),
  CONSTRAINT chk_review_service CHECK (service BETWEEN 1 AND 5)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='material reviews';

CREATE TABLE IF NOT EXISTS material_credit (
  material_id BIGINT UNSIGNED NOT NULL,
  review_count INT UNSIGNED NOT NULL DEFAULT 0,
  avg_rating DECIMAL(3,2) NOT NULL DEFAULT 0.00,
  score SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  grade CHAR(3) NOT NULL DEFAULT 'NA',
  quality_avg DECIMAL(3,2) NOT NULL DEFAULT 0.00,
  price_avg DECIMAL(3,2) NOT NULL DEFAULT 0.00,
  delivery_avg DECIMAL(3,2) NOT NULL DEFAULT 0.00,
  service_avg DECIMAL(3,2) NOT NULL DEFAULT 0.00,
  positive_rate DECIMAL(5,2) NOT NULL DEFAULT 0.00,
  verified_count INT UNSIGNED NOT NULL DEFAULT 0,
  updated_at DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3) ON UPDATE CURRENT_TIMESTAMP(3),
  PRIMARY KEY (material_id),
  KEY idx_material_credit_score (score),
  CONSTRAINT fk_mcredit_material FOREIGN KEY (material_id) REFERENCES material (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='material credit aggregate';

CREATE TABLE IF NOT EXISTS merchant_credit (
  store_id BIGINT UNSIGNED NOT NULL,
  review_count INT UNSIGNED NOT NULL DEFAULT 0,
  avg_rating DECIMAL(3,2) NOT NULL DEFAULT 0.00,
  score SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  grade CHAR(3) NOT NULL DEFAULT 'NA',
  material_count INT UNSIGNED NOT NULL DEFAULT 0,
  listed_count INT UNSIGNED NOT NULL DEFAULT 0,
  verified_count INT UNSIGNED NOT NULL DEFAULT 0,
  positive_rate DECIMAL(5,2) NOT NULL DEFAULT 0.00,
  updated_at DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3) ON UPDATE CURRENT_TIMESTAMP(3),
  PRIMARY KEY (store_id),
  KEY idx_merchant_credit_score (score),
  CONSTRAINT fk_mercredit_store FOREIGN KEY (store_id) REFERENCES store (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='merchant credit aggregate';

CREATE TABLE IF NOT EXISTS credit_event (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  subject_type ENUM('material','merchant') NOT NULL,
  subject_id BIGINT UNSIGNED NOT NULL,
  event_type VARCHAR(32) NOT NULL,
  delta SMALLINT NOT NULL DEFAULT 0,
  score_before SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  score_after SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  metadata JSON NULL,
  created_at DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
  PRIMARY KEY (id),
  KEY idx_credit_event_subject (subject_type, subject_id, created_at),
  KEY idx_credit_event_type (event_type, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='credit score audit trail';

CREATE TABLE IF NOT EXISTS review_reply (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  review_id BIGINT UNSIGNED NOT NULL,
  store_id BIGINT UNSIGNED NULL,
  author_name VARCHAR(100) NOT NULL,
  content VARCHAR(1000) NOT NULL,
  created_at DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
  PRIMARY KEY (id),
  KEY idx_review_reply_review (review_id, created_at),
  CONSTRAINT fk_review_reply_review FOREIGN KEY (review_id) REFERENCES material_review (id) ON DELETE CASCADE,
  CONSTRAINT fk_review_reply_store FOREIGN KEY (store_id) REFERENCES store (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='merchant reply to review';

CREATE TABLE IF NOT EXISTS review_appeal (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  review_id BIGINT UNSIGNED NOT NULL,
  store_id BIGINT UNSIGNED NULL,
  reason VARCHAR(1000) NOT NULL,
  status ENUM('pending','accepted','rejected') NOT NULL DEFAULT 'pending',
  resolver_id BIGINT UNSIGNED NULL,
  resolved_at DATETIME(3) NULL,
  created_at DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
  PRIMARY KEY (id),
  KEY idx_appeal_review (review_id,status),
  KEY idx_appeal_store (store_id,status),
  CONSTRAINT fk_appeal_review FOREIGN KEY (review_id) REFERENCES material_review (id) ON DELETE CASCADE,
  CONSTRAINT fk_appeal_store FOREIGN KEY (store_id) REFERENCES store (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='merchant appeal';

-- 009 merchant KYC / onboarding
-- Additive; caller selects target database.

CREATE TABLE IF NOT EXISTS merchant_kyc (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  store_id BIGINT UNSIGNED NOT NULL,
  legal_name VARCHAR(200) NOT NULL,
  license_no VARCHAR(64) NOT NULL,
  tax_no VARCHAR(64) NULL,
  legal_person VARCHAR(100) NULL,
  contact_name VARCHAR(100) NULL,
  contact_phone VARCHAR(32) NULL,
  documents JSON NULL,
  deposit_amount DECIMAL(18,2) NOT NULL DEFAULT 0.00,
  agreement_version VARCHAR(32) NOT NULL,
  agreement_signed_at DATETIME(3) NULL,
  status ENUM('pending','verified','rejected','expired') NOT NULL DEFAULT 'pending',
  review_note VARCHAR(500) NULL,
  reviewed_by BIGINT UNSIGNED NULL,
  reviewed_at DATETIME(3) NULL,
  created_at DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
  updated_at DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3) ON UPDATE CURRENT_TIMESTAMP(3),
  PRIMARY KEY (id),
  UNIQUE KEY uk_kyc_store (store_id),
  KEY idx_kyc_status (status),
  CONSTRAINT fk_kyc_store FOREIGN KEY (store_id) REFERENCES store (id) ON DELETE CASCADE,
  CONSTRAINT chk_kyc_deposit CHECK (deposit_amount >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='merchant KYC / onboarding';

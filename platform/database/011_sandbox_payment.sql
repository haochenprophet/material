-- 011 sandbox payment: no real money, no provider SDK
-- Additive. Caller selects target database.

CREATE TABLE IF NOT EXISTS sandbox_payment (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  payment_no VARCHAR(32) NOT NULL,
  order_no VARCHAR(64) NOT NULL,
  channel ENUM('wechat','alipay','unionpay','bank','offline') NOT NULL DEFAULT 'wechat',
  amount DECIMAL(18,4) NOT NULL,
  currency CHAR(3) NOT NULL DEFAULT 'CNY',
  status ENUM('created','processing','succeeded','failed','refunded','partial_refunded') NOT NULL DEFAULT 'created',
  provider VARCHAR(32) NOT NULL DEFAULT 'sandbox',
  external_id VARCHAR(128) NULL,
  idempotency_key VARCHAR(128) NOT NULL,
  paid_at DATETIME(3) NULL,
  created_at DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
  updated_at DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3) ON UPDATE CURRENT_TIMESTAMP(3),
  PRIMARY KEY (id),
  UNIQUE KEY uk_sandbox_payment_no (payment_no),
  UNIQUE KEY uk_sandbox_payment_idem (idempotency_key),
  KEY idx_sandbox_payment_order (order_no),
  KEY idx_sandbox_payment_status (status),
  CONSTRAINT chk_sandbox_payment_amount CHECK (amount > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='sandbox payment';

CREATE TABLE IF NOT EXISTS sandbox_payment_split (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  payment_id BIGINT UNSIGNED NOT NULL,
  account_type ENUM('seller','platform_commission','platform_service','tax','other') NOT NULL,
  account_id BIGINT UNSIGNED NULL,
  amount DECIMAL(18,4) NOT NULL,
  status ENUM('pending','succeeded','failed','reversed') NOT NULL DEFAULT 'pending',
  created_at DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
  PRIMARY KEY (id),
  KEY idx_sandbox_split_payment (payment_id),
  CONSTRAINT fk_sandbox_split_payment FOREIGN KEY (payment_id) REFERENCES sandbox_payment (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='sandbox payment split';

CREATE TABLE IF NOT EXISTS sandbox_refund (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  refund_no VARCHAR(32) NOT NULL,
  payment_id BIGINT UNSIGNED NOT NULL,
  amount DECIMAL(18,4) NOT NULL,
  reason VARCHAR(255) NULL,
  status ENUM('created','succeeded','failed') NOT NULL DEFAULT 'succeeded',
  refunded_at DATETIME(3) NULL,
  created_at DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
  PRIMARY KEY (id),
  UNIQUE KEY uk_sandbox_refund_no (refund_no),
  KEY idx_sandbox_refund_payment (payment_id),
  CONSTRAINT fk_sandbox_refund_payment FOREIGN KEY (payment_id) REFERENCES sandbox_payment (id) ON DELETE CASCADE,
  CONSTRAINT chk_sandbox_refund_amount CHECK (amount > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='sandbox refund';

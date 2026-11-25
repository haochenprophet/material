-- 013 API token / rate limit
-- Additive; caller selects target database.

CREATE TABLE IF NOT EXISTS api_token (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  token_hash CHAR(64) NOT NULL,
  client_name VARCHAR(100) NOT NULL,
  scopes_json JSON NOT NULL,
  rate_limit INT UNSIGNED NOT NULL DEFAULT 60,
  status ENUM('active','revoked') NOT NULL DEFAULT 'active',
  created_at DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
  last_used_at DATETIME(3) NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uk_api_token_hash (token_hash),
  KEY idx_api_token_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='API tokens';

CREATE TABLE IF NOT EXISTS api_rate_limit (
  token_id BIGINT UNSIGNED NOT NULL,
  window_start BIGINT NOT NULL,
  count INT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (token_id, window_start),
  CONSTRAINT fk_api_rate_token FOREIGN KEY (token_id) REFERENCES api_token (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='API token rate limit';

-- 012 commission rule versioning
-- Additive; caller selects target database.

ALTER TABLE commission_rule
  ADD COLUMN rule_key VARCHAR(64) NOT NULL DEFAULT 'default' AFTER id,
  ADD COLUMN version INT UNSIGNED NOT NULL DEFAULT 1 AFTER rule_key,
  ADD COLUMN note VARCHAR(255) NULL AFTER status,
  ADD UNIQUE KEY uk_commission_version (rule_key, version);

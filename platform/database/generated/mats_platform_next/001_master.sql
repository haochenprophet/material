-- =============================================================================
-- 物格云 / MaterialGrid — 001 主数据与平台核心
-- MySQL 8.0.28+ / InnoDB / utf8mb4
-- 说明：本文件只建“身份 + 主数据 + 事件/审计”表；库存见 002，提报见 003，
--       交易/结算见 004；迁移见 005；种子数据见 006。
-- =============================================================================

CREATE DATABASE IF NOT EXISTS `mats_platform_next`
  DEFAULT CHARACTER SET utf8mb4
  DEFAULT COLLATE utf8mb4_0900_ai_ci;
USE `mats_platform_next`;

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 1;

-- -----------------------------------------------------------------------------
-- 1. 身份与组织
-- -----------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `user` (
  `id`            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `uuid`          CHAR(36)        NOT NULL,
  `email`         VARCHAR(190)    NULL,
  `phone`         VARCHAR(32)     NULL,
  `password_hash` VARCHAR(255)    NOT NULL,
  `display_name`  VARCHAR(100)    NOT NULL,
  `status`        ENUM('pending','active','disabled') NOT NULL DEFAULT 'pending',
  `last_login_at` DATETIME(3)     NULL,
  `created_at`    DATETIME(3)     NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
  `updated_at`    DATETIME(3)     NOT NULL DEFAULT CURRENT_TIMESTAMP(3) ON UPDATE CURRENT_TIMESTAMP(3),
  `deleted_at`    DATETIME(3)     NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_user_uuid` (`uuid`),
  UNIQUE KEY `uk_user_email` (`email`),
  UNIQUE KEY `uk_user_phone` (`phone`),
  KEY `idx_user_status` (`status`),
  KEY `idx_user_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='用户';

CREATE TABLE IF NOT EXISTS `user_identity` (
  `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`     BIGINT UNSIGNED NOT NULL,
  `type`        ENUM('id_card','passport','wechat','alipay','other') NOT NULL,
  `identifier`  VARCHAR(191)    NOT NULL,
  `verified_at` DATETIME(3)     NULL,
  `meta`        JSON            NULL,
  `created_at`  DATETIME(3)     NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_identity_type_identifier` (`type`,`identifier`),
  KEY `idx_identity_user` (`user_id`),
  CONSTRAINT `fk_identity_user` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='用户身份（实名/三方）';

CREATE TABLE IF NOT EXISTS `org` (
  `id`           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `uuid`         CHAR(36)        NOT NULL,
  `name`         VARCHAR(200)    NOT NULL,
  `type`         ENUM('individual','company','school','government','other') NOT NULL DEFAULT 'company',
  `tax_no`       VARCHAR(64)     NULL,
  `license_no`   VARCHAR(64)     NULL,
  `legal_person` VARCHAR(100)    NULL,
  `status`       ENUM('pending','active','disabled') NOT NULL DEFAULT 'pending',
  `verified_at`  DATETIME(3)     NULL,
  `created_at`   DATETIME(3)     NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
  `updated_at`   DATETIME(3)     NOT NULL DEFAULT CURRENT_TIMESTAMP(3) ON UPDATE CURRENT_TIMESTAMP(3),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_org_uuid` (`uuid`),
  UNIQUE KEY `uk_org_tax_no` (`tax_no`),
  KEY `idx_org_status` (`status`),
  KEY `idx_org_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='组织/企业';

CREATE TABLE IF NOT EXISTS `role` (
  `id`     BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `code`   VARCHAR(64)     NOT NULL,
  `name`   VARCHAR(100)    NOT NULL,
  `scope`  ENUM('platform','org','store','warehouse') NOT NULL DEFAULT 'org',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_role_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='角色';

CREATE TABLE IF NOT EXISTS `permission` (
  `id`     BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `code`   VARCHAR(100)    NOT NULL,
  `name`   VARCHAR(100)    NOT NULL,
  `module` VARCHAR(64)     NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_permission_code` (`code`),
  KEY `idx_permission_module` (`module`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='权限点';

CREATE TABLE IF NOT EXISTS `role_permission` (
  `role_id`       BIGINT UNSIGNED NOT NULL,
  `permission_id` BIGINT UNSIGNED NOT NULL,
  PRIMARY KEY (`role_id`,`permission_id`),
  CONSTRAINT `fk_rp_role` FOREIGN KEY (`role_id`) REFERENCES `role` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_rp_permission` FOREIGN KEY (`permission_id`) REFERENCES `permission` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='角色-权限';

CREATE TABLE IF NOT EXISTS `org_member` (
  `org_id`    BIGINT UNSIGNED NOT NULL,
  `user_id`   BIGINT UNSIGNED NOT NULL,
  `role_id`   BIGINT UNSIGNED NOT NULL,
  `status`    ENUM('invited','active','disabled') NOT NULL DEFAULT 'active',
  `joined_at` DATETIME(3)     NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
  PRIMARY KEY (`org_id`,`user_id`),
  KEY `idx_org_member_user` (`user_id`),
  CONSTRAINT `fk_org_member_org` FOREIGN KEY (`org_id`) REFERENCES `org` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_org_member_user` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_org_member_role` FOREIGN KEY (`role_id`) REFERENCES `role` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='组织成员';

CREATE TABLE IF NOT EXISTS `verification` (
  `id`           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `org_id`       BIGINT UNSIGNED NOT NULL,
  `type`         ENUM('company','individual','store','category') NOT NULL,
  `status`       ENUM('draft','submitted','approved','rejected','expired') NOT NULL DEFAULT 'draft',
  `files`        JSON            NULL,
  `remark`       VARCHAR(500)    NULL,
  `reviewer_id`  BIGINT UNSIGNED NULL,
  `submitted_at` DATETIME(3)     NULL,
  `reviewed_at`  DATETIME(3)     NULL,
  `created_at`   DATETIME(3)     NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
  PRIMARY KEY (`id`),
  KEY `idx_verification_org` (`org_id`,`status`),
  CONSTRAINT `fk_verification_org` FOREIGN KEY (`org_id`) REFERENCES `org` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='认证/资质';

CREATE TABLE IF NOT EXISTS `address` (
  `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `org_id`      BIGINT UNSIGNED NOT NULL,
  `user_id`     BIGINT UNSIGNED NULL,
  `contact`     VARCHAR(64)     NOT NULL,
  `phone`       VARCHAR(32)     NOT NULL,
  `province`    VARCHAR(64)     NOT NULL,
  `city`        VARCHAR(64)     NOT NULL,
  `district`    VARCHAR(64)     NULL,
  `detail`      VARCHAR(255)    NOT NULL,
  `postal_code` VARCHAR(16)     NULL,
  `is_default`  TINYINT(1)      NOT NULL DEFAULT 0,
  `created_at`  DATETIME(3)     NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
  PRIMARY KEY (`id`),
  KEY `idx_address_org` (`org_id`),
  CONSTRAINT `fk_address_org` FOREIGN KEY (`org_id`) REFERENCES `org` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='地址';

-- -----------------------------------------------------------------------------
-- 2. 类目、属性、单位、品牌、封装
-- -----------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `category` (
  `id`        BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `parent_id` BIGINT UNSIGNED NULL,
  `code`      VARCHAR(64)     NOT NULL,
  `name`      VARCHAR(100)    NOT NULL,
  `level`     TINYINT UNSIGNED NOT NULL DEFAULT 1,
  `path`      VARCHAR(255)    NOT NULL DEFAULT '/',
  `sort`      INT             NOT NULL DEFAULT 0,
  `status`    ENUM('active','disabled') NOT NULL DEFAULT 'active',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_category_code` (`code`),
  KEY `idx_category_parent` (`parent_id`),
  KEY `idx_category_path` (`path`),
  CONSTRAINT `fk_category_parent` FOREIGN KEY (`parent_id`) REFERENCES `category` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='类目树';

CREATE TABLE IF NOT EXISTS `attr_def` (
  `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `category_id` BIGINT UNSIGNED NOT NULL,
  `code`        VARCHAR(64)     NOT NULL,
  `name`        VARCHAR(100)    NOT NULL,
  `data_type`   ENUM('text','number','enum','bool','date') NOT NULL DEFAULT 'text',
  `unit`        VARCHAR(32)     NULL,
  `required`    TINYINT(1)      NOT NULL DEFAULT 0,
  `searchable`  TINYINT(1)      NOT NULL DEFAULT 0,
  `sort`        INT             NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_attr_def` (`category_id`,`code`),
  CONSTRAINT `fk_attr_category` FOREIGN KEY (`category_id`) REFERENCES `category` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='属性定义';

CREATE TABLE IF NOT EXISTS `attr_option` (
  `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `attr_def_id` BIGINT UNSIGNED NOT NULL,
  `value`       VARCHAR(100)    NOT NULL,
  `label`       VARCHAR(100)    NOT NULL,
  `sort`        INT             NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_attr_option` (`attr_def_id`,`value`),
  CONSTRAINT `fk_attr_option_def` FOREIGN KEY (`attr_def_id`) REFERENCES `attr_def` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='属性枚举';

CREATE TABLE IF NOT EXISTS `uom` (
  `id`              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `code`            VARCHAR(16)     NOT NULL,
  `name`            VARCHAR(32)     NOT NULL,
  `precision_scale` TINYINT UNSIGNED NOT NULL DEFAULT 2,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_uom_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='计量单位';

CREATE TABLE IF NOT EXISTS `brand` (
  `id`      BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name`    VARCHAR(191)    NOT NULL,
  `name_en` VARCHAR(191)    NULL,
  `country` VARCHAR(64)     NULL,
  `logo`    VARCHAR(500)    NULL,
  `status`  ENUM('active','disabled') NOT NULL DEFAULT 'active',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_brand_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='品牌';

CREATE TABLE IF NOT EXISTS `manufacturer` (
  `id`      BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name`    VARCHAR(191)    NOT NULL,
  `name_en` VARCHAR(191)    NULL,
  `country` VARCHAR(64)     NULL,
  `status`  ENUM('active','disabled') NOT NULL DEFAULT 'active',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_manufacturer_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='制造商';

CREATE TABLE IF NOT EXISTS `package` (
  `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `code`        VARCHAR(64)     NOT NULL,
  `name`        VARCHAR(100)    NOT NULL,
  `description` VARCHAR(255)    NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_package_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='封装';

-- -----------------------------------------------------------------------------
-- 3. 物料主数据
-- -----------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `material` (
  `id`               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `uuid`             CHAR(36)        NOT NULL,
  `legacy_id`        BIGINT UNSIGNED NULL COMMENT '旧 materiel.idmateriel',
  `code`             VARCHAR(64)     NOT NULL COMMENT '物料编码',
  `part_number`      VARCHAR(191)    NOT NULL COMMENT '料号/MPN',
  `name`             VARCHAR(200)    NOT NULL,
  `description`      TEXT            NULL,
  `category_id`      BIGINT UNSIGNED NOT NULL DEFAULT 99,
  `uom_id`           BIGINT UNSIGNED NOT NULL DEFAULT 1,
  `brand_id`         BIGINT UNSIGNED NOT NULL DEFAULT 1,
  `manufacturer_id`  BIGINT UNSIGNED NOT NULL DEFAULT 1,
  `package_id`       BIGINT UNSIGNED NOT NULL DEFAULT 1,
  `barcode`          VARCHAR(64)     NULL,
  `lifecycle_status` ENUM('draft','active','nrnd','eol','obsolete') NOT NULL DEFAULT 'draft',
  `datasheet_status` ENUM('available','missing','path_only','unknown') NOT NULL DEFAULT 'unknown',
  `datasheet_url`    VARCHAR(500)    NULL,
  `rohs`             ENUM('yes','no','unknown') NOT NULL DEFAULT 'unknown',
  `reach`            ENUM('yes','no','unknown') NOT NULL DEFAULT 'unknown',
  `weight_g`         DECIMAL(18,4)   NULL,
  `dimensions`       VARCHAR(255)    NULL COMMENT '长x宽x高 mm',
  `attributes_summary` JSON          NULL,
  `created_by`       BIGINT UNSIGNED NULL,
  `created_at`       DATETIME(3)     NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
  `updated_at`       DATETIME(3)     NOT NULL DEFAULT CURRENT_TIMESTAMP(3) ON UPDATE CURRENT_TIMESTAMP(3),
  `deleted_at`       DATETIME(3)     NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_material_uuid` (`uuid`),
  UNIQUE KEY `uk_material_code` (`code`),
  UNIQUE KEY `uk_material_legacy` (`legacy_id`),
  UNIQUE KEY `uk_material_part` (`brand_id`,`part_number`,`package_id`),
  KEY `idx_material_category` (`category_id`),
  KEY `idx_material_part_number` (`part_number`),
  KEY `idx_material_lifecycle` (`lifecycle_status`),
  KEY `idx_material_name` (`name`),
  FULLTEXT KEY `ft_material` (`name`,`description`) WITH PARSER ngram,
  CONSTRAINT `fk_material_category` FOREIGN KEY (`category_id`) REFERENCES `category` (`id`),
  CONSTRAINT `fk_material_uom` FOREIGN KEY (`uom_id`) REFERENCES `uom` (`id`),
  CONSTRAINT `fk_material_brand` FOREIGN KEY (`brand_id`) REFERENCES `brand` (`id`),
  CONSTRAINT `fk_material_manufacturer` FOREIGN KEY (`manufacturer_id`) REFERENCES `manufacturer` (`id`),
  CONSTRAINT `fk_material_package` FOREIGN KEY (`package_id`) REFERENCES `package` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='物料主数据';

CREATE TABLE IF NOT EXISTS `material_attr` (
  `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `material_id` BIGINT UNSIGNED NOT NULL,
  `attr_def_id` BIGINT UNSIGNED NOT NULL,
  `value_text`  VARCHAR(500)    NULL,
  `value_num`   DECIMAL(18,6)   NULL,
  `value_unit`  VARCHAR(32)     NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_material_attr` (`material_id`,`attr_def_id`),
  KEY `idx_material_attr_def` (`attr_def_id`),
  CONSTRAINT `fk_material_attr_material` FOREIGN KEY (`material_id`) REFERENCES `material` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_material_attr_def` FOREIGN KEY (`attr_def_id`) REFERENCES `attr_def` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='物料属性值';

CREATE TABLE IF NOT EXISTS `material_alias` (
  `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `material_id` BIGINT UNSIGNED NOT NULL,
  `alias`       VARCHAR(191)    NOT NULL,
  `source`      VARCHAR(32)     NOT NULL DEFAULT 'manual',
  `created_at`  DATETIME(3)     NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_material_alias` (`material_id`,`alias`),
  KEY `idx_alias` (`alias`),
  CONSTRAINT `fk_alias_material` FOREIGN KEY (`material_id`) REFERENCES `material` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='料号别名';

CREATE TABLE IF NOT EXISTS `material_substitute` (
  `id`                     BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `material_id`            BIGINT UNSIGNED NOT NULL,
  `substitute_material_id` BIGINT UNSIGNED NOT NULL,
  `type`                   ENUM('pin_to_pin','same_spec','functional','needs_validation') NOT NULL DEFAULT 'needs_validation',
  `note`                   VARCHAR(500)    NULL,
  `status`                 ENUM('active','disabled') NOT NULL DEFAULT 'active',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_substitute` (`material_id`,`substitute_material_id`,`type`),
  CONSTRAINT `fk_substitute_material` FOREIGN KEY (`material_id`) REFERENCES `material` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_substitute_target` FOREIGN KEY (`substitute_material_id`) REFERENCES `material` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='替代料';

CREATE TABLE IF NOT EXISTS `material_attachment` (
  `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `material_id` BIGINT UNSIGNED NOT NULL,
  `type`        ENUM('image','video','datasheet','drawing','certificate','other') NOT NULL DEFAULT 'other',
  `path`        VARCHAR(500)    NOT NULL,
  `mime`        VARCHAR(100)    NULL,
  `size`        BIGINT UNSIGNED NULL,
  `sha256`      CHAR(64)        NULL,
  `is_primary`  TINYINT(1)      NOT NULL DEFAULT 0,
  `sort`        INT             NOT NULL DEFAULT 0,
  `created_at`  DATETIME(3)     NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
  PRIMARY KEY (`id`),
  KEY `idx_attachment_material` (`material_id`,`type`),
  CONSTRAINT `fk_attachment_material` FOREIGN KEY (`material_id`) REFERENCES `material` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='物料附件';

-- -----------------------------------------------------------------------------
-- 4. 供应商
-- -----------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `supplier` (
  `id`           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `code`         VARCHAR(64)     NOT NULL,
  `name`         VARCHAR(200)    NOT NULL,
  `type`         ENUM('manufacturer','distributor','broker','service','other') NOT NULL DEFAULT 'distributor',
  `tax_no`       VARCHAR(64)     NULL,
  `contact`      VARCHAR(64)     NULL,
  `phone`        VARCHAR(32)     NULL,
  `email`        VARCHAR(190)    NULL,
  `currency`     CHAR(3)         NOT NULL DEFAULT 'CNY',
  `payment_term` VARCHAR(64)     NULL,
  `status`       ENUM('active','disabled') NOT NULL DEFAULT 'active',
  `created_at`   DATETIME(3)     NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
  `updated_at`   DATETIME(3)     NOT NULL DEFAULT CURRENT_TIMESTAMP(3) ON UPDATE CURRENT_TIMESTAMP(3),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_supplier_code` (`code`),
  KEY `idx_supplier_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='供应商';

CREATE TABLE IF NOT EXISTS `supplier_material` (
  `id`                  BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `supplier_id`         BIGINT UNSIGNED NOT NULL,
  `material_id`         BIGINT UNSIGNED NOT NULL,
  `supplier_part_number` VARCHAR(191)   NULL,
  `price`               DECIMAL(18,4)   NOT NULL DEFAULT 0,
  `currency`            CHAR(3)         NOT NULL DEFAULT 'CNY',
  `moq`                 DECIMAL(18,4)   NOT NULL DEFAULT 1,
  `lead_time_days`      INT             NOT NULL DEFAULT 0,
  `status`              ENUM('active','disabled') NOT NULL DEFAULT 'active',
  `updated_at`          DATETIME(3)     NOT NULL DEFAULT CURRENT_TIMESTAMP(3) ON UPDATE CURRENT_TIMESTAMP(3),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_supplier_material` (`supplier_id`,`material_id`,`supplier_part_number`),
  KEY `idx_supplier_material_material` (`material_id`),
  CONSTRAINT `fk_supplier_material_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `supplier` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_supplier_material_material` FOREIGN KEY (`material_id`) REFERENCES `material` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='供应商料号/价格';

-- -----------------------------------------------------------------------------
-- 5. 事件、Outbox、幂等、审计
-- -----------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `event_log` (
  `id`             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `event_type`     VARCHAR(64)     NOT NULL,
  `aggregate_type` VARCHAR(64)     NOT NULL,
  `aggregate_id`   VARCHAR(64)     NOT NULL,
  `payload`        JSON            NULL,
  `actor_id`       BIGINT UNSIGNED NULL,
  `actor_type`     ENUM('user','system','job','webhook') NOT NULL DEFAULT 'user',
  `request_id`     CHAR(36)        NULL,
  `occurred_at`    DATETIME(3)     NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
  `version`        INT UNSIGNED    NOT NULL DEFAULT 1,
  `created_at`     DATETIME(3)     NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
  PRIMARY KEY (`id`),
  KEY `idx_event_aggregate` (`aggregate_type`,`aggregate_id`,`id`),
  KEY `idx_event_type_time` (`event_type`,`occurred_at`),
  KEY `idx_event_request` (`request_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='业务事件（只追加）';

CREATE TABLE IF NOT EXISTS `outbox` (
  `id`           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `event_id`     BIGINT UNSIGNED NOT NULL,
  `status`       ENUM('pending','processing','done','failed') NOT NULL DEFAULT 'pending',
  `attempts`     INT UNSIGNED    NOT NULL DEFAULT 0,
  `available_at` DATETIME(3)     NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
  `last_error`   VARCHAR(500)    NULL,
  `created_at`   DATETIME(3)     NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
  `updated_at`   DATETIME(3)     NOT NULL DEFAULT CURRENT_TIMESTAMP(3) ON UPDATE CURRENT_TIMESTAMP(3),
  PRIMARY KEY (`id`),
  KEY `idx_outbox_status` (`status`,`available_at`),
  CONSTRAINT `fk_outbox_event` FOREIGN KEY (`event_id`) REFERENCES `event_log` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='事务外盒';

CREATE TABLE IF NOT EXISTS `idempotency_key` (
  `key`         VARCHAR(128)    NOT NULL,
  `scope`       VARCHAR(64)     NOT NULL,
  `response`    JSON            NULL,
  `created_at`  DATETIME(3)     NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
  `expires_at`  DATETIME(3)     NULL,
  PRIMARY KEY (`key`,`scope`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='幂等键';

CREATE TABLE IF NOT EXISTS `audit_log` (
  `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `actor_id`    BIGINT UNSIGNED NULL,
  `actor_type`  ENUM('user','system','job','api') NOT NULL DEFAULT 'user',
  `org_id`      BIGINT UNSIGNED NULL,
  `action`      VARCHAR(64)     NOT NULL,
  `object_type` VARCHAR(64)     NOT NULL,
  `object_id`   VARCHAR(64)     NOT NULL,
  `before_json` JSON            NULL,
  `after_json`  JSON            NULL,
  `ip`          VARCHAR(45)     NULL,
  `user_agent`  VARCHAR(255)    NULL,
  `request_id`  CHAR(36)        NULL,
  `created_at`  DATETIME(3)     NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
  PRIMARY KEY (`id`),
  KEY `idx_audit_object` (`object_type`,`object_id`,`id`),
  KEY `idx_audit_actor` (`actor_id`,`created_at`),
  KEY `idx_audit_action` (`action`,`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='审计日志（只追加）';

-- 完成
SELECT '001_master.sql done' AS result;

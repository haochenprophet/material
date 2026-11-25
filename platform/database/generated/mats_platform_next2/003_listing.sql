-- =============================================================================
-- 物格云 / MaterialGrid — 003 店铺、提报、质检、上架
-- 依赖：001_master.sql、002_inventory.sql
-- 原则：listing 是“可售单元”，与 material（标准物料）多对一；
--       成色/等级/批次/价格/库存都在 listing 维度表达。
-- =============================================================================
USE `mats_platform_next2`;

SET NAMES utf8mb4;

-- -----------------------------------------------------------------------------
-- 1. 店铺
-- -----------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `store` (
  `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `uuid`        CHAR(36)        NOT NULL,
  `org_id`      BIGINT UNSIGNED NOT NULL,
  `name`        VARCHAR(100)    NOT NULL,
  `slug`        VARCHAR(100)    NOT NULL,
  `logo`        VARCHAR(500)    NULL,
  `description` TEXT            NULL,
  `status`      ENUM('draft','pending','active','paused','closed') NOT NULL DEFAULT 'draft',
  `rating`      DECIMAL(3,2)    NOT NULL DEFAULT 0 COMMENT '0-5',
  `rating_count` INT UNSIGNED   NOT NULL DEFAULT 0,
  `created_at`  DATETIME(3)     NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
  `updated_at`  DATETIME(3)     NOT NULL DEFAULT CURRENT_TIMESTAMP(3) ON UPDATE CURRENT_TIMESTAMP(3),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_store_uuid` (`uuid`),
  UNIQUE KEY `uk_store_slug` (`slug`),
  KEY `idx_store_org` (`org_id`),
  KEY `idx_store_status` (`status`),
  CONSTRAINT `fk_store_org` FOREIGN KEY (`org_id`) REFERENCES `org` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='店铺';

-- -----------------------------------------------------------------------------
-- 2. 提报/商品
-- -----------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `listing` (
  `id`              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `uuid`            CHAR(36)        NOT NULL,
  `store_id`        BIGINT UNSIGNED NOT NULL,
  `material_id`     BIGINT UNSIGNED NOT NULL,
  `category_id`     BIGINT UNSIGNED NOT NULL,
  `title`           VARCHAR(200)    NOT NULL,
  `description`     TEXT            NULL,
  `item_condition`       ENUM('new','surplus','refurb','used','scrap') NOT NULL DEFAULT 'surplus',
  `grade`           ENUM('A','B','C','D','NA') NOT NULL DEFAULT 'NA',
  `refurb_scope`    VARCHAR(255)    NULL COMMENT '翻新范围（合规必填，若 condition=refurb）',
  `qc_status`       ENUM('not_required','pending','passed','failed') NOT NULL DEFAULT 'pending',
  `status`          ENUM('draft','pending_review','approved','rejected','listed','paused','sold_out','expired') NOT NULL DEFAULT 'draft',
  `price_mode`      ENUM('fixed','tier','inquiry') NOT NULL DEFAULT 'fixed',
  `price`           DECIMAL(18,4)   NULL,
  `currency`        CHAR(3)         NOT NULL DEFAULT 'CNY',
  `quantity`        DECIMAL(18,4)   NOT NULL DEFAULT 0,
  `moq`             DECIMAL(18,4)   NOT NULL DEFAULT 1,
  `uom_id`          BIGINT UNSIGNED NOT NULL DEFAULT 1,
  `warehouse_id`    BIGINT UNSIGNED NULL,
  `location_id`     BIGINT UNSIGNED NULL,
  `lead_time_days`  INT             NOT NULL DEFAULT 3,
  `warranty_days`   INT             NOT NULL DEFAULT 30,
  `certifications`  JSON            NULL COMMENT '3C/RoHS/REACH 等',
  `published_at`    DATETIME(3)     NULL,
  `expires_at`      DATETIME(3)     NULL,
  `created_by`      BIGINT UNSIGNED NULL,
  `created_at`      DATETIME(3)     NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
  `updated_at`      DATETIME(3)     NOT NULL DEFAULT CURRENT_TIMESTAMP(3) ON UPDATE CURRENT_TIMESTAMP(3),
  `deleted_at`      DATETIME(3)     NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_listing_uuid` (`uuid`),
  KEY `idx_listing_store` (`store_id`,`status`),
  KEY `idx_listing_material` (`material_id`),
  KEY `idx_listing_category` (`category_id`,`status`),
  KEY `idx_listing_status` (`status`,`published_at`),
  FULLTEXT KEY `ft_listing` (`title`,`description`) WITH PARSER ngram,
  CONSTRAINT `fk_listing_store` FOREIGN KEY (`store_id`) REFERENCES `store` (`id`),
  CONSTRAINT `fk_listing_material` FOREIGN KEY (`material_id`) REFERENCES `material` (`id`),
  CONSTRAINT `fk_listing_category` FOREIGN KEY (`category_id`) REFERENCES `category` (`id`),
  CONSTRAINT `fk_listing_uom` FOREIGN KEY (`uom_id`) REFERENCES `uom` (`id`),
  CONSTRAINT `fk_listing_warehouse` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouse` (`id`),
  CONSTRAINT `fk_listing_location` FOREIGN KEY (`location_id`) REFERENCES `location` (`id`),
  CONSTRAINT `chk_listing_qty` CHECK (`quantity` >= 0),
  CONSTRAINT `chk_listing_price` CHECK (`price` IS NULL OR `price` >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='提报/商品';

CREATE TABLE IF NOT EXISTS `listing_media` (
  `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `listing_id` BIGINT UNSIGNED NOT NULL,
  `type`       ENUM('image','video','datasheet','certificate','other') NOT NULL DEFAULT 'image',
  `path`       VARCHAR(500)    NOT NULL,
  `mime`       VARCHAR(100)    NULL,
  `size`       BIGINT UNSIGNED NULL,
  `sha256`     CHAR(64)        NULL,
  `is_primary` TINYINT(1)      NOT NULL DEFAULT 0,
  `sort`       INT             NOT NULL DEFAULT 0,
  `created_at` DATETIME(3)     NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
  PRIMARY KEY (`id`),
  KEY `idx_listing_media` (`listing_id`,`type`),
  CONSTRAINT `fk_listing_media_listing` FOREIGN KEY (`listing_id`) REFERENCES `listing` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='提报媒体';

CREATE TABLE IF NOT EXISTS `listing_tier_price` (
  `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `listing_id` BIGINT UNSIGNED NOT NULL,
  `min_qty`    DECIMAL(18,4)   NOT NULL,
  `max_qty`    DECIMAL(18,4)   NULL,
  `price`      DECIMAL(18,4)   NOT NULL,
  `currency`   CHAR(3)         NOT NULL DEFAULT 'CNY',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_listing_tier` (`listing_id`,`min_qty`),
  CONSTRAINT `fk_listing_tier_listing` FOREIGN KEY (`listing_id`) REFERENCES `listing` (`id`) ON DELETE CASCADE,
  CONSTRAINT `chk_tier_price` CHECK (`price` >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='阶梯价';

CREATE TABLE IF NOT EXISTS `listing_stock` (
  `id`           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `listing_id`   BIGINT UNSIGNED NOT NULL,
  `lot_id`       BIGINT UNSIGNED NOT NULL DEFAULT 0,
  `warehouse_id` BIGINT UNSIGNED NOT NULL,
  `qty`          DECIMAL(18,4)   NOT NULL DEFAULT 0,
  `reserved`     DECIMAL(18,4)   NOT NULL DEFAULT 0,
  `updated_at`   DATETIME(3)     NOT NULL DEFAULT CURRENT_TIMESTAMP(3) ON UPDATE CURRENT_TIMESTAMP(3),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_listing_stock` (`listing_id`,`lot_id`,`warehouse_id`),
  KEY `idx_listing_stock_warehouse` (`warehouse_id`),
  CONSTRAINT `fk_listing_stock_listing` FOREIGN KEY (`listing_id`) REFERENCES `listing` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_listing_stock_warehouse` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouse` (`id`),
  CONSTRAINT `chk_listing_stock_qty` CHECK (`qty` >= 0 AND `reserved` >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='商品库存';

-- -----------------------------------------------------------------------------
-- 3. 审核与质检
-- -----------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `listing_review` (
  `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `listing_id`  BIGINT UNSIGNED NOT NULL,
  `reviewer_id` BIGINT UNSIGNED NULL,
  `action`      ENUM('submit','approve','reject','request_change','offline') NOT NULL,
  `reason`      VARCHAR(500)    NULL,
  `created_at`  DATETIME(3)     NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
  PRIMARY KEY (`id`),
  KEY `idx_listing_review` (`listing_id`,`created_at`),
  CONSTRAINT `fk_listing_review_listing` FOREIGN KEY (`listing_id`) REFERENCES `listing` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_listing_review_user` FOREIGN KEY (`reviewer_id`) REFERENCES `user` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='提报审核记录';

CREATE TABLE IF NOT EXISTS `qc_task` (
  `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `listing_id`  BIGINT UNSIGNED NOT NULL,
  `type`        ENUM('incoming','pre_listing','return','spot') NOT NULL DEFAULT 'pre_listing',
  `status`      ENUM('pending','assigned','in_progress','done','cancelled') NOT NULL DEFAULT 'pending',
  `assignee_id` BIGINT UNSIGNED NULL,
  `due_at`      DATETIME(3)     NULL,
  `remark`      VARCHAR(255)    NULL,
  `created_at`  DATETIME(3)     NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
  `updated_at`  DATETIME(3)     NOT NULL DEFAULT CURRENT_TIMESTAMP(3) ON UPDATE CURRENT_TIMESTAMP(3),
  PRIMARY KEY (`id`),
  KEY `idx_qc_task_status` (`status`,`due_at`),
  KEY `idx_qc_task_listing` (`listing_id`),
  CONSTRAINT `fk_qc_task_listing` FOREIGN KEY (`listing_id`) REFERENCES `listing` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_qc_task_user` FOREIGN KEY (`assignee_id`) REFERENCES `user` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='质检任务';

CREATE TABLE IF NOT EXISTS `qc_report` (
  `id`           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `qc_task_id`   BIGINT UNSIGNED NOT NULL,
  `listing_id`   BIGINT UNSIGNED NOT NULL,
  `result`       ENUM('pass','fail','conditional') NOT NULL,
  `grade`        ENUM('A','B','C','D','NA') NOT NULL DEFAULT 'NA',
  `items`        JSON            NULL COMMENT '检测项/结果/方法',
  `photos`       JSON            NULL,
  `report_url`   VARCHAR(500)    NULL,
  `inspector_id` BIGINT UNSIGNED NULL,
  `inspected_at` DATETIME(3)     NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
  `created_at`   DATETIME(3)     NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
  PRIMARY KEY (`id`),
  KEY `idx_qc_report_listing` (`listing_id`,`inspected_at`),
  CONSTRAINT `fk_qc_report_task` FOREIGN KEY (`qc_task_id`) REFERENCES `qc_task` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_qc_report_listing` FOREIGN KEY (`listing_id`) REFERENCES `listing` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_qc_report_user` FOREIGN KEY (`inspector_id`) REFERENCES `user` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='质检报告';

SELECT '003_listing.sql done' AS result;

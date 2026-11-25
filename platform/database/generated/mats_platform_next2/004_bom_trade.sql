-- =============================================================================
-- 物格云 / MaterialGrid — 004 BOM、寻料、报价、订单、支付、结算、发票
-- 依赖：001_master.sql、002_inventory.sql、003_listing.sql
-- 原则：订单/支付/结算/台账四套编号互链；平台只做分账指令的镜像账。
-- =============================================================================
USE `mats_platform_next2`;

SET NAMES utf8mb4;

-- -----------------------------------------------------------------------------
-- 1. 项目与 BOM
-- -----------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `project` (
  `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `uuid`       CHAR(36)        NOT NULL,
  `org_id`     BIGINT UNSIGNED NOT NULL,
  `user_id`    BIGINT UNSIGNED NULL,
  `code`       VARCHAR(32)     NOT NULL,
  `name`       VARCHAR(200)    NOT NULL,
  `status`     ENUM('active','archived') NOT NULL DEFAULT 'active',
  `created_at` DATETIME(3)     NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
  `updated_at` DATETIME(3)     NOT NULL DEFAULT CURRENT_TIMESTAMP(3) ON UPDATE CURRENT_TIMESTAMP(3),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_project_uuid` (`uuid`),
  UNIQUE KEY `uk_project_code` (`org_id`,`code`),
  KEY `idx_project_org` (`org_id`),
  CONSTRAINT `fk_project_org` FOREIGN KEY (`org_id`) REFERENCES `org` (`id`),
  CONSTRAINT `fk_project_user` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='项目';

CREATE TABLE IF NOT EXISTS `bom` (
  `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `uuid`        CHAR(36)        NOT NULL,
  `project_id`  BIGINT UNSIGNED NOT NULL,
  `name`        VARCHAR(200)    NOT NULL,
  `version`     VARCHAR(32)     NOT NULL DEFAULT 'v1',
  `source_file` VARCHAR(500)    NULL,
  `status`      ENUM('draft','parsing','ready','matching','quoted','archived') NOT NULL DEFAULT 'draft',
  `created_by`  BIGINT UNSIGNED NULL,
  `created_at`  DATETIME(3)     NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
  `updated_at`  DATETIME(3)     NOT NULL DEFAULT CURRENT_TIMESTAMP(3) ON UPDATE CURRENT_TIMESTAMP(3),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_bom_uuid` (`uuid`),
  UNIQUE KEY `uk_bom_version` (`project_id`,`version`),
  KEY `idx_bom_project` (`project_id`),
  CONSTRAINT `fk_bom_project` FOREIGN KEY (`project_id`) REFERENCES `project` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='BOM 头';

CREATE TABLE IF NOT EXISTS `bom_line` (
  `id`           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `bom_id`       BIGINT UNSIGNED NOT NULL,
  `line_no`      INT UNSIGNED    NOT NULL,
  `part_number`  VARCHAR(191)    NOT NULL,
  `brand_text`   VARCHAR(100)    NULL,
  `qty`          DECIMAL(18,4)   NOT NULL DEFAULT 1,
  `uom_text`     VARCHAR(32)     NULL,
  `designator`   VARCHAR(255)    NULL,
  `note`         VARCHAR(500)    NULL,
  `material_id`  BIGINT UNSIGNED NULL,
  `status`       ENUM('pending','matched','partial','shortage','substitute') NOT NULL DEFAULT 'pending',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_bom_line` (`bom_id`,`line_no`),
  KEY `idx_bom_line_material` (`material_id`),
  KEY `idx_bom_line_part` (`part_number`),
  CONSTRAINT `fk_bom_line_bom` FOREIGN KEY (`bom_id`) REFERENCES `bom` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_bom_line_material` FOREIGN KEY (`material_id`) REFERENCES `material` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='BOM 行';

CREATE TABLE IF NOT EXISTS `bom_upload` (
  `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `bom_id`      BIGINT UNSIGNED NOT NULL,
  `file_path`   VARCHAR(500)    NOT NULL,
  `mapping`     JSON            NULL,
  `parsed_rows` INT UNSIGNED    NOT NULL DEFAULT 0,
  `error_rows`  INT UNSIGNED    NOT NULL DEFAULT 0,
  `errors`      JSON            NULL,
  `status`      ENUM('uploaded','parsed','failed','confirmed') NOT NULL DEFAULT 'uploaded',
  `created_at`  DATETIME(3)     NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
  PRIMARY KEY (`id`),
  KEY `idx_bom_upload_bom` (`bom_id`),
  CONSTRAINT `fk_bom_upload_bom` FOREIGN KEY (`bom_id`) REFERENCES `bom` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='BOM 上传/解析';

-- -----------------------------------------------------------------------------
-- 2. 匹配
-- -----------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `match_run` (
  `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `bom_id`      BIGINT UNSIGNED NOT NULL,
  `status`      ENUM('queued','running','done','failed') NOT NULL DEFAULT 'queued',
  `started_at`  DATETIME(3)     NULL,
  `finished_at` DATETIME(3)     NULL,
  `stats`       JSON            NULL,
  `error`       VARCHAR(500)    NULL,
  PRIMARY KEY (`id`),
  KEY `idx_match_run_bom` (`bom_id`,`status`),
  CONSTRAINT `fk_match_run_bom` FOREIGN KEY (`bom_id`) REFERENCES `bom` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='匹配运行';

CREATE TABLE IF NOT EXISTS `match_result` (
  `id`              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `run_id`          BIGINT UNSIGNED NOT NULL,
  `bom_line_id`     BIGINT UNSIGNED NOT NULL,
  `source`          ENUM('self','seller','external','substitute') NOT NULL,
  `material_id`     BIGINT UNSIGNED NULL,
  `listing_id`      BIGINT UNSIGNED NULL,
  `supplier_id`     BIGINT UNSIGNED NULL,
  `price`           DECIMAL(18,4)   NOT NULL DEFAULT 0,
  `currency`        CHAR(3)         NOT NULL DEFAULT 'CNY',
  `qty_available`   DECIMAL(18,4)   NOT NULL DEFAULT 0,
  `lead_time_days`  INT             NOT NULL DEFAULT 0,
  `qc_grade`        ENUM('A','B','C','D','NA') NOT NULL DEFAULT 'NA',
  `score`           DECIMAL(8,4)    NOT NULL DEFAULT 0,
  `status`          ENUM('candidate','selected','rejected') NOT NULL DEFAULT 'candidate',
  `note`            VARCHAR(500)    NULL,
  PRIMARY KEY (`id`),
  KEY `idx_match_result_run` (`run_id`,`bom_line_id`,`score`),
  KEY `idx_match_result_material` (`material_id`),
  KEY `idx_match_result_listing` (`listing_id`),
  CONSTRAINT `fk_match_result_run` FOREIGN KEY (`run_id`) REFERENCES `match_run` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_match_result_line` FOREIGN KEY (`bom_line_id`) REFERENCES `bom_line` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_match_result_material` FOREIGN KEY (`material_id`) REFERENCES `material` (`id`),
  CONSTRAINT `fk_match_result_listing` FOREIGN KEY (`listing_id`) REFERENCES `listing` (`id`),
  CONSTRAINT `fk_match_result_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `supplier` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='匹配结果';

CREATE TABLE IF NOT EXISTS `supplier_offer` (
  `id`              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `supplier_id`     BIGINT UNSIGNED NOT NULL,
  `material_id`     BIGINT UNSIGNED NOT NULL,
  `price`           DECIMAL(18,4)   NOT NULL,
  `currency`        CHAR(3)         NOT NULL DEFAULT 'CNY',
  `qty`             DECIMAL(18,4)   NOT NULL DEFAULT 0,
  `lead_time_days`  INT             NOT NULL DEFAULT 0,
  `valid_until`     DATETIME(3)     NULL,
  `source`          ENUM('api','manual','contract') NOT NULL DEFAULT 'manual',
  `created_at`      DATETIME(3)     NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
  PRIMARY KEY (`id`),
  KEY `idx_supplier_offer_material` (`material_id`,`valid_until`),
  CONSTRAINT `fk_supplier_offer_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `supplier` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_supplier_offer_material` FOREIGN KEY (`material_id`) REFERENCES `material` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='外部供应报价';

-- -----------------------------------------------------------------------------
-- 3. 报价
-- -----------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `quote` (
  `id`           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `uuid`         CHAR(36)        NOT NULL,
  `quote_no`     VARCHAR(32)     NOT NULL,
  `org_id`       BIGINT UNSIGNED NOT NULL,
  `user_id`      BIGINT UNSIGNED NULL,
  `bom_id`       BIGINT UNSIGNED NULL,
  `status`       ENUM('draft','sent','accepted','rejected','expired','converted') NOT NULL DEFAULT 'draft',
  `currency`     CHAR(3)         NOT NULL DEFAULT 'CNY',
  `subtotal`     DECIMAL(18,4)   NOT NULL DEFAULT 0,
  `tax`          DECIMAL(18,4)   NOT NULL DEFAULT 0,
  `shipping`     DECIMAL(18,4)   NOT NULL DEFAULT 0,
  `service_fee`  DECIMAL(18,4)   NOT NULL DEFAULT 0,
  `total`        DECIMAL(18,4)   NOT NULL DEFAULT 0,
  `valid_until`  DATETIME(3)     NULL,
  `created_at`   DATETIME(3)     NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
  `updated_at`   DATETIME(3)     NOT NULL DEFAULT CURRENT_TIMESTAMP(3) ON UPDATE CURRENT_TIMESTAMP(3),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_quote_uuid` (`uuid`),
  UNIQUE KEY `uk_quote_no` (`quote_no`),
  KEY `idx_quote_org` (`org_id`,`created_at`),
  KEY `idx_quote_bom` (`bom_id`),
  CONSTRAINT `fk_quote_org` FOREIGN KEY (`org_id`) REFERENCES `org` (`id`),
  CONSTRAINT `fk_quote_user` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`),
  CONSTRAINT `fk_quote_bom` FOREIGN KEY (`bom_id`) REFERENCES `bom` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='报价单';

CREATE TABLE IF NOT EXISTS `quote_line` (
  `id`           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `quote_id`     BIGINT UNSIGNED NOT NULL,
  `bom_line_id`  BIGINT UNSIGNED NULL,
  `line_no`      INT UNSIGNED    NOT NULL,
  `material_id`  BIGINT UNSIGNED NULL,
  `listing_id`   BIGINT UNSIGNED NULL,
  `qty`          DECIMAL(18,4)   NOT NULL DEFAULT 0,
  `unit_price`   DECIMAL(18,4)   NOT NULL DEFAULT 0,
  `tax_rate`     DECIMAL(5,2)    NOT NULL DEFAULT 13.00,
  `amount`       DECIMAL(18,4)   NOT NULL DEFAULT 0,
  `status`       ENUM('available','substitute','shortage') NOT NULL DEFAULT 'available',
  `note`         VARCHAR(500)    NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_quote_line` (`quote_id`,`line_no`),
  KEY `idx_quote_line_material` (`material_id`),
  CONSTRAINT `fk_quote_line_quote` FOREIGN KEY (`quote_id`) REFERENCES `quote` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_quote_line_bom` FOREIGN KEY (`bom_line_id`) REFERENCES `bom_line` (`id`),
  CONSTRAINT `fk_quote_line_material` FOREIGN KEY (`material_id`) REFERENCES `material` (`id`),
  CONSTRAINT `fk_quote_line_listing` FOREIGN KEY (`listing_id`) REFERENCES `listing` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='报价行';

-- -----------------------------------------------------------------------------
-- 4. 订单
-- -----------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `sales_order` (
  `id`             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `uuid`           CHAR(36)        NOT NULL,
  `order_no`       VARCHAR(32)     NOT NULL,
  `quote_id`       BIGINT UNSIGNED NULL,
  `buyer_org_id`   BIGINT UNSIGNED NOT NULL,
  `buyer_user_id`  BIGINT UNSIGNED NULL,
  `seller_org_id`  BIGINT UNSIGNED NOT NULL,
  `store_id`       BIGINT UNSIGNED NULL,
  `status`         ENUM('pending_payment','paid','allocating','shipped','received','completed',
                        'cancelled','after_sales') NOT NULL DEFAULT 'pending_payment',
  `currency`       CHAR(3)         NOT NULL DEFAULT 'CNY',
  `subtotal`       DECIMAL(18,4)   NOT NULL DEFAULT 0,
  `tax`            DECIMAL(18,4)   NOT NULL DEFAULT 0,
  `shipping`       DECIMAL(18,4)   NOT NULL DEFAULT 0,
  `discount`       DECIMAL(18,4)   NOT NULL DEFAULT 0,
  `total`          DECIMAL(18,4)   NOT NULL DEFAULT 0,
  `paid_amount`    DECIMAL(18,4)   NOT NULL DEFAULT 0,
  `refund_amount`  DECIMAL(18,4)   NOT NULL DEFAULT 0,
  `paid_at`        DATETIME(3)     NULL,
  `completed_at`   DATETIME(3)     NULL,
  `remark`         VARCHAR(500)    NULL,
  `created_at`     DATETIME(3)     NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
  `updated_at`     DATETIME(3)     NOT NULL DEFAULT CURRENT_TIMESTAMP(3) ON UPDATE CURRENT_TIMESTAMP(3),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_order_uuid` (`uuid`),
  UNIQUE KEY `uk_order_no` (`order_no`),
  KEY `idx_order_buyer` (`buyer_org_id`,`status`,`created_at`),
  KEY `idx_order_seller` (`seller_org_id`,`status`,`created_at`),
  KEY `idx_order_quote` (`quote_id`),
  CONSTRAINT `fk_order_quote` FOREIGN KEY (`quote_id`) REFERENCES `quote` (`id`),
  CONSTRAINT `fk_order_buyer_org` FOREIGN KEY (`buyer_org_id`) REFERENCES `org` (`id`),
  CONSTRAINT `fk_order_buyer_user` FOREIGN KEY (`buyer_user_id`) REFERENCES `user` (`id`),
  CONSTRAINT `fk_order_seller_org` FOREIGN KEY (`seller_org_id`) REFERENCES `org` (`id`),
  CONSTRAINT `fk_order_store` FOREIGN KEY (`store_id`) REFERENCES `store` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='销售订单';

CREATE TABLE IF NOT EXISTS `sales_order_line` (
  `id`           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_id`     BIGINT UNSIGNED NOT NULL,
  `line_no`      INT UNSIGNED    NOT NULL,
  `material_id`  BIGINT UNSIGNED NOT NULL,
  `listing_id`   BIGINT UNSIGNED NULL,
  `qty`          DECIMAL(18,4)   NOT NULL,
  `unit_price`   DECIMAL(18,4)   NOT NULL,
  `tax_rate`     DECIMAL(5,2)    NOT NULL DEFAULT 13.00,
  `amount`       DECIMAL(18,4)   NOT NULL,
  `status`       ENUM('pending','allocated','shipped','received','cancelled','refunded') NOT NULL DEFAULT 'pending',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_order_line` (`order_id`,`line_no`),
  KEY `idx_order_line_material` (`material_id`),
  CONSTRAINT `fk_order_line_order` FOREIGN KEY (`order_id`) REFERENCES `sales_order` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_order_line_material` FOREIGN KEY (`material_id`) REFERENCES `material` (`id`),
  CONSTRAINT `fk_order_line_listing` FOREIGN KEY (`listing_id`) REFERENCES `listing` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='订单行';

CREATE TABLE IF NOT EXISTS `order_split` (
  `id`              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_id`        BIGINT UNSIGNED NOT NULL,
  `split_no`        VARCHAR(32)     NOT NULL,
  `seller_org_id`   BIGINT UNSIGNED NOT NULL,
  `store_id`        BIGINT UNSIGNED NULL,
  `amount`          DECIMAL(18,4)   NOT NULL DEFAULT 0,
  `status`          ENUM('pending','paid','shipped','received','settled','cancelled') NOT NULL DEFAULT 'pending',
  `created_at`      DATETIME(3)     NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_order_split_no` (`split_no`),
  KEY `idx_order_split_order` (`order_id`),
  CONSTRAINT `fk_order_split_order` FOREIGN KEY (`order_id`) REFERENCES `sales_order` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_order_split_seller` FOREIGN KEY (`seller_org_id`) REFERENCES `org` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='拆单';

-- -----------------------------------------------------------------------------
-- 5. 合同、支付、退款
-- -----------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `contract` (
  `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_id`    BIGINT UNSIGNED NOT NULL,
  `contract_no` VARCHAR(32)     NOT NULL,
  `type`        ENUM('sale','service','consignment','purchase') NOT NULL DEFAULT 'sale',
  `status`      ENUM('draft','signed','void') NOT NULL DEFAULT 'draft',
  `file_url`    VARCHAR(500)    NULL,
  `signed_at`   DATETIME(3)     NULL,
  `created_at`  DATETIME(3)     NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_contract_no` (`contract_no`),
  KEY `idx_contract_order` (`order_id`),
  CONSTRAINT `fk_contract_order` FOREIGN KEY (`order_id`) REFERENCES `sales_order` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='合同/电子签';

CREATE TABLE IF NOT EXISTS `payment` (
  `id`               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `payment_no`       VARCHAR(32)     NOT NULL,
  `order_id`         BIGINT UNSIGNED NOT NULL,
  `channel`          ENUM('wechat','alipay','unionpay','bank','offline') NOT NULL,
  `amount`           DECIMAL(18,4)   NOT NULL,
  `currency`         CHAR(3)         NOT NULL DEFAULT 'CNY',
  `status`           ENUM('created','processing','succeeded','failed','refunded','partial_refunded') NOT NULL DEFAULT 'created',
  `external_id`      VARCHAR(128)    NULL COMMENT '支付机构单号',
  `idempotency_key`  VARCHAR(128)    NOT NULL,
  `paid_at`          DATETIME(3)     NULL,
  `created_at`       DATETIME(3)     NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
  `updated_at`       DATETIME(3)     NOT NULL DEFAULT CURRENT_TIMESTAMP(3) ON UPDATE CURRENT_TIMESTAMP(3),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_payment_no` (`payment_no`),
  UNIQUE KEY `uk_payment_idem` (`idempotency_key`),
  KEY `idx_payment_order` (`order_id`),
  KEY `idx_payment_external` (`external_id`),
  CONSTRAINT `fk_payment_order` FOREIGN KEY (`order_id`) REFERENCES `sales_order` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='支付单';

CREATE TABLE IF NOT EXISTS `payment_split` (
  `id`           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `payment_id`   BIGINT UNSIGNED NOT NULL,
  `account_type` ENUM('seller','platform_commission','platform_service','logistics','tax','other') NOT NULL,
  `account_id`   BIGINT UNSIGNED NULL,
  `amount`       DECIMAL(18,4)   NOT NULL,
  `status`       ENUM('pending','succeeded','failed','reversed') NOT NULL DEFAULT 'pending',
  `external_id`  VARCHAR(128)    NULL,
  `created_at`   DATETIME(3)     NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
  PRIMARY KEY (`id`),
  KEY `idx_split_payment` (`payment_id`),
  CONSTRAINT `fk_split_payment` FOREIGN KEY (`payment_id`) REFERENCES `payment` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='分账明细';

CREATE TABLE IF NOT EXISTS `refund` (
  `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `refund_no`   VARCHAR(32)     NOT NULL,
  `payment_id`  BIGINT UNSIGNED NOT NULL,
  `order_id`    BIGINT UNSIGNED NOT NULL,
  `amount`      DECIMAL(18,4)   NOT NULL,
  `reason`      VARCHAR(255)    NULL,
  `status`      ENUM('created','processing','succeeded','failed') NOT NULL DEFAULT 'created',
  `refunded_at` DATETIME(3)     NULL,
  `created_at`  DATETIME(3)     NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_refund_no` (`refund_no`),
  KEY `idx_refund_payment` (`payment_id`),
  KEY `idx_refund_order` (`order_id`),
  CONSTRAINT `fk_refund_payment` FOREIGN KEY (`payment_id`) REFERENCES `payment` (`id`),
  CONSTRAINT `fk_refund_order` FOREIGN KEY (`order_id`) REFERENCES `sales_order` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='退款单';

-- -----------------------------------------------------------------------------
-- 6. 履约
-- -----------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `shipment` (
  `id`           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `shipment_no`  VARCHAR(32)     NOT NULL,
  `order_id`     BIGINT UNSIGNED NOT NULL,
  `type`         ENUM('seller_direct','platform','drop') NOT NULL DEFAULT 'seller_direct',
  `carrier`      VARCHAR(64)     NULL,
  `tracking_no`  VARCHAR(64)     NULL,
  `status`       ENUM('pending','shipped','in_transit','delivered','exception') NOT NULL DEFAULT 'pending',
  `shipped_at`   DATETIME(3)     NULL,
  `delivered_at` DATETIME(3)     NULL,
  `created_at`   DATETIME(3)     NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_shipment_no` (`shipment_no`),
  KEY `idx_shipment_order` (`order_id`),
  KEY `idx_shipment_tracking` (`tracking_no`),
  CONSTRAINT `fk_shipment_order` FOREIGN KEY (`order_id`) REFERENCES `sales_order` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='发货单';

CREATE TABLE IF NOT EXISTS `receipt` (
  `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_id`    BIGINT UNSIGNED NOT NULL,
  `received_by` BIGINT UNSIGNED NULL,
  `received_at` DATETIME(3)     NULL,
  `status`      ENUM('pending','received','rejected') NOT NULL DEFAULT 'pending',
  `remark`      VARCHAR(500)    NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_receipt_order` (`order_id`),
  CONSTRAINT `fk_receipt_order` FOREIGN KEY (`order_id`) REFERENCES `sales_order` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='收货/验收';

-- -----------------------------------------------------------------------------
-- 7. 发票
-- -----------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `invoice` (
  `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `invoice_no`  VARCHAR(32)     NULL,
  `order_id`    BIGINT UNSIGNED NULL,
  `type`        ENUM('goods','service') NOT NULL,
  `title`       VARCHAR(200)    NOT NULL,
  `tax_no`      VARCHAR(64)     NULL,
  `amount`      DECIMAL(18,4)   NOT NULL,
  `tax_rate`    DECIMAL(5,2)    NOT NULL,
  `tax_amount`  DECIMAL(18,4)   NOT NULL,
  `status`      ENUM('pending','issued','void','red') NOT NULL DEFAULT 'pending',
  `issued_at`   DATETIME(3)     NULL,
  `file_url`    VARCHAR(500)    NULL,
  `created_at`  DATETIME(3)     NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_invoice_no` (`invoice_no`),
  KEY `idx_invoice_order` (`order_id`),
  CONSTRAINT `fk_invoice_order` FOREIGN KEY (`order_id`) REFERENCES `sales_order` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='发票';

-- -----------------------------------------------------------------------------
-- 8. 结算、台账
-- -----------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `ledger_account` (
  `id`     BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `code`   VARCHAR(16)     NOT NULL,
  `name`   VARCHAR(100)    NOT NULL,
  `type`   ENUM('asset','liability','equity','revenue','expense') NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_ledger_account_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='会计科目';

CREATE TABLE IF NOT EXISTS `ledger_entry` (
  `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `entry_no`   VARCHAR(32)     NOT NULL,
  `line_no`    INT UNSIGNED    NOT NULL DEFAULT 1,
  `account_id` BIGINT UNSIGNED NOT NULL,
  `debit`      DECIMAL(18,4)   NOT NULL DEFAULT 0,
  `credit`     DECIMAL(18,4)   NOT NULL DEFAULT 0,
  `currency`   CHAR(3)         NOT NULL DEFAULT 'CNY',
  `biz_type`   VARCHAR(32)     NOT NULL,
  `biz_id`     VARCHAR(64)     NOT NULL,
  `event_id`   BIGINT UNSIGNED NULL,
  `created_at` DATETIME(3)     NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_ledger_entry` (`entry_no`,`line_no`),
  KEY `idx_ledger_account_time` (`account_id`,`created_at`),
  KEY `idx_ledger_biz` (`biz_type`,`biz_id`),
  CONSTRAINT `fk_ledger_account` FOREIGN KEY (`account_id`) REFERENCES `ledger_account` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='分录（镜像账）';

CREATE TABLE IF NOT EXISTS `commission_rule` (
  `id`             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `category_id`    BIGINT UNSIGNED NULL,
  `seller_type`    ENUM('individual','company','school','any') NOT NULL DEFAULT 'any',
  `rate`           DECIMAL(6,4)    NOT NULL COMMENT '例如 0.0200',
  `min_fee`        DECIMAL(18,4)   NOT NULL DEFAULT 0,
  `max_fee`        DECIMAL(18,4)   NULL,
  `effective_from` DATE            NOT NULL,
  `effective_to`   DATE            NULL,
  `status`         ENUM('active','disabled') NOT NULL DEFAULT 'active',
  PRIMARY KEY (`id`),
  KEY `idx_commission_category` (`category_id`,`effective_from`),
  CONSTRAINT `fk_commission_category` FOREIGN KEY (`category_id`) REFERENCES `category` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='佣金规则';

CREATE TABLE IF NOT EXISTS `settlement` (
  `id`             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `settlement_no`  VARCHAR(32)     NOT NULL,
  `seller_org_id`  BIGINT UNSIGNED NOT NULL,
  `period_start`   DATE            NOT NULL,
  `period_end`     DATE            NOT NULL,
  `status`         ENUM('pending','frozen','approved','paid','reconciled','cancelled') NOT NULL DEFAULT 'pending',
  `gross_amount`   DECIMAL(18,4)   NOT NULL DEFAULT 0,
  `commission`     DECIMAL(18,4)   NOT NULL DEFAULT 0,
  `service_fee`    DECIMAL(18,4)   NOT NULL DEFAULT 0,
  `refund_amount`  DECIMAL(18,4)   NOT NULL DEFAULT 0,
  `net_amount`     DECIMAL(18,4)   NOT NULL DEFAULT 0,
  `created_at`     DATETIME(3)     NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
  `paid_at`        DATETIME(3)     NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_settlement_no` (`settlement_no`),
  KEY `idx_settlement_seller` (`seller_org_id`,`status`,`period_end`),
  CONSTRAINT `fk_settlement_seller` FOREIGN KEY (`seller_org_id`) REFERENCES `org` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='结算单';

CREATE TABLE IF NOT EXISTS `settlement_line` (
  `id`            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `settlement_id` BIGINT UNSIGNED NOT NULL,
  `order_id`      BIGINT UNSIGNED NOT NULL,
  `order_line_id` BIGINT UNSIGNED NULL,
  `gross_amount`  DECIMAL(18,4)   NOT NULL DEFAULT 0,
  `commission`    DECIMAL(18,4)   NOT NULL DEFAULT 0,
  `service_fee`   DECIMAL(18,4)   NOT NULL DEFAULT 0,
  `refund_amount` DECIMAL(18,4)   NOT NULL DEFAULT 0,
  `net_amount`    DECIMAL(18,4)   NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_settlement_line_head` (`settlement_id`),
  KEY `idx_settlement_line_order` (`order_id`),
  CONSTRAINT `fk_settlement_line_head` FOREIGN KEY (`settlement_id`) REFERENCES `settlement` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_settlement_line_order` FOREIGN KEY (`order_id`) REFERENCES `sales_order` (`id`),
  CONSTRAINT `fk_settlement_line_order_line` FOREIGN KEY (`order_line_id`) REFERENCES `sales_order_line` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='结算明细';

CREATE TABLE IF NOT EXISTS `payout` (
  `id`            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `settlement_id` BIGINT UNSIGNED NOT NULL,
  `amount`        DECIMAL(18,4)   NOT NULL,
  `channel`       ENUM('bank','payment','offline') NOT NULL DEFAULT 'payment',
  `status`        ENUM('pending','processing','paid','failed') NOT NULL DEFAULT 'pending',
  `external_id`   VARCHAR(128)    NULL,
  `paid_at`       DATETIME(3)     NULL,
  `created_at`    DATETIME(3)     NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
  PRIMARY KEY (`id`),
  KEY `idx_payout_settlement` (`settlement_id`),
  CONSTRAINT `fk_payout_settlement` FOREIGN KEY (`settlement_id`) REFERENCES `settlement` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='放款';

CREATE TABLE IF NOT EXISTS `reconciliation` (
  `id`               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `type`             ENUM('payment','bank','inventory','order','invoice') NOT NULL,
  `biz_date`         DATE            NOT NULL,
  `platform_amount`  DECIMAL(18,4)   NOT NULL DEFAULT 0,
  `external_amount`  DECIMAL(18,4)   NOT NULL DEFAULT 0,
  `diff`             DECIMAL(18,4)   NOT NULL DEFAULT 0,
  `status`           ENUM('pending','matched','exception','resolved') NOT NULL DEFAULT 'pending',
  `remark`           VARCHAR(500)    NULL,
  `created_at`       DATETIME(3)     NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_reconciliation` (`type`,`biz_date`),
  KEY `idx_reconciliation_status` (`status`,`biz_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='对账';

-- -----------------------------------------------------------------------------
-- 9. 推流/广告
-- -----------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `promotion_campaign` (
  `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `store_id`    BIGINT UNSIGNED NOT NULL,
  `name`        VARCHAR(100)    NOT NULL,
  `type`        ENUM('cpc','cpm','cpt') NOT NULL DEFAULT 'cpc',
  `budget`      DECIMAL(18,4)   NOT NULL DEFAULT 0,
  `spent`       DECIMAL(18,4)   NOT NULL DEFAULT 0,
  `status`      ENUM('draft','active','paused','ended') NOT NULL DEFAULT 'draft',
  `start_at`    DATETIME(3)     NULL,
  `end_at`      DATETIME(3)     NULL,
  `created_at`  DATETIME(3)     NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
  PRIMARY KEY (`id`),
  KEY `idx_campaign_store` (`store_id`,`status`),
  CONSTRAINT `fk_campaign_store` FOREIGN KEY (`store_id`) REFERENCES `store` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='推广计划';

CREATE TABLE IF NOT EXISTS `ad_slot` (
  `id`      BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `code`    VARCHAR(64)     NOT NULL,
  `name`    VARCHAR(100)    NOT NULL,
  `type`    ENUM('search','category','home','detail') NOT NULL,
  `status`  ENUM('active','disabled') NOT NULL DEFAULT 'active',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_ad_slot_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='广告位';

CREATE TABLE IF NOT EXISTS `ad_event` (
  `id`           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `campaign_id`  BIGINT UNSIGNED NOT NULL,
  `slot_id`      BIGINT UNSIGNED NULL,
  `event_type`   ENUM('impression','click','conversion') NOT NULL,
  `listing_id`   BIGINT UNSIGNED NULL,
  `user_id`      BIGINT UNSIGNED NULL,
  `ip`           VARCHAR(45)     NULL,
  `user_agent`   VARCHAR(255)    NULL,
  `occurred_at`  DATETIME(3)     NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
  `cost`         DECIMAL(18,4)   NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_ad_event_campaign` (`campaign_id`,`occurred_at`),
  KEY `idx_ad_event_type` (`event_type`,`occurred_at`),
  CONSTRAINT `fk_ad_event_campaign` FOREIGN KEY (`campaign_id`) REFERENCES `promotion_campaign` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ad_event_slot` FOREIGN KEY (`slot_id`) REFERENCES `ad_slot` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='广告事件';

CREATE TABLE IF NOT EXISTS `ad_bill` (
  `id`           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `campaign_id`  BIGINT UNSIGNED NOT NULL,
  `biz_date`     DATE            NOT NULL,
  `impressions`  INT UNSIGNED    NOT NULL DEFAULT 0,
  `clicks`       INT UNSIGNED    NOT NULL DEFAULT 0,
  `amount`       DECIMAL(18,4)   NOT NULL DEFAULT 0,
  `status`       ENUM('pending','confirmed','paid','void') NOT NULL DEFAULT 'pending',
  `created_at`   DATETIME(3)     NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_ad_bill` (`campaign_id`,`biz_date`),
  CONSTRAINT `fk_ad_bill_campaign` FOREIGN KEY (`campaign_id`) REFERENCES `promotion_campaign` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='广告账单';

SELECT '004_bom_trade.sql done' AS result;

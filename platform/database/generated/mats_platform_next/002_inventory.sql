-- =============================================================================
-- 物格云 / MaterialGrid — 002 库存与仓储
-- 依赖：001_master.sql
-- 原则：stock_txn 是唯一事实来源；stock_balance 是汇总；余额只由事务更新。
-- =============================================================================
USE `mats_platform_next`;

SET NAMES utf8mb4;

-- -----------------------------------------------------------------------------
-- 1. 仓库与库位
-- -----------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `warehouse` (
  `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `code`       VARCHAR(32)     NOT NULL,
  `name`       VARCHAR(100)    NOT NULL,
  `type`       ENUM('platform','seller','partner','transit','virtual') NOT NULL DEFAULT 'platform',
  `org_id`     BIGINT UNSIGNED NULL COMMENT '所属组织；平台仓为空',
  `address`    VARCHAR(255)    NULL,
  `status`     ENUM('active','disabled') NOT NULL DEFAULT 'active',
  `created_at` DATETIME(3)     NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_warehouse_code` (`code`),
  KEY `idx_warehouse_org` (`org_id`),
  CONSTRAINT `fk_warehouse_org` FOREIGN KEY (`org_id`) REFERENCES `org` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='仓库';

CREATE TABLE IF NOT EXISTS `location` (
  `id`           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `warehouse_id` BIGINT UNSIGNED NOT NULL,
  `code`         VARCHAR(64)     NOT NULL,
  `name`         VARCHAR(100)    NULL,
  `zone`         VARCHAR(32)     NULL,
  `rack`         VARCHAR(32)     NULL,
  `level`        VARCHAR(32)     NULL,
  `bin`          VARCHAR(32)     NULL,
  `status`       ENUM('active','disabled') NOT NULL DEFAULT 'active',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_location_code` (`warehouse_id`,`code`),
  CONSTRAINT `fk_location_warehouse` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouse` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='库位';

-- -----------------------------------------------------------------------------
-- 2. 批次
-- -----------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `stock_lot` (
  `id`               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `material_id`      BIGINT UNSIGNED NOT NULL,
  `lot_no`           VARCHAR(64)     NOT NULL,
  `manufacture_date` DATE            NULL,
  `expiry_date`      DATE            NULL,
  `qc_status`        ENUM('pending','passed','failed','na') NOT NULL DEFAULT 'pending',
  `grade`            ENUM('A','B','C','D','NA') NOT NULL DEFAULT 'NA',
  `unit_cost`        DECIMAL(18,4)   NOT NULL DEFAULT 0,
  `currency`         CHAR(3)         NOT NULL DEFAULT 'CNY',
  `supplier_id`      BIGINT UNSIGNED NULL,
  `status`           ENUM('active','hold','scrap') NOT NULL DEFAULT 'active',
  `created_at`       DATETIME(3)     NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_lot` (`material_id`,`lot_no`),
  KEY `idx_lot_supplier` (`supplier_id`),
  CONSTRAINT `fk_lot_material` FOREIGN KEY (`material_id`) REFERENCES `material` (`id`),
  CONSTRAINT `fk_lot_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `supplier` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='批次';

-- -----------------------------------------------------------------------------
-- 3. 库存流水（唯一事实来源）
-- -----------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `stock_txn` (
  `id`           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `material_id`  BIGINT UNSIGNED NOT NULL,
  `warehouse_id` BIGINT UNSIGNED NOT NULL,
  `location_id`  BIGINT UNSIGNED NULL,
  `lot_id`       BIGINT UNSIGNED NULL,
  `txn_type`     ENUM('OPENING','RECEIPT','ISSUE','TRANSFER_IN','TRANSFER_OUT',
                      'ADJUST','COUNT','SCRAP','RETURN','RESERVE','RELEASE') NOT NULL,
  `qty`          DECIMAL(18,4)   NOT NULL COMMENT '有符号：入库正、出库负',
  `unit_cost`    DECIMAL(18,4)   NOT NULL DEFAULT 0,
  `ref_type`     VARCHAR(32)     NOT NULL,
  `ref_id`       VARCHAR(64)     NOT NULL,
  `line_no`      INT UNSIGNED    NOT NULL DEFAULT 1,
  `operator_id`  BIGINT UNSIGNED NULL,
  `occurred_at`  DATETIME(3)     NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
  `remark`       VARCHAR(255)    NULL,
  `created_at`   DATETIME(3)     NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_txn_ref` (`ref_type`,`ref_id`,`line_no`,`txn_type`),
  KEY `idx_txn_material_time` (`material_id`,`occurred_at`),
  KEY `idx_txn_warehouse` (`warehouse_id`,`occurred_at`),
  KEY `idx_txn_lot` (`lot_id`),
  KEY `idx_txn_ref` (`ref_type`,`ref_id`),
  CONSTRAINT `fk_txn_material` FOREIGN KEY (`material_id`) REFERENCES `material` (`id`),
  CONSTRAINT `fk_txn_warehouse` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouse` (`id`),
  CONSTRAINT `fk_txn_location` FOREIGN KEY (`location_id`) REFERENCES `location` (`id`),
  CONSTRAINT `fk_txn_lot` FOREIGN KEY (`lot_id`) REFERENCES `stock_lot` (`id`),
  CONSTRAINT `fk_txn_operator` FOREIGN KEY (`operator_id`) REFERENCES `user` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='库存事务流水';

-- -----------------------------------------------------------------------------
-- 4. 库存余额（汇总）
-- -----------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `stock_balance` (
  `material_id`  BIGINT UNSIGNED NOT NULL,
  `warehouse_id` BIGINT UNSIGNED NOT NULL,
  `lot_id`       BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '0=无批次',
  `on_hand`      DECIMAL(18,4)   NOT NULL DEFAULT 0,
  `reserved`     DECIMAL(18,4)   NOT NULL DEFAULT 0,
  `updated_at`   DATETIME(3)     NOT NULL DEFAULT CURRENT_TIMESTAMP(3) ON UPDATE CURRENT_TIMESTAMP(3),
  PRIMARY KEY (`material_id`,`warehouse_id`,`lot_id`),
  KEY `idx_balance_warehouse` (`warehouse_id`),
  KEY `idx_balance_material` (`material_id`),
  CONSTRAINT `fk_balance_material` FOREIGN KEY (`material_id`) REFERENCES `material` (`id`),
  CONSTRAINT `fk_balance_warehouse` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouse` (`id`),
  CONSTRAINT `chk_balance_reserved` CHECK (`reserved` >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='库存余额';

-- -----------------------------------------------------------------------------
-- 5. 预留
-- -----------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `stock_reservation` (
  `id`           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `material_id`  BIGINT UNSIGNED NOT NULL,
  `warehouse_id` BIGINT UNSIGNED NOT NULL,
  `lot_id`       BIGINT UNSIGNED NOT NULL DEFAULT 0,
  `qty`          DECIMAL(18,4)   NOT NULL,
  `ref_type`     VARCHAR(32)     NOT NULL,
  `ref_id`       VARCHAR(64)     NOT NULL,
  `line_no`      INT UNSIGNED    NOT NULL DEFAULT 1,
  `status`       ENUM('active','released','consumed','expired') NOT NULL DEFAULT 'active',
  `expires_at`   DATETIME(3)     NULL,
  `created_at`   DATETIME(3)     NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
  `updated_at`   DATETIME(3)     NOT NULL DEFAULT CURRENT_TIMESTAMP(3) ON UPDATE CURRENT_TIMESTAMP(3),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_reservation_ref` (`ref_type`,`ref_id`,`line_no`),
  KEY `idx_reservation_status` (`status`,`expires_at`),
  KEY `idx_reservation_material` (`material_id`,`warehouse_id`),
  CONSTRAINT `fk_reservation_material` FOREIGN KEY (`material_id`) REFERENCES `material` (`id`),
  CONSTRAINT `fk_reservation_warehouse` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouse` (`id`),
  CONSTRAINT `chk_reservation_qty` CHECK (`qty` > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='库存预留';

-- -----------------------------------------------------------------------------
-- 6. 盘点
-- -----------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `stocktake` (
  `id`           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `code`         VARCHAR(32)     NOT NULL,
  `warehouse_id` BIGINT UNSIGNED NOT NULL,
  `status`       ENUM('draft','counting','review','posted','cancelled') NOT NULL DEFAULT 'draft',
  `started_at`   DATETIME(3)     NULL,
  `finished_at`  DATETIME(3)     NULL,
  `created_by`   BIGINT UNSIGNED NULL,
  `remark`       VARCHAR(255)    NULL,
  `created_at`   DATETIME(3)     NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_stocktake_code` (`code`),
  KEY `idx_stocktake_warehouse` (`warehouse_id`,`status`),
  CONSTRAINT `fk_stocktake_warehouse` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouse` (`id`),
  CONSTRAINT `fk_stocktake_user` FOREIGN KEY (`created_by`) REFERENCES `user` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='盘点单';

CREATE TABLE IF NOT EXISTS `stocktake_line` (
  `id`           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `stocktake_id` BIGINT UNSIGNED NOT NULL,
  `material_id`  BIGINT UNSIGNED NOT NULL,
  `lot_id`       BIGINT UNSIGNED NOT NULL DEFAULT 0,
  `location_id`  BIGINT UNSIGNED NULL,
  `book_qty`     DECIMAL(18,4)   NOT NULL DEFAULT 0,
  `counted_qty`  DECIMAL(18,4)   NULL,
  `diff_qty`     DECIMAL(18,4)   NULL,
  `status`       ENUM('pending','counted','adjusted') NOT NULL DEFAULT 'pending',
  `remark`       VARCHAR(255)    NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_stocktake_line` (`stocktake_id`,`material_id`,`lot_id`),
  KEY `idx_stocktake_line_material` (`material_id`),
  CONSTRAINT `fk_stocktake_line_head` FOREIGN KEY (`stocktake_id`) REFERENCES `stocktake` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_stocktake_line_material` FOREIGN KEY (`material_id`) REFERENCES `material` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='盘点行';

SELECT '002_inventory.sql done' AS result;

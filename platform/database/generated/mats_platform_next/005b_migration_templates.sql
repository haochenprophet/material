-- =============================================================================
-- 物格云 / MaterialGrid — 005b 旧模板表迁移（bill / constitute / order）
-- 说明：实时 materiel 库没有这三张表；它们只存在于 table/create_*.sql 模板。
--       本脚本：
--       1) 在 mats 建 staging 表（按模板结构）；
--       2) 若 materiel 库存在同名表，则复制；否则跳过（打印提示）；
--       3) 迁移到 stock_txn / bom / sales_order。
-- 前置：001–004、005 已执行；如无源表，迁移结果为空，不会报错。
-- =============================================================================
USE `mats_platform_next`;

SET NAMES utf8mb4;
SET @migration_user_id = (SELECT `id` FROM `user` WHERE `email`='legacy@local.invalid' LIMIT 1);

-- -----------------------------------------------------------------------------
-- 1. staging 表（模板结构）
-- -----------------------------------------------------------------------------

DROP TABLE IF EXISTS `stg_bill`;
CREATE TABLE `stg_bill` (
  `idbill` int(11) NOT NULL AUTO_INCREMENT,
  `uuid` varchar(45) NOT NULL,
  `time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `where` varchar(45) DEFAULT NULL,
  `who` varchar(45) DEFAULT NULL,
  `type` varchar(45) DEFAULT NULL,
  `item` varchar(45) DEFAULT NULL,
  `amount` double DEFAULT NULL,
  `file` varchar(45) DEFAULT NULL,
  `imgage` varchar(45) DEFAULT NULL,
  `vedio` varchar(45) DEFAULT NULL,
  `audio` varchar(45) DEFAULT NULL,
  `from` varchar(45) DEFAULT NULL,
  `to` varchar(45) DEFAULT NULL,
  `status` varchar(45) DEFAULT NULL,
  `remark` varchar(45) DEFAULT NULL,
  `quantity` varchar(45) DEFAULT NULL,
  `univalent` varchar(45) DEFAULT NULL,
  `department` varchar(45) DEFAULT NULL,
  `approve` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`idbill`,`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `stg_constitute`;
CREATE TABLE `stg_constitute` (
  `idconstitute` int(11) NOT NULL AUTO_INCREMENT,
  `uuid` varchar(45) NOT NULL,
  `time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `product` varchar(45) DEFAULT NULL,
  `name` varchar(45) DEFAULT NULL,
  `url` varchar(450) DEFAULT NULL,
  `doc` varchar(450) DEFAULT NULL,
  `amount` decimal(65,10) DEFAULT NULL,
  `price` decimal(65,10) DEFAULT NULL,
  `measure` varchar(45) DEFAULT NULL,
  `currency` varchar(45) DEFAULT NULL,
  `remark` varchar(450) DEFAULT NULL,
  `status` varchar(45) DEFAULT NULL,
  `priority` int(11) NOT NULL DEFAULT 0,
  `supplier` varchar(100) DEFAULT NULL,
  `author` varchar(45) DEFAULT NULL,
  `image` varchar(450) DEFAULT NULL,
  `video` varchar(450) DEFAULT NULL,
  `audio` varchar(450) DEFAULT NULL,
  `text` varchar(450) DEFAULT NULL,
  `file` varchar(450) DEFAULT NULL,
  `website` varchar(450) NOT NULL DEFAULT 'http://www.red-arrows.cn/',
  `telephone` varchar(45) DEFAULT NULL,
  `address` varchar(450) DEFAULT NULL,
  `person` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`idconstitute`,`uuid`),
  UNIQUE KEY `idconstitute_UNIQUE` (`idconstitute`),
  UNIQUE KEY `uuid_UNIQUE` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `stg_order`;
CREATE TABLE `stg_order` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `uuid` varchar(45) NOT NULL,
  `product` varchar(45) NOT NULL,
  `amount` double NOT NULL,
  `delivery` datetime NOT NULL,
  `time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `customer` varchar(45) NOT NULL,
  `who` varchar(45) NOT NULL,
  `remark` text,
  `status` text,
  `priority` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`,`uuid`),
  UNIQUE KEY `id_UNIQUE` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------------------------------------
-- 2. 源表存在则复制，否则跳过
-- -----------------------------------------------------------------------------

SET @has_bill = (SELECT COUNT(*) FROM information_schema.tables
                 WHERE table_schema='materiel' AND table_name='bill');
SET @sql = IF(@has_bill > 0,
  'INSERT INTO stg_bill SELECT * FROM materiel.bill',
  'SELECT "skip: materiel.bill not found" AS msg');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @has_constitute = (SELECT COUNT(*) FROM information_schema.tables
                       WHERE table_schema='materiel' AND table_name='constitute');
SET @sql = IF(@has_constitute > 0,
  'INSERT INTO stg_constitute SELECT * FROM materiel.constitute',
  'SELECT "skip: materiel.constitute not found" AS msg');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @has_order = (SELECT COUNT(*) FROM information_schema.tables
                  WHERE table_schema='materiel' AND table_name='order');
SET @sql = IF(@has_order > 0,
  'INSERT INTO stg_order SELECT * FROM materiel.`order`',
  'SELECT "skip: materiel.order not found" AS msg');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

-- -----------------------------------------------------------------------------
-- 3. bill → stock_txn
-- -----------------------------------------------------------------------------

INSERT IGNORE INTO `stock_txn`
  (`material_id`,`warehouse_id`,`location_id`,`lot_id`,`txn_type`,`qty`,`unit_cost`,
   `ref_type`,`ref_id`,`line_no`,`operator_id`,`occurred_at`,`remark`)
SELECT
  mt.`id`,
  1,
  1,
  NULL,
  CASE WHEN UPPER(TRIM(b.`type`)) = 'IN' THEN 'RECEIPT' ELSE 'ISSUE' END,
  CASE WHEN UPPER(TRIM(b.`type`)) = 'IN'
       THEN CAST(b.`quantity` AS DECIMAL(18,4))
       ELSE -CAST(b.`quantity` AS DECIMAL(18,4)) END,
  COALESCE(CAST(NULLIF(b.`univalent`,'') AS DECIMAL(18,4)), 0),
  'LEGACY_BILL',
  CAST(b.`idbill` AS CHAR),
  1,
  @migration_user_id,
  COALESCE(b.`time`, NOW()),
  CONCAT_WS(' | ', NULLIF(TRIM(b.`remark`),''), NULLIF(TRIM(b.`from`),''), NULLIF(TRIM(b.`to`),''))
FROM `stg_bill` b
JOIN `material` mt ON mt.`id` = (
  SELECT x.`id` FROM `material` x
  WHERE x.`part_number` = UPPER(TRIM(b.`item`)) OR x.`name` = TRIM(b.`item`)
  ORDER BY x.`id` LIMIT 1
)
WHERE TRIM(b.`quantity`) REGEXP '^-?[0-9]+(\\.[0-9]+)?$';

INSERT INTO `stock_balance` (`material_id`,`warehouse_id`,`lot_id`,`on_hand`,`reserved`)
SELECT `material_id`,`warehouse_id`,COALESCE(`lot_id`,0),SUM(`qty`),0
FROM `stock_txn`
GROUP BY `material_id`,`warehouse_id`,COALESCE(`lot_id`,0)
ON DUPLICATE KEY UPDATE `on_hand` = VALUES(`on_hand`);

-- -----------------------------------------------------------------------------
-- 4. constitute → project / bom / bom_line
-- -----------------------------------------------------------------------------

INSERT INTO `org` (`uuid`,`name`,`type`,`status`)
SELECT UUID(),'迁移组织','company','disabled'
WHERE NOT EXISTS (SELECT 1 FROM `org` WHERE `name`='迁移组织');

INSERT INTO `project` (`uuid`,`org_id`,`code`,`name`,`status`)
SELECT UUID(), (SELECT `id` FROM `org` WHERE `name`='迁移组织' ORDER BY `id` LIMIT 1),
       'LEGACY', '旧库迁移项目', 'active'
WHERE NOT EXISTS (SELECT 1 FROM `project` WHERE `code`='LEGACY');

INSERT IGNORE INTO `bom` (`uuid`,`project_id`,`name`,`version`,`status`,`created_at`)
SELECT DISTINCT
       UUID(),
       p.`id`,
       TRIM(c.`product`),
       CONCAT('LEG-', LEFT(MD5(TRIM(c.`product`)),12)),
       'archived',
       NOW()
FROM `stg_constitute` c
CROSS JOIN `project` p
WHERE p.`code`='LEGACY'
  AND TRIM(c.`product`) <> '';

INSERT IGNORE INTO `bom_line`
  (`bom_id`,`line_no`,`part_number`,`qty`,`material_id`,`status`)
SELECT
  b.`id`,
  ROW_NUMBER() OVER (PARTITION BY b.`id` ORDER BY c.`idconstitute`),
  UPPER(TRIM(c.`name`)),
  COALESCE(c.`amount`, 1),
  (SELECT x.`id` FROM `material` x
   WHERE x.`part_number` = UPPER(TRIM(c.`name`)) OR x.`name` = TRIM(c.`name`)
   ORDER BY x.`id` LIMIT 1),
  'pending'
FROM `stg_constitute` c
JOIN `bom` b
  ON b.`name` = TRIM(c.`product`)
 AND b.`project_id` = (SELECT `id` FROM `project` WHERE `code`='LEGACY' LIMIT 1);

-- -----------------------------------------------------------------------------
-- 5. order → sales_order / sales_order_line
-- -----------------------------------------------------------------------------

INSERT INTO `org` (`uuid`,`name`,`type`,`status`)
SELECT UUID(),'迁移客户','company','disabled'
WHERE NOT EXISTS (SELECT 1 FROM `org` WHERE `name`='迁移客户');
INSERT INTO `org` (`uuid`,`name`,`type`,`status`)
SELECT UUID(),'迁移卖家','company','disabled'
WHERE NOT EXISTS (SELECT 1 FROM `org` WHERE `name`='迁移卖家');
INSERT INTO `store` (`uuid`,`org_id`,`name`,`slug`,`status`)
SELECT UUID(), o.`id`, '迁移店铺', 'legacy-store', 'closed'
FROM `org` o WHERE o.`name`='迁移卖家'
ON DUPLICATE KEY UPDATE `store`.`name` = VALUES(`name`);

INSERT IGNORE INTO `sales_order`
  (`uuid`,`order_no`,`buyer_org_id`,`seller_org_id`,`store_id`,`status`,
   `currency`,`subtotal`,`total`,`created_at`)
SELECT
  COALESCE(NULLIF(TRIM(o.`uuid`),''), UUID()),
  CONCAT('LEG-', o.`id`),
  (SELECT `id` FROM `org` WHERE `name`='迁移客户' ORDER BY `id` LIMIT 1),
  (SELECT `id` FROM `org` WHERE `name`='迁移卖家' ORDER BY `id` LIMIT 1),
  (SELECT `id` FROM `store` WHERE `slug`='legacy-store' LIMIT 1),
  CASE
    WHEN LOWER(TRIM(o.`status`)) IN ('completed','完成','已完成') THEN 'completed'
    WHEN LOWER(TRIM(o.`status`)) IN ('paid','已支付') THEN 'paid'
    WHEN LOWER(TRIM(o.`status`)) IN ('cancelled','取消') THEN 'cancelled'
    ELSE 'pending_payment'
  END,
  'CNY',
  0,
  0,
  COALESCE(o.`time`, NOW())
FROM `stg_order` o;

INSERT IGNORE INTO `sales_order_line`
  (`order_id`,`line_no`,`material_id`,`qty`,`unit_price`,`tax_rate`,`amount`,`status`)
SELECT
  so.`id`,
  1,
  mt.`id`,
  o.`amount`,
  0,
  13.00,
  0,
  'pending'
FROM `stg_order` o
JOIN `sales_order` so ON so.`order_no` = CONCAT('LEG-', o.`id`)
JOIN `material` mt ON mt.`id` = (
  SELECT x.`id` FROM `material` x
  WHERE x.`part_number` = UPPER(TRIM(o.`product`)) OR x.`name` = TRIM(o.`product`)
  ORDER BY x.`id` LIMIT 1
);

-- -----------------------------------------------------------------------------
-- 6. 校验
-- -----------------------------------------------------------------------------

SELECT 'bill' AS src, COUNT(*) AS cnt FROM `stg_bill`
UNION ALL SELECT 'stock_txn(LEGACY_BILL)', COUNT(*) FROM `stock_txn` WHERE `ref_type`='LEGACY_BILL'
UNION ALL SELECT 'constitute', COUNT(*) FROM `stg_constitute`
UNION ALL SELECT 'bom', COUNT(*) FROM `bom`
UNION ALL SELECT 'order', COUNT(*) FROM `stg_order`
UNION ALL SELECT 'sales_order(LEG)', COUNT(*) FROM `sales_order` WHERE `order_no` LIKE 'LEG-%';

SELECT '005b_migration_templates.sql done' AS result;

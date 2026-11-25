-- =============================================================================
-- 物格云 / MaterialGrid — 005 旧库迁移（materiel 主表）
-- 依赖：001–004 已执行；006_seed.sql 建议先执行
--
-- 重要事实（2026-11-24 实时库）：
--   materiel 库只有 13 张表：action/finance/how/materiel/price/status/type/url/
--   what/when/where/who/why；**没有 bill/constitute/order**。
--   bill/constitute/order 只存在于 material/table/create_*.sql 模板。
--   因此本文件只迁移 materiel 主表；模板表迁移见 005b（需先建表）。
--
-- 迁移前请备份；脚本使用 INSERT IGNORE，冲突行会被跳过，需看校验结果。
-- =============================================================================
USE `mats_platform_next2`;

SET NAMES utf8mb4;
SET @migration_user_id = NULL;

-- -----------------------------------------------------------------------------
-- 0. 默认主数据（幂等）
-- -----------------------------------------------------------------------------

INSERT INTO `brand` (`id`,`name`,`status`) VALUES (1,'未识别','active')
  ON DUPLICATE KEY UPDATE `name`=`name`;
INSERT INTO `manufacturer` (`id`,`name`,`status`) VALUES (1,'未识别','active')
  ON DUPLICATE KEY UPDATE `name`=`name`;
INSERT INTO `uom` (`id`,`code`,`name`,`precision_scale`) VALUES (1,'PCS','个',0)
  ON DUPLICATE KEY UPDATE `name`=`name`;
INSERT INTO `package` (`id`,`code`,`name`) VALUES (1,'UNKNOWN','未识别')
  ON DUPLICATE KEY UPDATE `name`=`name`;
INSERT INTO `category` (`id`,`parent_id`,`code`,`name`,`level`,`path`,`status`)
  VALUES (99,NULL,'OTHER','其他',1,'/99','active')
  ON DUPLICATE KEY UPDATE `name`=`name`;
INSERT INTO `warehouse` (`id`,`code`,`name`,`type`,`status`) VALUES (1,'WH-DEFAULT','默认仓','platform','active')
  ON DUPLICATE KEY UPDATE `name`=`name`;
INSERT INTO `location` (`id`,`warehouse_id`,`code`,`name`,`status`) VALUES (1,1,'DEFAULT','默认库位','active')
  ON DUPLICATE KEY UPDATE `name`=`name`;

INSERT INTO `user` (`uuid`,`email`,`password_hash`,`display_name`,`status`)
  VALUES (UUID(),'legacy@local.invalid','!','迁移用户','disabled')
  ON DUPLICATE KEY UPDATE `display_name`=`display_name`;
SET @migration_user_id = (SELECT `id` FROM `user` WHERE `email`='legacy@local.invalid' LIMIT 1);

-- -----------------------------------------------------------------------------
-- 1. 旧表快照（staging）
-- -----------------------------------------------------------------------------

DROP TABLE IF EXISTS `stg_materiel`;
CREATE TABLE `stg_materiel` LIKE `materiel`.`materiel`;
INSERT INTO `stg_materiel` SELECT * FROM `materiel`.`materiel`;

-- -----------------------------------------------------------------------------
-- 2. 类目/品牌：从旧文本派生
-- -----------------------------------------------------------------------------

INSERT IGNORE INTO `category` (`parent_id`,`code`,`name`,`level`,`path`,`status`)
SELECT DISTINCT
       NULL,
       CONCAT('LEG-', LEFT(SHA1(TRIM(`type`)),8)),
       TRIM(`type`),
       1,
       CONCAT('/LEG-', LEFT(SHA1(TRIM(`type`)),8)),
       'active'
FROM `stg_materiel`
WHERE TRIM(`type`) <> '';

INSERT IGNORE INTO `brand` (`name`,`status`)
SELECT DISTINCT TRIM(`manufacturer`), 'active'
FROM `stg_materiel`
WHERE TRIM(`manufacturer`) <> '';

INSERT IGNORE INTO `manufacturer` (`name`,`status`)
SELECT DISTINCT TRIM(`manufacturer`), 'active'
FROM `stg_materiel`
WHERE TRIM(`manufacturer`) <> '';

-- -----------------------------------------------------------------------------
-- 3. 物料
-- -----------------------------------------------------------------------------

INSERT IGNORE INTO `material`
  (`uuid`,`legacy_id`,`code`,`part_number`,`name`,`description`,
   `category_id`,`uom_id`,`brand_id`,`manufacturer_id`,`package_id`,
   `barcode`,`lifecycle_status`,`datasheet_status`,`datasheet_url`,
   `created_at`,`updated_at`)
SELECT
  COALESCE(NULLIF(TRIM(m.`uuid`),''), UUID()),
  m.`idmateriel`,
  CONCAT('LEG-', LPAD(m.`idmateriel`, 8, '0')),
  CASE WHEN TRIM(m.`part_number`) = '' THEN CONCAT('LEG-', m.`idmateriel`)
       ELSE UPPER(TRIM(m.`part_number`)) END,
  TRIM(m.`name`),
  CONCAT_WS(' | ', NULLIF(TRIM(m.`what`),''), NULLIF(TRIM(m.`remark`),'')),
  COALESCE((SELECT c.`id` FROM `category` c
            WHERE c.`name`=TRIM(m.`type`) AND c.`parent_id` IS NULL
            ORDER BY c.`id` LIMIT 1), 99),
  1,
  COALESCE((SELECT b.`id` FROM `brand` b WHERE b.`name`=TRIM(m.`manufacturer`) ORDER BY b.`id` LIMIT 1), 1),
  COALESCE((SELECT mf.`id` FROM `manufacturer` mf WHERE mf.`name`=TRIM(m.`manufacturer`) ORDER BY mf.`id` LIMIT 1), 1),
  1,
  NULLIF(TRIM(m.`barcode`),''),
  CASE WHEN LOWER(TRIM(m.`status`)) IN ('active','normal','在库','正常') THEN 'active' ELSE 'draft' END,
  CASE WHEN TRIM(m.`url`) LIKE 'http%' THEN 'available' ELSE 'unknown' END,
  NULLIF(TRIM(m.`url`),''),
  COALESCE(m.`time`, NOW()),
  COALESCE(m.`time`, NOW())
FROM `stg_materiel` m;

-- -----------------------------------------------------------------------------
-- 4. 批次 + 期初库存
-- -----------------------------------------------------------------------------

INSERT IGNORE INTO `stock_lot`
  (`material_id`,`lot_no`,`qc_status`,`grade`,`unit_cost`,`currency`,`status`)
SELECT mt.`id`,
       COALESCE(NULLIF(TRIM(m.`date_code`),''), CONCAT('LEG-', m.`idmateriel`)),
       'na',
       'NA',
       COALESCE(m.`cost_price`, 0),
       'CNY',
       'active'
FROM `stg_materiel` m
JOIN `material` mt ON mt.`legacy_id` = m.`idmateriel`;

INSERT IGNORE INTO `stock_txn`
  (`material_id`,`warehouse_id`,`location_id`,`lot_id`,`txn_type`,`qty`,`unit_cost`,
   `ref_type`,`ref_id`,`line_no`,`operator_id`,`occurred_at`,`remark`)
SELECT mt.`id`,
       1,
       1,
       sl.`id`,
       'OPENING',
       m.`inbound_quantity`,
       COALESCE(m.`cost_price`, 0),
       'LEGACY_MATERIEL',
       CAST(m.`idmateriel` AS CHAR),
       1,
       @migration_user_id,
       COALESCE(m.`time`, NOW()),
       'legacy inbound_quantity'
FROM `stg_materiel` m
JOIN `material` mt ON mt.`legacy_id` = m.`idmateriel`
JOIN `stock_lot` sl
  ON sl.`material_id` = mt.`id`
 AND sl.`lot_no` = COALESCE(NULLIF(TRIM(m.`date_code`),''), CONCAT('LEG-', m.`idmateriel`))
WHERE m.`inbound_quantity` <> 0;

INSERT INTO `stock_balance` (`material_id`,`warehouse_id`,`lot_id`,`on_hand`,`reserved`)
SELECT `material_id`,`warehouse_id`,COALESCE(`lot_id`,0),SUM(`qty`),0
FROM `stock_txn`
GROUP BY `material_id`,`warehouse_id`,COALESCE(`lot_id`,0)
ON DUPLICATE KEY UPDATE `on_hand` = VALUES(`on_hand`);

-- -----------------------------------------------------------------------------
-- 5. 校验
-- -----------------------------------------------------------------------------

SELECT 'materiel' AS src, COUNT(*) AS cnt FROM `stg_materiel`
UNION ALL SELECT 'material', COUNT(*) FROM `material`
UNION ALL SELECT 'stock_lot', COUNT(*) FROM `stock_lot`
UNION ALL SELECT 'stock_txn(OPENING)', COUNT(*) FROM `stock_txn` WHERE `ref_type`='LEGACY_MATERIEL'
UNION ALL SELECT 'material_no_category', COUNT(*) FROM `material` WHERE `category_id`=99;

-- 余额 vs 流水
SELECT b.`material_id`, b.`warehouse_id`, b.`lot_id`, b.`on_hand`,
       COALESCE(SUM(t.`qty`),0) AS txn_sum,
       b.`on_hand` - COALESCE(SUM(t.`qty`),0) AS diff
FROM `stock_balance` b
LEFT JOIN `stock_txn` t
  ON t.`material_id`=b.`material_id`
 AND t.`warehouse_id`=b.`warehouse_id`
 AND t.`lot_id`=b.`lot_id`
GROUP BY b.`material_id`, b.`warehouse_id`, b.`lot_id`, b.`on_hand`
HAVING ABS(diff) > 0.0001;

SELECT '005_migration_legacy.sql done' AS result;

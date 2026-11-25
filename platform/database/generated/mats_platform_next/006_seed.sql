-- =============================================================================
-- 物格云 / MaterialGrid — 006 种子数据
-- 依赖：001–004 已执行
-- 内容：角色/权限、单位、类目树、品牌/厂商/封装、仓库、属性模板、
--       佣金规则、会计科目、广告位
-- 说明：幂等，可重复执行；生产环境请按实际业务调整类目/费率。
-- =============================================================================
USE `mats_platform_next`;

SET NAMES utf8mb4;

-- -----------------------------------------------------------------------------
-- 1. 角色与权限
-- -----------------------------------------------------------------------------

INSERT INTO `role` (`id`,`code`,`name`,`scope`) VALUES
 (1,'admin','平台管理员','platform'),
 (2,'ops','平台运营','platform'),
 (3,'finance','财务','platform'),
 (4,'qc','质检员','warehouse'),
 (5,'warehouse','仓管员','warehouse'),
 (6,'seller_admin','卖家管理员','store'),
 (7,'seller_ops','卖家运营','store'),
 (8,'buyer_admin','企业采购管理员','org'),
 (9,'buyer','买家','org'),
 (10,'viewer','只读','org')
ON DUPLICATE KEY UPDATE `name`=VALUES(`name`), `scope`=VALUES(`scope`);

INSERT INTO `permission` (`code`,`name`,`module`) VALUES
 ('user.view','查看用户','identity'),
 ('user.manage','管理用户','identity'),
 ('org.view','查看组织','identity'),
 ('org.manage','管理组织','identity'),
 ('material.view','查看物料','catalog'),
 ('material.create','新建物料','catalog'),
 ('material.update','编辑物料','catalog'),
 ('material.delete','停用物料','catalog'),
 ('inventory.view','查看库存','inventory'),
 ('inventory.post','库存过账','inventory'),
 ('inventory.adjust','库存调整','inventory'),
 ('listing.view','查看提报','listing'),
 ('listing.create','创建提报','listing'),
 ('listing.review','审核提报','listing'),
 ('qc.execute','执行质检','qc'),
 ('order.view','查看订单','trade'),
 ('order.manage','管理订单','trade'),
 ('payment.view','查看支付','trade'),
 ('settlement.view','查看结算','settlement'),
 ('settlement.approve','审批结算','settlement'),
 ('invoice.manage','管理发票','settlement'),
 ('promotion.manage','管理推流','growth'),
 ('admin.audit','查看审计','ops'),
 ('admin.config','系统配置','ops'),
 ('api.manage','管理 API','dev')
ON DUPLICATE KEY UPDATE `name`=VALUES(`name`), `module`=VALUES(`module`);

-- admin 全部权限
INSERT IGNORE INTO `role_permission` (`role_id`,`permission_id`)
SELECT 1, `id` FROM `permission`;

-- ops
INSERT IGNORE INTO `role_permission` (`role_id`,`permission_id`)
SELECT 2, `id` FROM `permission`
WHERE `code` IN ('user.view','org.view','material.view','material.create','material.update',
                 'inventory.view','listing.view','listing.review','qc.execute',
                 'order.view','order.manage','payment.view','promotion.manage','admin.audit');

-- finance
INSERT IGNORE INTO `role_permission` (`role_id`,`permission_id`)
SELECT 3, `id` FROM `permission`
WHERE `code` IN ('org.view','order.view','payment.view','settlement.view','settlement.approve',
                 'invoice.manage','admin.audit');

-- qc
INSERT IGNORE INTO `role_permission` (`role_id`,`permission_id`)
SELECT 4, `id` FROM `permission`
WHERE `code` IN ('material.view','inventory.view','listing.view','qc.execute');

-- warehouse
INSERT IGNORE INTO `role_permission` (`role_id`,`permission_id`)
SELECT 5, `id` FROM `permission`
WHERE `code` IN ('material.view','inventory.view','inventory.post','inventory.adjust','listing.view');

-- seller_admin
INSERT IGNORE INTO `role_permission` (`role_id`,`permission_id`)
SELECT 6, `id` FROM `permission`
WHERE `code` IN ('material.view','material.create','inventory.view','inventory.post',
                 'listing.view','listing.create','order.view','order.manage','payment.view',
                 'settlement.view','api.manage');

-- seller_ops
INSERT IGNORE INTO `role_permission` (`role_id`,`permission_id`)
SELECT 7, `id` FROM `permission`
WHERE `code` IN ('material.view','inventory.view','listing.view','listing.create','order.view',
                 'order.manage');

-- buyer_admin
INSERT IGNORE INTO `role_permission` (`role_id`,`permission_id`)
SELECT 8, `id` FROM `permission`
WHERE `code` IN ('material.view','order.view','order.manage','payment.view','settlement.view',
                 'invoice.manage','org.manage');

-- buyer
INSERT IGNORE INTO `role_permission` (`role_id`,`permission_id`)
SELECT 9, `id` FROM `permission`
WHERE `code` IN ('material.view','order.view','payment.view','invoice.manage');

-- viewer
INSERT IGNORE INTO `role_permission` (`role_id`,`permission_id`)
SELECT 10, `id` FROM `permission`
WHERE `code` IN ('material.view','inventory.view','listing.view','order.view');

-- -----------------------------------------------------------------------------
-- 2. 单位 / 类目 / 品牌 / 封装
-- -----------------------------------------------------------------------------

INSERT INTO `uom` (`id`,`code`,`name`,`precision_scale`) VALUES
 (1,'PCS','个',0),
 (2,'SET','套',0),
 (3,'KG','千克',3),
 (4,'M','米',2),
 (5,'REEL','卷',0),
 (6,'TRAY','托盘',0),
 (7,'BOX','盒',0)
ON DUPLICATE KEY UPDATE `name`=VALUES(`name`), `precision_scale`=VALUES(`precision_scale`);

INSERT INTO `category` (`id`,`parent_id`,`code`,`name`,`level`,`path`,`sort`,`status`) VALUES
 (1,NULL,'ROOT','全部',0,'/1',0,'active'),
 (10,1,'ELEC','电子元器件',1,'/1/10',10,'active'),
 (11,10,'CONNECTOR','连接器',2,'/1/10/11',11,'active'),
 (12,10,'PASSIVE','被动件',2,'/1/10/12',12,'active'),
 (13,10,'IC','集成电路',2,'/1/10/13',13,'active'),
 (14,10,'MODULE','模组',2,'/1/10/14',14,'active'),
 (15,10,'OPTO','光电器件',2,'/1/10/15',15,'active'),
 (16,10,'SENSOR','传感器',2,'/1/10/16',16,'active'),
 (17,10,'POWER','电源/电池',2,'/1/10/17',17,'active'),
 (18,10,'MECH','结构件',2,'/1/10/18',18,'active'),
 (20,1,'EQUIP','设备/仪器',1,'/1/20',20,'active'),
 (21,20,'TEST','测试仪器',2,'/1/20/21',21,'active'),
 (22,20,'TOOL','工具/耗材',2,'/1/20/22',22,'active'),
 (99,1,'OTHER','其他',1,'/1/99',99,'active')
ON DUPLICATE KEY UPDATE `name`=VALUES(`name`), `path`=VALUES(`path`), `level`=VALUES(`level`);

INSERT INTO `brand` (`id`,`name`,`name_en`,`country`,`status`) VALUES
 (1,'未识别',NULL,NULL,'active'),
 (2,'长城','Great Wall','CN','active'),
 (3,'德州仪器','TI','US','active'),
 (4,'意法半导体','ST','CH','active'),
 (5,'恩智浦','NXP','NL','active'),
 (6,'村田','Murata','JP','active'),
 (7,'泰科','TE Connectivity','CH','active'),
 (8,'莫仕','Molex','US','active')
ON DUPLICATE KEY UPDATE `name_en`=VALUES(`name_en`), `country`=VALUES(`country`);

INSERT INTO `manufacturer` (`id`,`name`,`name_en`,`country`,`status`) VALUES
 (1,'未识别',NULL,NULL,'active'),
 (2,'长城','Great Wall','CN','active'),
 (3,'德州仪器','TI','US','active'),
 (4,'意法半导体','ST','CH','active'),
 (5,'恩智浦','NXP','NL','active'),
 (6,'村田','Murata','JP','active')
ON DUPLICATE KEY UPDATE `name_en`=VALUES(`name_en`), `country`=VALUES(`country`);

INSERT INTO `package` (`id`,`code`,`name`,`description`) VALUES
 (1,'UNKNOWN','未识别',NULL),
 (2,'SMD','贴片',NULL),
 (3,'DIP','直插',NULL),
 (4,'BGA','球栅阵列',NULL),
 (5,'QFN','方形扁平无引脚',NULL),
 (6,'QFP','方形扁平',NULL),
 (7,'SOP','小外形',NULL),
 (8,'0402','0402',NULL),
 (9,'0603','0603',NULL),
 (10,'0805','0805',NULL)
ON DUPLICATE KEY UPDATE `name`=VALUES(`name`), `description`=VALUES(`description`);

-- -----------------------------------------------------------------------------
-- 3. 仓库/库位
-- -----------------------------------------------------------------------------

INSERT INTO `warehouse` (`id`,`code`,`name`,`type`,`status`) VALUES
 (1,'WH-DEFAULT','默认仓','platform','active'),
 (2,'WH-QC','待检仓','platform','active'),
 (3,'WH-TRANSIT','在途仓','transit','active')
ON DUPLICATE KEY UPDATE `name`=VALUES(`name`), `type`=VALUES(`type`);

INSERT INTO `location` (`id`,`warehouse_id`,`code`,`name`,`zone`,`status`) VALUES
 (1,1,'DEFAULT','默认库位','A','active'),
 (2,2,'QC','待检区','Q','active'),
 (3,3,'TRANSIT','在途','T','active')
ON DUPLICATE KEY UPDATE `name`=VALUES(`name`), `zone`=VALUES(`zone`);

-- -----------------------------------------------------------------------------
-- 4. 属性模板
-- -----------------------------------------------------------------------------

INSERT INTO `attr_def` (`category_id`,`code`,`name`,`data_type`,`unit`,`required`,`searchable`,`sort`) VALUES
 (11,'positions','位数','number','pin',0,1,1),
 (11,'pitch_mm','间距','number','mm',0,1,2),
 (11,'current_a','额定电流','number','A',0,1,3),
 (11,'voltage_v','额定电压','number','V',0,1,4),
 (11,'gender','公母','enum',NULL,0,1,5),
 (11,'mounting','安装方式','enum',NULL,0,1,6),
 (12,'resistance','阻值','number','Ω',0,1,1),
 (12,'capacitance','容值','number','F',0,1,2),
 (12,'inductance','感值','number','H',0,1,3),
 (12,'tolerance','精度','number','%',0,1,4),
 (12,'power_w','功率','number','W',0,1,5),
 (12,'voltage_v','耐压','number','V',0,1,6),
 (13,'function','功能','text',NULL,0,1,1),
 (13,'pins','引脚数','number','pin',0,1,2),
 (13,'supply_voltage','供电电压','number','V',0,1,3),
 (13,'interface','接口','text',NULL,0,1,4),
 (13,'temperature','工作温度','text',NULL,0,1,5),
 (14,'module_type','模组类型','text',NULL,0,1,1),
 (14,'supply_voltage','供电电压','number','V',0,1,2),
 (20,'model','型号','text',NULL,1,1,1),
 (20,'year','年份','number','year',0,1,2),
 (20,'condition','成色','enum',NULL,1,1,3)
ON DUPLICATE KEY UPDATE `name`=VALUES(`name`), `data_type`=VALUES(`data_type`), `unit`=VALUES(`unit`);

INSERT INTO `attr_option` (`attr_def_id`,`value`,`label`,`sort`)
SELECT a.`id`, x.`value`, x.`label`, x.`sort`
FROM `attr_def` a
JOIN (
  SELECT 'gender' AS `code`, 'male' AS `value`, '公头' AS `label`, 1 AS `sort`
  UNION ALL SELECT 'gender','female','母头',2
  UNION ALL SELECT 'gender','hermaphroditic','双性',3
  UNION ALL SELECT 'mounting','smd','贴片',1
  UNION ALL SELECT 'mounting','through_hole','直插',2
  UNION ALL SELECT 'condition','new','全新',1
  UNION ALL SELECT 'condition','surplus','散新/余料',2
  UNION ALL SELECT 'condition','refurb','翻新',3
  UNION ALL SELECT 'condition','used','二手',4
  UNION ALL SELECT 'condition','scrap','报废/拆件',5
) x ON x.`code` = a.`code`
ON DUPLICATE KEY UPDATE `label`=VALUES(`label`), `sort`=VALUES(`sort`);

-- -----------------------------------------------------------------------------
-- 5. 佣金规则 / 会计科目 / 广告位
-- -----------------------------------------------------------------------------

INSERT INTO `commission_rule` (`category_id`,`seller_type`,`rate`,`min_fee`,`max_fee`,`effective_from`,`status`)
SELECT NULL,'any',0.0200,1.00,2000.00,'2026-01-01','active'
WHERE NOT EXISTS (SELECT 1 FROM `commission_rule` WHERE `category_id` IS NULL AND `seller_type`='any');

INSERT INTO `ledger_account` (`code`,`name`,`type`) VALUES
 ('1001','支付机构备付金','asset'),
 ('1002','银行存款','asset'),
 ('1405','存货','asset'),
 ('2202','应付账款-卖家','liability'),
 ('2203','应付账款-服务商','liability'),
 ('2221','应交税费-销项','liability'),
 ('6001','主营业务收入-自营','revenue'),
 ('6002','主营业务收入-佣金','revenue'),
 ('6003','主营业务收入-服务','revenue'),
 ('6004','主营业务收入-会员/广告','revenue'),
 ('6401','主营业务成本','expense'),
 ('6601','销售费用','expense'),
 ('6602','管理费用','expense'),
 ('6603','研发费用','expense')
ON DUPLICATE KEY UPDATE `name`=VALUES(`name`), `type`=VALUES(`type`);

INSERT INTO `ad_slot` (`code`,`name`,`type`,`status`) VALUES
 ('search_top','搜索结果顶部','search','active'),
 ('category_top','类目页顶部','category','active'),
 ('home_banner','首页 banner','home','active'),
 ('detail_side','详情页侧栏','detail','active')
ON DUPLICATE KEY UPDATE `name`=VALUES(`name`), `type`=VALUES(`type`);

SELECT '006_seed.sql done' AS result;

CREATE TABLE `constitute` (
  `idconstitute` int(11) NOT NULL AUTO_INCREMENT,
  `uuid` varchar(45) NOT NULL,
  `time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `product` varchar(45) DEFAULT NULL COMMENT 'What Finished product name?',
  `name` varchar(45) DEFAULT NULL,
  `url` varchar(450) DEFAULT NULL,
  `doc` varchar(450) DEFAULT NULL COMMENT 'doc url',
  `amount` decimal(65,10) DEFAULT NULL,
  `price` decimal(65,10) DEFAULT NULL COMMENT 'univalent',
  `measure` varchar(45) DEFAULT NULL COMMENT 'Measurement unit symbol: distance length,speed,weight,temperature',
  `currency` varchar(45) DEFAULT NULL COMMENT 'currency:US dollar,RMB,EUR,JPY,GBP,ruble,',
  `remark` varchar(450) DEFAULT NULL,
  `status` varchar(45) DEFAULT NULL,
  `priority` int(11) NOT NULL DEFAULT '0',
  `supplier` varchar(100) DEFAULT NULL COMMENT 'who supplier?',
  `author` varchar(45) DEFAULT NULL COMMENT 'who author?',
  `image` varchar(450) DEFAULT NULL COMMENT 'image url',
  `video` varchar(450) DEFAULT NULL COMMENT 'video url',
  `audio` varchar(450) DEFAULT NULL COMMENT 'audio url',
  `text` varchar(450) DEFAULT NULL,
  `file` varchar(450) DEFAULT NULL COMMENT 'file url',
  `website` varchar(450) NOT NULL DEFAULT 'http://www.red-arrows.cn/',
  `telephone` varchar(45) DEFAULT NULL,
  `address` varchar(450) DEFAULT NULL,
  `person` varchar(45) DEFAULT NULL COMMENT 'Contact person',
  PRIMARY KEY (`idconstitute`,`uuid`,`time`),
  UNIQUE KEY `idconstitute_UNIQUE` (`idconstitute`),
  UNIQUE KEY `uuid_UNIQUE` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='constitute';
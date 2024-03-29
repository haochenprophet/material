CREATE TABLE `message` (
  `idmessage` int(11) NOT NULL AUTO_INCREMENT,
  `uuid` varchar(45) NOT NULL,
  `time` datetime DEFAULT CURRENT_TIMESTAMP,
  `when` datetime DEFAULT CURRENT_TIMESTAMP,
  `country` varchar(45) DEFAULT NULL,
  `where` varchar(100) DEFAULT NULL,
  `url` varchar(200) DEFAULT NULL,
  `who` varchar(45) DEFAULT NULL,
  `what` varchar(200) DEFAULT NULL,
  `type` varchar(45) DEFAULT NULL,
  `priority` varchar(45) DEFAULT NULL,
  `status` varchar(45) DEFAULT NULL,
  `why` varchar(200) DEFAULT NULL,
  `evaluate` text,
  `logo` varchar(100) DEFAULT NULL,
  `audio` varchar(200) DEFAULT NULL,
  `video` varchar(200) DEFAULT NULL,
  `image` varchar(200) DEFAULT NULL,
  `remark` text,
  `author` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`idmessage`,`uuid`),
  UNIQUE KEY `idmessage_UNIQUE` (`idmessage`),
  UNIQUE KEY `uuid_UNIQUE` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
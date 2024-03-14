CREATE TABLE `psq5what` (
  `idpsq5what` bigint(20) NOT NULL AUTO_INCREMENT,
  `uuid` varchar(45) NOT NULL,
  `people` varchar(1024) DEFAULT NULL,
  `question` varchar(1024) DEFAULT NULL,
  `solution` varchar(1024) DEFAULT NULL,
  `when` datetime NOT NULL,
  `where` varchar(1024) DEFAULT NULL,
  `who` varchar(1024) DEFAULT NULL,
  `what` varchar(1024) DEFAULT NULL,
  `why` varchar(1024) DEFAULT NULL,
  `how` varchar(1024) DEFAULT NULL COMMENT 'how to do',
  `action` varchar(1024) DEFAULT NULL,
  `target` varchar(1024) DEFAULT NULL,
  `feedback` varchar(45) DEFAULT NULL,
  `object` varchar(1024) DEFAULT NULL,
  `status` varchar(45) NOT NULL DEFAULT 'normal',
  `time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `parents` varchar(45) DEFAULT NULL COMMENT 'parents uuid ',
  `child` varchar(45) DEFAULT NULL,
  `firstchild` varchar(45) DEFAULT NULL,
  `nextsibling` varchar(45) DEFAULT NULL,
  `type` varchar(45) DEFAULT NULL,
  `priority` int(11) NOT NULL DEFAULT '0',
  `author` varchar(45) NOT NULL DEFAULT 'hunter',
  PRIMARY KEY (`idpsq5what`,`uuid`),
  UNIQUE KEY `idnew_table_UNIQUE` (`idpsq5what`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='people-question-solution-when-where-who-what-how-action-target';
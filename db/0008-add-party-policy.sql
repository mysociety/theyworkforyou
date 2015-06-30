CREATE TABLE `partypolicy` (
  `id` int(11) NOT NULL auto_increment,
  `house` int(11) default NULL,
  `party` varchar(100) collate latin1_spanish_ci NOT NULL default '',
  `policy_id` varchar(100) NOT NULL default '',
  `score` float NOT NULL default 0,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `party_policy` (`party`, `house`, `policy_id`),
  KEY `party` (`party`),
  KEY `policy_id` (`policy_id`)
);
ALTER TABLE `partypolicy` COLLATE latin1_spanish_ci;

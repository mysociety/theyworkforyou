CREATE TABLE `consinfo` (
  `constituency` varchar(100) NOT NULL default '',
  `data_key` varchar(100) NOT NULL default '',
  `data_value` text NOT NULL,
  KEY `constituency` (`constituency`)
);

CREATE TABLE `constituency` (
  `name` varchar(100) NOT NULL default '',
  `main_name` tinyint(1) NOT NULL default '0',
  `from_date` date NOT NULL default '1000-01-01',
  `to_date` date NOT NULL default '9999-12-31',
  `cons_id` int(11) default NULL,
  KEY `from_date` (`from_date`),
  KEY `to_date` (`to_date`),
  KEY `name` (`name`),
  KEY `constituency` (`cons_id`)
);

CREATE TABLE `epobject` (
  `epobject_id` int(11) NOT NULL auto_increment,
  `title` varchar(255) default NULL,
  `body` mediumtext,
  `type` int(11) default NULL,
  `created` datetime default NULL,
  `modified` datetime default NULL,
  PRIMARY KEY  (`epobject_id`),
  KEY `type` (`type`)
);

CREATE TABLE `gidredirect` (
  `gid_from` char(60) default NULL,
  `gid_to` char(60) default NULL,
  `hdate` date NOT NULL default '0000-00-00',
  `major` int(11) default NULL,
  UNIQUE KEY `gid_from` (`gid_from`),
  KEY `gid_to` (`gid_to`)
);

CREATE TABLE `hansard` (
  `epobject_id` int(11) NOT NULL default '0',
  `gid` varchar(60) default NULL,
  `htype` int(11) NOT NULL default '0',
  `speaker_id` int(11) default NULL,
  `major` int(11) default NULL,
  `section_id` int(11) NOT NULL default '0',
  `subsection_id` int(11) NOT NULL default '0',
  `hpos` int(11) NOT NULL default '0',
  `hdate` date NOT NULL default '0000-00-00',
  `htime` time default NULL,
  `source_url` varchar(255) NOT NULL default '',
  `minor` int(11) default NULL,
  `created` datetime default NULL,
  `modified` datetime default NULL,
  PRIMARY KEY  (`epobject_id`),
  UNIQUE KEY `gid` (`gid`),
  KEY `epobject_id` (`epobject_id`),
  KEY `subsection_id` (`subsection_id`),
  KEY `section_id` (`section_id`),
  KEY `hdate` (`hdate`),
  KEY `speaker_id` (`speaker_id`),
  KEY `major` (`major`),
  KEY `htype` (`htype`),
  KEY `majorhdate` (`major`,`hdate`),
  KEY `modified` (`modified`)
);

CREATE TABLE `member` (
  `member_id` int(11) NOT NULL default '0',
  `house` int(11) default NULL,
  `first_name` varchar(100) default NULL,
  `last_name` varchar(100) NOT NULL default '',
  `constituency` varchar(100) NOT NULL default '',
  `party` varchar(100) NOT NULL default '',
  `entered_house` date NOT NULL default '1000-01-01',
  `left_house` date NOT NULL default '9999-12-31',
  `entered_reason` enum('unknown','general_election','by_election','changed_party','reinstated','appointed','devolution','election') NOT NULL default 'unknown',
  `left_reason` enum('unknown','still_in_office','general_election','general_election_standing','general_election_not_standing','changed_party','died','declared_void','resigned','disqualified','became_peer','devolution','dissolution') NOT NULL default 'unknown',
  `person_id` int(11) NOT NULL default '0',
  `title` varchar(50) NOT NULL default '',
  PRIMARY KEY  (`member_id`),
  UNIQUE KEY `first_name` (`first_name`,`last_name`,`constituency`,`entered_house`,`left_house`),
  KEY `person_id` (`person_id`),
  KEY `constituency` (`constituency`),
  KEY `house` (`house`),
  KEY `left_house_house` (`left_house`,`house`)
);

CREATE TABLE `memberinfo` (
  `member_id` int(11) NOT NULL default '0',
  `data_key` varchar(100) NOT NULL default '',
  `data_value` text NOT NULL,
  KEY `member_id` (`member_id`)
);

CREATE TABLE `moffice` (
  `moffice_id` int(11) NOT NULL auto_increment,
  `dept` varchar(100) NOT NULL default '',
  `position` varchar(200) NOT NULL default '',
  `from_date` date NOT NULL default '1000-01-01',
  `to_date` date NOT NULL default '9999-12-31',
  `person` int(11) default NULL,
  `source` varchar(255) NOT NULL,
  PRIMARY KEY  (`moffice_id`),
  KEY `person` (`person`)
);

CREATE TABLE `personinfo` (
  `person_id` int(11) NOT NULL default '0',
  `data_key` varchar(100) NOT NULL default '',
  `data_value` text NOT NULL,
  KEY `person_id` (`person_id`)
);

CREATE TABLE `postcode_lookup` (
  `postcode` varchar(10) NOT NULL default '',
  `name` varchar(100) NOT NULL default '',
  PRIMARY KEY  (`postcode`)
);

-- each time we index, we increment the batch number;
-- can use this to speed up search
CREATE TABLE `indexbatch` (
  `indexbatch_id` int(11) NOT NULL auto_increment,
  `created` datetime default NULL,
  PRIMARY KEY  (`indexbatch_id`)
);


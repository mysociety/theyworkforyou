CREATE TABLE `alerts` (
  `alert_id` mediumint(8) unsigned NOT NULL auto_increment,
  `email` varchar(255) NOT NULL default '',
  `criteria` varchar(255) NOT NULL default '',
  `deleted` tinyint(1) NOT NULL default '0',
  `registrationtoken` varchar(34) NOT NULL default '',
  `confirmed` tinyint(1) NOT NULL default '0',
  `created` datetime NOT NULL default '0000-00-00 00:00:00',
  PRIMARY KEY  (`alert_id`),
  KEY `email` (`email`),
  KEY `confirmed` (`confirmed`,`deleted`)
);

CREATE TABLE `anonvotes` (
  `epobject_id` int(10) unsigned NOT NULL default '0',
  `yes_votes` int(10) unsigned NOT NULL default '0',
  `no_votes` int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (`epobject_id`)
);

CREATE TABLE `commentreports` (
  `report_id` int(11) NOT NULL auto_increment,
  `comment_id` int(11) default NULL,
  `user_id` int(11) default NULL,
  `body` text,
  `reported` datetime default NULL,
  `resolved` datetime default NULL,
  `resolvedby` int(11) default NULL,
  `locked` datetime default NULL,
  `lockedby` int(11) default NULL,
  `upheld` tinyint(1) NOT NULL default '0',
  `firstname` varchar(50) default NULL,
  `lastname` varchar(50) default NULL,
  `email` varchar(100) default NULL,
  PRIMARY KEY  (`report_id`)
);

CREATE TABLE `comments` (
  `comment_id` int(11) NOT NULL auto_increment,
  `user_id` int(11) NOT NULL default '0',
  `epobject_id` int(11) NOT NULL default '0',
  `body` text,
  `posted` datetime default NULL,
  `modflagged` datetime default NULL,
  `visible` tinyint(1) NOT NULL default '0',
  `original_gid` varchar(60) default NULL,
  PRIMARY KEY  (`comment_id`),
  KEY `user_id` (`user_id`,`epobject_id`,`visible`),
  KEY `epobject_id` (`epobject_id`,`visible`),
  KEY `visible` (`visible`)
);

CREATE TABLE `editqueue` (
  `edit_id` int(11) NOT NULL auto_increment,
  `user_id` int(11) default NULL,
  `edit_type` int(11) default NULL,
  `epobject_id_l` int(11) default NULL,
  `epobject_id_h` int(11) default NULL,
  `glossary_id` int(11) default NULL,
  `time_start` datetime default NULL,
  `time_end` datetime default NULL,
  `title` varchar(255) default NULL,
  `body` text,
  `submitted` datetime default NULL,
  `editor_id` int(11) default NULL,
  `approved` tinyint(1) default NULL,
  `decided` datetime default NULL,
  `reason` varchar(255) default NULL,
  PRIMARY KEY  (`edit_id`),
  KEY `approved` (`approved`),
  KEY `glossary_id` (`glossary_id`)
);

CREATE TABLE `glossary` (
  `glossary_id` int(11) NOT NULL auto_increment,
  `title` varchar(255) default NULL,
  `body` text,
  `wikipedia` varchar(255) default NULL,
  `created` datetime default NULL,
  `last_modified` datetime default NULL,
  `type` int(2) default NULL,
  `visible` tinyint(4) default NULL,
  PRIMARY KEY  (`glossary_id`),
  KEY `visible` (`visible`)
);

CREATE TABLE `search_query_log` (
  `id` int(11) NOT NULL auto_increment,
  `query_string` text,
  `page_number` int(11) default NULL,
  `count_hits` int(11) default NULL,
  `ip_address` text,
  `query_time` datetime default NULL,
  PRIMARY KEY  (`id`),
  KEY `query_time` (`query_time`)
);

CREATE TABLE `titles` (
  `title` varchar(190) NOT NULL default '',
  PRIMARY KEY  (`title`)
);

CREATE TABLE `trackbacks` (
  `trackback_id` int(11) NOT NULL auto_increment,
  `epobject_id` int(11) default NULL,
  `blog_name` varchar(255) default NULL,
  `title` varchar(255) default NULL,
  `excerpt` varchar(255) default NULL,
  `url` varchar(255) default NULL,
  `posted` datetime default NULL,
  `visible` tinyint(1) NOT NULL default '0',
  `source_ip` varchar(20) default NULL,
  PRIMARY KEY  (`trackback_id`),
  KEY `visible` (`visible`)
);

CREATE TABLE `users` (
  `user_id` mediumint(9) NOT NULL auto_increment,
  `firstname` varchar(255) NOT NULL default '',
  `lastname` varchar(255) NOT NULL default '',
  `email` varchar(255) NOT NULL default '',
  `password` varchar(34) NOT NULL default '',
  `lastvisit` datetime NOT NULL default '0000-00-00 00:00:00',
  `registrationtime` datetime NOT NULL default '0000-00-00 00:00:00',
  `registrationip` varchar(20) default NULL,
  `status` enum('Viewer','User','Moderator','Administrator','Superuser') default 'Viewer',
  `emailpublic` tinyint(1) NOT NULL default '0',
  `optin` tinyint(1) NOT NULL default '0',
  `deleted` tinyint(1) NOT NULL default '0',
  `postcode` varchar(10) NOT NULL default '',
  `registrationtoken` varchar(24) NOT NULL default '',
  `confirmed` tinyint(1) NOT NULL default '0',
  `url` varchar(255) NOT NULL default '',
  PRIMARY KEY  (`user_id`),
  KEY `email` (`email`)
);

CREATE TABLE `uservotes` (
  `user_id` int(10) unsigned NOT NULL default '0',
  `epobject_id` int(11) NOT NULL default '0',
  `vote` tinyint(1) NOT NULL default '0',
  KEY `epobject_id` (`epobject_id`,`vote`)
);

--
-- Data from XML files etc.
--

CREATE TABLE `consinfo` (
  `constituency` varchar(100) collate latin1_spanish_ci NOT NULL default '',
  `data_key` varchar(100) NOT NULL default '',
  `data_value` text NOT NULL,
  UNIQUE KEY `consinfo_constituency_data_key` (`constituency`,`data_key`),
  KEY `constituency` (`constituency`),
  KEY `consinfo_data_key_data_value` (`data_key`, `data_value`(100))
);

CREATE TABLE `constituency` (
  `name` varchar(100) collate latin1_spanish_ci NOT NULL default '',
  `main_name` tinyint(1) NOT NULL default '0',
  `from_date` date NOT NULL default '1000-01-01',
  `to_date` date NOT NULL default '9999-12-31',
  `cons_id` int(11) default NULL,
  UNIQUE KEY `constituency_cons_id_name` (`cons_id`,`name`),
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
  `gid_from` varchar(100) default NULL,
  `gid_to` varchar(100) default NULL,
  `hdate` date NOT NULL default '0000-00-00',
  `major` int(11) default NULL,
  UNIQUE KEY `gid_from` (`gid_from`),
  KEY `gid_to` (`gid_to`)
);

CREATE TABLE `hansard` (
  `epobject_id` int(11) NOT NULL default '0',
  `gid` varchar(100) default NULL,
  `htype` int(11) NOT NULL default '0',
  `person_id` int(11) NOT NULL default '0',
  `major` int(11) NOT NULL default '0',
  `section_id` int(11) NOT NULL default '0',
  `subsection_id` int(11) NOT NULL default '0',
  `hpos` int(11) NOT NULL default '0',
  `hdate` date NOT NULL default '0000-00-00',
  `htime` time default NULL,
  `source_url` varchar(255) NOT NULL default '',
  `minor` int(11) default NULL,
  `created` datetime default NULL,
  `modified` datetime default NULL,
  `colnum` mediumint(8) default NULL,
  `video_status` tinyint(1) NOT NULL default '0',
  PRIMARY KEY  (`epobject_id`),
  UNIQUE KEY `gid` (`gid`),
  KEY `epobject_id` (`epobject_id`),
  KEY `subsection_id` (`subsection_id`),
  KEY `section_id` (`section_id`),
  KEY `hdate` (`hdate`),
  KEY `person_id` (`person_id`),
  KEY `major` (`major`),
  KEY `htype` (`htype`),
  KEY `majorhdate` (`major`,`hdate`),
  KEY `modified` (`modified`),
  KEY `source_url` (`source_url`),
  KEY `video_status` (`video_status`),
  KEY `hansard_person_id_hdate_hpos` (`person_id`,`hdate`,`hpos`),
  KEY `hansard_major_minor_htype` (`major`,`minor`,`htype`)
);

CREATE TABLE `member` (
  `member_id` int(11) NOT NULL default '0',
  `house` int(11) default NULL,
  `constituency` varchar(100) collate latin1_spanish_ci NOT NULL default '',
  `party` varchar(100) collate latin1_spanish_ci NOT NULL default '',
  `entered_house` date NOT NULL default '1000-01-01',
  `left_house` date NOT NULL default '9999-12-31',
  `entered_reason` enum('unknown','general_election','by_election','changed_party','changed_name','reinstated','appointed','devolution','election','accession','regional_election','replaced_in_region','became_presiding_officer', 'general_election_probably') collate latin1_spanish_ci NOT NULL default 'unknown',
  `left_reason` enum('unknown','still_in_office','general_election','general_election_standing','general_election_not_standing','changed_party','changed_name','died','declared_void','resigned','disqualified','became_peer','devolution','dissolution','retired','regional_election','became_presiding_officer') collate latin1_spanish_ci NOT NULL default 'unknown',
  `person_id` int(11) NOT NULL default '0',
  `lastupdate` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  PRIMARY KEY  (`member_id`),
  KEY `person_id` (`person_id`),
  KEY `constituency` (`constituency`),
  KEY `house` (`house`),
  KEY `left_house_house` (`left_house`,`house`)
);
ALTER TABLE `member` COLLATE latin1_spanish_ci;

CREATE TABLE `person_names` (
  `id` int(11) NOT NULL auto_increment,
  `person_id` int(11) NOT NULL,
  `title` varchar(50) collate latin1_spanish_ci NOT NULL default '',
  `given_name` varchar(100) collate latin1_spanish_ci NOT NULL default '',
  `family_name` varchar(255) collate latin1_spanish_ci NOT NULL default '',
  `lordofname` varchar(100) collate latin1_spanish_ci NOT NULL default '',
  `start_date` date NOT NULL default '1000-01-01',
  `end_date` date NOT NULL default '9999-12-31',
  `type` enum('name', 'alias') not null default 'name',
  PRIMARY KEY  (`id`),
  KEY `person_id_type_start_date_end_date` (`person_id`,`type`,`start_date`,`end_date`)
);

CREATE TABLE `memberinfo` (
  `member_id` int(11) NOT NULL default '0',
  `data_key` varchar(100) NOT NULL default '',
  `data_value` text NOT NULL,
  `lastupdate` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  UNIQUE KEY `memberinfo_member_id_data_key` (`member_id`,`data_key`),
  KEY `member_id` (`member_id`)
);

CREATE TABLE `policies` (
  `policy_id` varchar(100) NOT NULL default '',
  `title` text NOT NULL,
  `description` text NOT NULL,
  `image` varchar(200) default '',
  `image_attrib` varchar(200) default '',
  `image_license` varchar(200) default '',
  `image_license_url` text,
  `image_source` text,
  UNIQUE KEY `policy_id` (`policy_id`)
);

CREATE TABLE `policydivisions` (
  `division_id` varchar(100) NOT NULL,
  `policy_id` varchar(100) NOT NULL default '',
  `house` varchar(100),
  `gid` varchar(100) default '',
  `direction` enum( 'Majority', 'Majority (strong)', 'minority', 'minority (strong)', 'absent', 'both', 'abstention', 'spoiled'),
  `division_title` text NOT NULL,
  `yes_text` text,
  `no_text` text,
  `division_date` date NOT NULL default '1000-01-01',
  `division_number` int(11),
  `policy_vote` enum('aye', 'aye3', 'no', 'no3', 'both', 'absent', '') default '',
  `yes_total` int(3) default 0,
  `no_total` int(3) default 0,
  `absent_total` int(3) default 0,
  `both_total` int(3) default 0,
  `majority_vote` enum('aye', 'no', '') default '',
  `lastupdate` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  UNIQUE KEY `policy_division` (`division_id`, `policy_id`),
  KEY `division_id` (`division_id`)
);

CREATE TABLE `persondivisionvotes` (
  `person_id` int(11) NOT NULL default '0',
  `division_id` varchar(100) NOT NULL default '',
  `vote` enum("yes", "aye", "no", "both", "tellaye", "tellno", "absent", "spoiled") NOT NULL,
  `lastupdate` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  UNIQUE KEY `person_vote` (`person_id`, `division_id`),
  KEY `division_id` (`division_id`),
  KEY `person_id` (`person_id`)
);

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

CREATE TABLE `moffice` (
  `moffice_id` varchar(100) NOT NULL,
  `dept` varchar(255) NOT NULL default '',
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
  `lastupdate` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  UNIQUE KEY `personinfo_person_id_data_key` (`person_id`,`data_key`),
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

-- For Public Bill Committees originally
CREATE TABLE `bills` (
  `id` int(11) NOT NULL auto_increment,
  `title` varchar(255) NOT NULL,
  `url` varchar(255) NOT NULL,
  `type` enum('unknown','government','consolidation','pmb-ballot','pmb-tenminute','pmb-presentation','pmb-lords','private','hybrid') NOT NULL default 'unknown',
  `lords` tinyint(1) NOT NULL,
  `session` varchar(50) NOT NULL,
  `standingprefix` varchar(255) NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `title` (`title`)
);

CREATE TABLE `pbc_members` (
  `id` int(11) NOT NULL auto_increment,
  `person_id` int(11) NOT NULL,
  `chairman` tinyint(1) NOT NULL,
  `bill_id` int(11) NOT NULL,
  `sitting` varchar(4) NOT NULL,
  `attending` tinyint(1) NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `person_id` (`person_id`),
  KEY `bill_id` (`bill_id`)
);

CREATE TABLE `titles` (
  `title` varchar(190) NOT NULL default '',
  PRIMARY KEY  (`title`)
);

CREATE TABLE `titles_ignored` (
  `title` varchar(190) NOT NULL default '',
  PRIMARY KEY  (`title`)
);

CREATE TABLE `future` (
  `id` int(11) NOT NULL,
  `created` timestamp NOT NULL default CURRENT_TIMESTAMP,
  `modified` datetime NOT NULL,
  `deleted` tinyint(1) NOT NULL,
  `link_calendar` varchar(255) NOT NULL,
  `link_external` varchar(255) NOT NULL,
  `body` varchar(20) NOT NULL,
  `chamber` varchar(255) NOT NULL,
  `event_date` date NOT NULL,
  `time_start` time,
  `time_end` time,
  -- One of the following two will be blank, possibly both
  `committee_name` varchar(255) NOT NULL,
  `debate_type` varchar(255) NOT NULL,
  `title` mediumtext NOT NULL,
  `witnesses` mediumtext NOT NULL,
  `location` varchar(255) NOT NULL,
  `pos` int(11) NOT NULL default '0',
  PRIMARY KEY  (`id`),
  KEY `event_date` (`event_date`),
  KEY `modified` (`modified`)
) CHARACTER SET 'utf8';

CREATE TABLE `future_people` (
  `calendar_id` int(11) NOT NULL,
  `person_id` int(11) NOT NULL,
  `witness` tinyint(1) NOT NULL,
  KEY `calendar_id` (`calendar_id`)
);

--
-- User content tables
--

CREATE TABLE `alerts` (
  `alert_id` mediumint(8) unsigned NOT NULL auto_increment,
  `email` varchar(255) NOT NULL default '',
  `criteria` varchar(255) NOT NULL default '',
  `deleted` tinyint(1) NOT NULL default '0',
  `registrationtoken` varchar(34) NOT NULL default '',
  `confirmed` tinyint(1) NOT NULL default '0',
  `created` datetime NOT NULL default '0000-00-00 00:00:00',
  `postcode` varchar(10) NOT NULL default '',
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
  `title` varchar(255) collate utf8_general_ci default NULL,
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
  `password` varchar(102) NOT NULL default '',
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
  `api_key` char(24) default NULL,
  `facebook_id` char(24) default NULL,
  `facebook_token` char(200) default NULL,
  PRIMARY KEY  (`user_id`),
  UNIQUE KEY `api_key` (`api_key`),
  KEY `email` (`email`)
);

CREATE TABLE `uservotes` (
  `user_id` int(10) unsigned NOT NULL default '0',
  `epobject_id` int(11) NOT NULL default '0',
  `vote` tinyint(1) NOT NULL default '0',
  KEY `epobject_id` (`epobject_id`,`vote`)
);

CREATE TABLE `mentions` (
  `mention_id` int(11) NOT NULL auto_increment,
  `gid` varchar(100) default NULL,
  `type` int(11) NOT NULL,
  `date` date default NULL,
  `url` varchar(255) default NULL,
  `mentioned_gid` varchar(100) default NULL,
  UNIQUE KEY `all_values` (`gid`,`type`,`date`,`url`,`mentioned_gid`),
  PRIMARY KEY (`mention_id`)
);

-- Free Our Bills
CREATE TABLE `campaigners` (
  `campaigner_id` mediumint(8) unsigned NOT NULL auto_increment,
  `email` varchar(255) NOT NULL default '',
  `postcode` varchar(255) NOT NULL default '',
  `constituency` varchar(100) NOT NULL default '',
  `token` varchar(255) NOT NULL default '',
  `confirmed` tinyint(1) NOT NULL default '0',
  `signup_date` datetime NOT NULL,
  PRIMARY KEY  (`campaigner_id`),
  KEY `email` (`email`),
  KEY `confirmed` (`confirmed`),
  KEY `constituency` (`constituency`)
);

-- who each email has been sent to so far
CREATE TABLE `campaigners_sent_email` (
  `campaigner_id` int(11) NOT NULL,
  `email_name` varchar(100) NOT NULL,

  UNIQUE KEY `campaigner_id` (`campaigner_id`,`email_name`)
);

CREATE TABLE `video_timestamps` (
  `id` int(11) NOT NULL auto_increment,
  `gid` varchar(100) NOT NULL,
  `user_id` int(11) default NULL,
  `adate` date NOT NULL default '0000-00-00',
  `atime` time NOT NULL,
  `deleted` tinyint(1) NOT NULL default '0',
  `whenstamped` timestamp NOT NULL default CURRENT_TIMESTAMP,
  PRIMARY KEY  (`id`),
  KEY `gid` (`gid`),
  KEY `deleted` (`deleted`),
  KEY `user_id` (`user_id`)
);

CREATE TABLE `api_key` (
  `id` int(11) NOT NULL auto_increment,
  `user_id` mediumint(9) NOT NULL,
  `api_key` char(24) NOT NULL,
  `commercial` tinyint(1) NOT NULL,
  `created` datetime NOT NULL,
  `disabled` datetime default NULL,
  `reason` text NOT NULL,
  `estimated_usage` int(9) NOT NULL default 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `api_key` (`api_key`)
);

CREATE TABLE `api_stats` (
  `id` int(11) NOT NULL auto_increment,
  `api_key` char(24) NOT NULL,
  `ip_address` varchar(16) NOT NULL,
  `query_time` datetime NOT NULL,
  `query` text NOT NULL,
  PRIMARY KEY (`id`),
  KEY `api_key` (`api_key`),
  KEY `query_time` (`query_time`)
);

CREATE TABLE `survey` (
  `shown` int(11) NOT NULL default '0',
  `yes` int(11) NOT NULL default '0',
  `no` int(11) NOT NULL default '0'
);

CREATE TABLE `tokens` (
    `token` varchar(100) NOT NULL default '',
    `type` varchar(1) NOT NULL default '',
    `data` text NOT NULL default '',
    `expires` datetime,
    PRIMARY KEY (`token`)
);

-- For things like banners, front page highlighting etc --

CREATE TABLE `editorial` (
    `item` varchar(50) NOT NULL,
    `value` text,
    PRIMARY KEY `item` (`item`)
);


CREATE TABLE `topics` (
    `id` int(11) NOT NULL auto_increment,
    `slug` varchar(100) NOT NULL,
    `title` text NOT NULL,
    `description` text,
    `image` text default '',
    `search_string` text,
    `front_page` bool DEFAULT FALSE,
    PRIMARY KEY (`id`),
    UNIQUE KEY `slug` (`slug`)
);

CREATE TABLE `topic_policysets` (
  `topic_key` int(11) NOT NULL,
  `policyset` varchar(30) NOT NULL,
  UNIQUE KEY `topic_policyset` (`topic_key`, `policyset`)
);

CREATE TABLE `topic_policies` (
  `topic_key` int(11) NOT NULL,
  `policy_id` int(11) NOT NULL,
  UNIQUE KEY `topic_policy` (`topic_key`, `policy_id`)
);

CREATE TABLE `topic_epobjects` (
  `topic_key` int(11) NOT NULL,
  `epobject_id` int(11) NOT NULL,
  UNIQUE KEY `topic_object` (`topic_key`, `epobject_id`)
);


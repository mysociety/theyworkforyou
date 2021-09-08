CREATE TABLE `known_absences` (
  `id` int(11) NOT NULL auto_increment,
  `person_id` int(11) NOT NULL default '0',
  `start_date` date NOT NULL default '1000-01-01',
  `end_date` date NOT NULL default '9999-12-31',
  `desc` varchar(100) NOT NULL default '',
  PRIMARY KEY (`id`),
  KEY `person_id` (`person_id`)
);

CREATE TABLE `cohort_assignments`(
  `person_id` int(11) NOT NULL default '0',
  `cohort_hash` varchar(100) NOT NULL default '',
  PRIMARY KEY (`person_id`)
);
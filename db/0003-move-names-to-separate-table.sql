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

INSERT INTO person_names (person_id, title, given_name, family_name, start_date, end_date) SELECT person_id, title, first_name, last_name, entered_house, left_house FROM member WHERE house!=2;
INSERT INTO person_names (person_id, title, given_name, family_name, lordofname, start_date, end_date) SELECT person_id, title, first_name, last_name, constituency, entered_house, left_house FROM member WHERE house=2;
UPDATE member SET constituency='' WHERE house=2;

ALTER TABLE member DROP first_name, DROP last_name, DROP title;

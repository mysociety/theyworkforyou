
CREATE TABLE `policydivisionlink` (
  `id` int(11) NOT NULL auto_increment,
  `policy_id` varchar(100) NOT NULL default '',
  `division_id` varchar(100) NOT NULL default '',
  `direction` enum('agree', 'against', 'neutral') NOT NULL default 'neutral',
  `strength` enum('weak', 'strong') NOT NULL default 'weak',
  `lastupdate` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  UNIQUE KEY `policy_division` (`policy_id`, `division_id`),
  KEY `id` (`id`)
);

CREATE TABLE `policyorganization` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `slug` VARCHAR(255) NOT NULL,
  `name` VARCHAR(255) NOT NULL,
  `classification` VARCHAR(255) NOT NULL,
  PRIMARY KEY (`id`)
);
CREATE TABLE `policycomparisonperiod` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `slug` VARCHAR(255) NOT NULL,
  `description` TEXT NOT NULL,
  `start_date` DATE NOT NULL,
  `end_date` DATE NOT NULL,
  `chamber_id` INT(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
);
CREATE TABLE `policyvotedistribution` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `policy_id` INT(11) NOT NULL DEFAULT 0,
  `person_id` INT(11) NOT NULL DEFAULT 0,
  `period_id` INT(4) NOT NULL DEFAULT 0,
  `chamber_id` INT(4) NOT NULL DEFAULT 0,
  `party_id` INT(4) DEFAULT NULL,
  `is_target` TINYINT(1) NOT NULL DEFAULT 0,
  `num_votes_same` FLOAT NOT NULL DEFAULT 0,
  `num_strong_votes_same` FLOAT NOT NULL DEFAULT 0,
  `num_votes_different` FLOAT NOT NULL DEFAULT 0,
  `num_strong_votes_different` FLOAT NOT NULL DEFAULT 0,
  `num_votes_absent` FLOAT NOT NULL DEFAULT 0,
  `num_strong_votes_absent` FLOAT NOT NULL DEFAULT 0,
  `num_votes_abstain` FLOAT NOT NULL DEFAULT 0,
  `num_strong_votes_abstain` FLOAT NOT NULL DEFAULT 0,
  `num_agreements_same` FLOAT NOT NULL DEFAULT 0,
  `num_strong_agreements_same` FLOAT NOT NULL DEFAULT 0,
  `num_agreements_different` FLOAT NOT NULL DEFAULT 0,
  `num_strong_agreements_different` FLOAT NOT NULL DEFAULT 0,
  `start_year` INT(4) NOT NULL DEFAULT 0,
  `end_year` INT(4) NOT NULL DEFAULT 0,
  `distance_score` FLOAT NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `party_policy` (`person_id`, `policy_id`, `period_id`, `chamber_id`, `party_id`, `is_target`)
);
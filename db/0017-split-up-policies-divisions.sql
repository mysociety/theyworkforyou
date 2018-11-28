CREATE TABLE `divisions` (
  `division_id` varchar(100) NOT NULL,
  `house` varchar(100),
  `gid` varchar(100) default '',
  `division_title` text NOT NULL,
  `yes_text` text,
  `no_text` text,
  `division_date` date NOT NULL default '1000-01-01',
  `division_number` int(11),
  `yes_total` int(3) default 0,
  `no_total` int(3) default 0,
  `absent_total` int(3) default 0,
  `both_total` int(3) default 0,
  `majority_vote` enum('aye', 'no', '') default '',
  `lastupdate` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  UNIQUE KEY `division_id` (`division_id`),
  KEY `gid` (`gid`)
);

INSERT INTO divisions (
    division_id,
    house, gid, division_title,
    yes_text, no_text, division_date, division_number,
    yes_total, no_total, absent_total, both_total, majority_vote)
SELECT DISTINCT
    division_id,
    house, gid, division_title,
    yes_text, no_text, division_date, division_number,
    yes_total, no_total, absent_total, both_total, majority_vote
    FROM policydivisions;

ALTER TABLE policydivisions
    DROP house, DROP gid, DROP division_title,
    DROP yes_text, DROP no_text, DROP division_date, DROP division_number,
    DROP yes_total, DROP no_total, DROP absent_total, DROP both_total, DROP majority_vote;

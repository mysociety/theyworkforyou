-- Type the office rows so committees can be told from government posts without
-- string-matching the moffice_id, and link them to the organization table.
-- position/position_cy are the English and Welsh forms of the role.

ALTER TABLE `moffice`
  ADD COLUMN `org_id` varchar(150) default NULL,
  ADD COLUMN `post_type` varchar(30) NOT NULL default 'other',
  ADD COLUMN `parliament` varchar(20) NOT NULL default '',
  ADD COLUMN `position_cy` varchar(255) NOT NULL default '',
  ADD COLUMN `loader` varchar(30) NOT NULL default '',
  MODIFY COLUMN `position` varchar(255) NOT NULL default '',
  ADD KEY `org_id` (`org_id`),
  ADD KEY `post_type_parliament` (`post_type`, `parliament`);

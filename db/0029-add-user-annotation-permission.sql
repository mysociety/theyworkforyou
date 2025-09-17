ALTER TABLE `users`
  ADD COLUMN `can_annotate` tinyint(1) NOT NULL default 0,
  ADD COLUMN `organisation` varchar(255) default NULL;

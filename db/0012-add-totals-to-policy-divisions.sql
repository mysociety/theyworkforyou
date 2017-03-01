ALTER TABLE policydivisions
  ADD COLUMN `yes_total` int(3) default 0,
  ADD COLUMN `no_total` int(3) default 0,
  ADD COLUMN `absent_total` int(3) default 0,
  ADD COLUMN `both_total` int(3) default 0,
  ADD COLUMN `majority_vote` enum('aye', 'no', '') default '';

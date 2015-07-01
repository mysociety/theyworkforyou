ALTER TABLE `policydivisions` ADD COLUMN `policy_vote` enum('aye', 'aye3', 'no', 'no3', 'both', 'absent', '') default '';

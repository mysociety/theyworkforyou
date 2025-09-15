-- Add new enum values 'whip_removed' and 'whip_restored' to entered_reason and left_reason columns
-- Add new source column to member table

ALTER TABLE `member` 
  MODIFY COLUMN `entered_reason` enum('', 'unknown','general_election','by_election','changed_party','changed_name','reinstated','appointed','devolution','election','accession','regional_election','replaced_in_region','became_presiding_officer', 'general_election_probably','whip_removed','whip_restored') NOT NULL default 'unknown',
  MODIFY COLUMN `left_reason` enum('', 'unknown','still_in_office','general_election','general_election_standing','general_election_not_standing','changed_party','changed_name','died','declared_void','resigned','disqualified','became_peer','devolution','dissolution','retired','regional_election','became_presiding_officer','recall_petition','whip_removed','whip_restored') NOT NULL default 'unknown',
  ADD COLUMN `source` varchar(255) NOT NULL default '';
ALTER TABLE `member`
MODIFY COLUMN `left_reason`
  enum('unknown','still_in_office','general_election','general_election_standing','general_election_not_standing','changed_party','changed_name','died','declared_void','resigned','disqualified','became_peer','devolution','dissolution','retired','regional_election','became_presiding_officer','recall_petition') collate latin1_spanish_ci NOT NULL default 'unknown';

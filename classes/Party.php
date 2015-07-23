<?php
/**
 * Party Class
 *
 * @package TheyWorkForYou
 */

namespace MySociety\TheyWorkForYou;

/**
 * Party
 */

class Party {

    public $name;

    private $db;

    public function __construct($name) {
        // treat Labour and Labour/Co-operative the same as that's how
        // people view them and it'll confuse the results otherwise
        if ( $name == 'Labour/Co-operative' ) {
            $name = 'Labour';
        }
        $this->name = $name;
        $this->db = new \ParlDB;
    }


    public function getAllPolicyPositions($policies) {
        $positions = $this->fetchPolicyPositionsByMethod($policies, "policy_position");

        return $positions;
    }

    public function policy_position($policy_id, $want_score = false) {
        $position = $this->db->query(
            "SELECT score
            FROM partypolicy
            WHERE
                party = :party
                AND house = 1
                AND policy_id = :policy_id",
            array(
                ':party' => $this->name,
                ':policy_id' => $policy_id
            )
        );

        if ( $position->rows ) {
            $score = $position->field(0, 'score');
            $score_desc = score_to_strongly($score);

            if ( $want_score ) {
                return array( $score_desc, $score);
            } else {
                return $score_desc;
            }
        } else {
            return null;
        }
    }

    public function calculateAllPolicyPositions($policies) {
        $positions = $this->fetchPolicyPositionsByMethod($policies, "calculate_policy_position");

        return $positions;
    }

    public function calculate_policy_position($policy_id, $want_score = false) {

        // This could be done as a join but it turns out to be
        // much slower to do that
        $divisions = $this->db->query(
            "SELECT division_id, policy_vote, division_date
            FROM policydivisions
            WHERE policy_id = :policy_id
            AND house = 'commons'",
            array(
                ':policy_id' => $policy_id
            )
        );

        $score = 0;
        $max_score = 0;
        $num_divs = $divisions->rows;
        $total_votes = 0;

        $party_where = 'party = :party';
        $params = array(
            ':party' => $this->name
        );

        if ( $this->name == 'Labour' ) {
            $party_where = 'party = :party OR party = :party2';
            $params = array(
                ':party' => $this->name,
                ':party2' => 'Labour/Co-operative'
            );
        }

        for ( $i = 0; $i < $num_divs; $i++ ) {
            $division_id = $divisions->field($i, 'division_id');
            $weights = $this->get_vote_scores($divisions->field($i, 'policy_vote'));

            $params[':division_id'] = $division_id;

            $votes = $this->db->query(
                "SELECT count(*) as num_votes, vote
                FROM persondivisionvotes
                JOIN member ON persondivisionvotes.person_id = member.person_id
                WHERE
                    $party_where
                    AND member.house = 1
                    AND division_id = :division_id
                    AND left_house = '9999-12-31'
                GROUP BY vote",
                $params
            );

            $num_votes = 0;
            for ( $j = 0; $j < $votes->rows(); $j++ ) {
                $vote_dir = $votes->field($j, 'vote');
                if ( $vote_dir == '' ) continue;
                if ( $vote_dir == 'tellno' ) $vote_dir = 'no';
                if ( $vote_dir == 'tellaye' ) $vote_dir = 'aye';

                $num_votes += $votes->field($j, 'num_votes');
                $score += ($votes->field($j, 'num_votes') * $weights[$vote_dir]);
            }

            $total_votes += $num_votes;
            $max_score += $num_votes * max( array_values( $weights ) );
        }

        if ( $total_votes == 0 ) {
            return null;
        }

        // this implies that all the divisions in the policy have a policy
        // position of absent so we set weight to -1 to indicate we can't
        // really say what the parties position is.
        if ( $max_score == 0 ) {
            $weight = -1;
        } else {
            $weight = 1 - ( $score/$max_score );
        }
        $score_desc = score_to_strongly($weight);

        if ( $want_score ) {
            return array( $score_desc, $weight);
        } else {
            return $score_desc;
        }
    }

    public function cache_position( $position ) {
        $this->db->query(
            "REPLACE INTO partypolicy
                (party, house, policy_id, score)
                VALUES (:party, 1, :policy_id, :score)",
            array(
                ':score' => $position['score'],
                ':party' => $this->name,
                ':policy_id' => $position['policy_id']
            )
        );
    }

    private function fetchPolicyPositionsByMethod($policies, $method) {
        $positions = array();

        foreach ( $policies->getPolicies() as $policy_id => $policy_text ) {
            list( $position, $score ) = $this->$method($policy_id, true);
            if ( $position === null ) {
                continue;
            }

            $positions[$policy_id] = array(
                'policy_id' => $policy_id,
                'position' => $position,
                'score' => $score,
                'desc' => $policy_text
            );
        }

        return $positions;
    }

    private function get_vote_scores($vote) {
        $absent = 1;
        $both = 1;
        $agree = 10;

        if ( stripos($vote, '3') !== FALSE ) {
            $agree = 50;
            $absent = 25;
            $both = 25;
        }

        $scores = array(
            'absent' => $absent,
            'both' => $both
        );

        if ( stripos($vote, 'aye') !== FALSE ) {
            $scores['aye'] = $agree;
            $scores['no'] = 0;
        } else if ( stripos($vote, 'no') !== FALSE ) {
            $scores['no'] = $agree;
            $scores['aye'] = 0;
        } else {
            $scores['both'] = 0;
            $scores['absent'] = 0;
            $scores['no'] = 0;
            $scores['aye'] = 0;
        }

        return $scores;
    }

    public static function getParties() {
        $db = new \ParlDB;

        $party_list = $db->query(
            "SELECT DISTINCT party FROM member WHERE party <> ''"
        );

        $parties = array();
        $party_count = $party_list->rows;

        for ( $i = 0; $i < $party_count; $i++ ) {
            $party = $party_list->field($i, 'party');
            if (
                !$party
                || $party == 'Independent'
                || $party == 'Crossbench'
            ) {
                continue;
            }
            $parties[] = $party;
        }

        return $parties;
    }
}

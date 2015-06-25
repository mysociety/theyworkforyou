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
        $this->name = $name;
        $this->db = new \ParlDB;
    }

    public function policy_position($policy_id) {

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
        $scores = array();
        $num_divs = $divisions->rows;

        for ( $i = 0; $i < $num_divs; $i++ ) {
            $division_id = $divisions->field($i, 'division_id');
            $weights = $this->get_vote_scores($divisions->field($i, 'policy_vote'));

            $votes = $this->db->query(
                "SELECT count(*) as num_votes, vote
                FROM persondivisionvotes
                JOIN member ON persondivisionvotes.person_id = member.person_id
                WHERE
                    party = :party
                    AND division_id = :division_id
                    AND entered_house <= :division_date
                    AND left_house >= :division_date
                GROUP BY vote",
                array(
                    ':party' => $this->name,
                    ':division_id' => $division_id,
                    ':division_date' => $divisions->field($i, 'division_date')
                )
            );

            $num_votes = 0;
            for ( $j = 0; $j < $votes->rows(); $j++ ) {
                $vote_dir = $votes->field($j, 'vote');
                if ( $vote_dir == 'tellno' ) $vote_dir = 'no';
                if ( $vote_dir == 'tellaye' ) $vote_dir = 'aye';

                $num_votes += $votes->field($j, 'num_votes');
                $score += ($votes->field($j, 'num_votes') * $weights[$vote_dir]);
            }

            $max_score += $num_votes * max( array_values( $weights ) );
        }

        if ( $max_score == 0 ) {
            $max_score = 1;
        }
        $weight = 1 - ( $score/$max_score );
        return score_to_strongly($weight);
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

}

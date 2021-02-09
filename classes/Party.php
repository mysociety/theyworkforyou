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

    public function getCurrentMemberCount($house) {
        $member_count = $this->db->query(
            "SELECT count(*) as num_members
            FROM member
            WHERE
                party = :party
                AND house = :house
                AND entered_house <= :date
                AND left_house >= :date",
            array(
                ':party' => $this->name,
                ':house' => $house,
                ':date' => date('Y-m-d'),
            )
        )->first();
        if ($member_count) {
            $num_members = $member_count['num_members'];
            return $num_members;
        } else {
            return 0;
        }
    }

    public function getAllPolicyPositions($policies) {
        $positions = $this->fetchPolicyPositionsByMethod($policies, "policy_position");

        return $positions;
    }

    public function policy_position($policy_id) {
        $position = $this->db->query(
            "SELECT score, divisions, date_min, date_max
            FROM partypolicy
            WHERE
                party = :party
                AND house = 1
                AND policy_id = :policy_id",
            array(
                ':party' => $this->name,
                ':policy_id' => $policy_id
            )
        )->first();

        if ($position) {
            $position['position'] = score_to_strongly($position['score']);
            return $position;
        } else {
            return null;
        }
    }

    public function calculateAllPolicyPositions($policies) {
        $positions = $this->fetchPolicyPositionsByMethod($policies, "calculate_policy_position");

        return $positions;
    }

    public function calculate_policy_position($policy_id) {

        // This could be done as a join but it turns out to be
        // much slower to do that
        $divisions = $this->db->query(
            "SELECT division_id, division_date, policy_vote
            FROM policydivisions
                JOIN divisions USING(division_id)
            WHERE policy_id = :policy_id
            AND house = 'commons'",
            array(
                ':policy_id' => $policy_id
            )
        );

        $score = 0;
        $max_score = 0;
        $total_votes = 0;
        $date_min = '';
        $date_max = '';
        $num_divisions = 0;

        $party_where = 'party = :party';
        $params = array(
            ':party' => $this->name
        );

        if ( $this->name == 'Labour' ) {
            $party_where = '( party = :party OR party = :party2 )';
            $params = array(
                ':party' => $this->name,
                ':party2' => 'Labour/Co-operative'
            );
        }

        foreach ($divisions as $division) {
            $division_id = $division['division_id'];
            $weights = $this->get_vote_scores($division['policy_vote']);
            $date = $division['division_date'];

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
            foreach ($votes as $vote) {
                $vote_dir = $vote['vote'];
                if ( $vote_dir == '' ) continue;
                if ( $vote_dir == 'tellno' ) $vote_dir = 'no';
                if ( $vote_dir == 'tellaye' ) $vote_dir = 'aye';

                $num_votes += $vote['num_votes'];
                $score += ($vote['num_votes'] * $weights[$vote_dir]);
            }
            # For range, only care if there were results
            if ($votes->rows()) {
                if (!$date_min || $date_min > $date) $date_min = $date;
                if (!$date_max || $date_max < $date) $date_max = $date;
                $num_divisions++;
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

        return [
            'score' => $weight,
            'position' => $score_desc,
            'divisions' => $num_divisions,
            'date_min' => $date_min,
            'date_max' => $date_max,
        ];
    }

    public function cache_position( $position ) {
        $this->db->query(
            "REPLACE INTO partypolicy
                (party, house, policy_id, score, divisions, date_min, date_max)
                VALUES (:party, 1, :policy_id, :score, :divisions, :date_min, :date_max)",
            array(
                ':score' => $position['score'],
                ':party' => $this->name,
                ':policy_id' => $position['policy_id'],
                ':divisions' => $position['divisions'],
                ':date_min' => $position['date_min'],
                ':date_max' => $position['date_max'],
            )
        );
    }

    private function fetchPolicyPositionsByMethod($policies, $method) {
        $positions = array();

        if ($this->name == 'Independent') {
            return $positions;
        }

        foreach ( $policies->getPolicies() as $policy_id => $policy_text ) {
            $data = $this->$method($policy_id);
            if ( $data === null ) {
                continue;
            }

            $data['policy_id'] = $policy_id;
            $data['desc'] = $policy_text;
            $positions[$policy_id] = $data;
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
        foreach ($party_list as $row) {
            $party = $row['party'];
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

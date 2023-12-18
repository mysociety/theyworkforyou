<?php

/**
 * Party Cohort Class
 *
 * @package TheyWorkForYou
 */

namespace MySociety\TheyWorkForYou;

/**
 * Party
 */

class PartyCohort
{
    private $db;
    private $hash;
    private $memberships;
    private $absences;
    private $party;

    public function __construct($hash, $pop_with_example = false)
    {
        // $pop with example option will retrieve the relevant fields from
        // an representative member of the cohort.
        // treat Labour and Labour/Co-operative the same as that's how
        // people view them and it'll confuse the results otherwise

        $this->hash = $hash;
        $this->db = new \ParlDB;
        $this->memberships = null;
        $this->absences = null;
        $this->party = null;

        if ($pop_with_example) {
            $this->memberships = $this->getMemberships();
            $this->absences = $this->getAbsences();
            $this->party = $this->getParty();
        }
    }

    public function getExamplePerson()
    {
        // get person_id for a typical member of this cohort
        // given by definition all members have the same relevant properties
        $row = $this->db->query(
            "SELECT person_id from cohort_assignments where cohort_hash = :hash",
            array(":hash" => $this->hash)
        )->first();

        if ($row) {
            return $row["person_id"];
        } else {
            return null;
        }
    }

    public function getMemberships()
    {
        // get start and left dates for this membership cohort
        if (!is_null($this->memberships)) {
            return $this->memberships;
        };
        $house_id = 1;
        $person_id = $this->getExamplePerson();
        $memberships = $this->db->query(
            "SELECT person_id, entered_house as start_date, left_house as end_date
             FROM member
             WHERE house = :house_id and person_id = :person_id",
            array(":person_id" => $person_id, ":house_id" => $house_id)
        );
        return $memberships;
    }

    public function getParty()
    {
        // get the party this cohort applies to
        // currently this is the first non null value
        if (!is_null($this->party)) {
            return $this->party;
        };
        $person_id = $this->getExamplePerson();
        $member = new Member(array('person_id' => $person_id));
        return $member->cohortParty();

    }

    public function getAbsences()
    {
        // get any known absences for this membership cohort
        if (!is_null($this->absences)) {
            return $this->absences;
        };
        $person_id = $this->getExamplePerson();

        $absences = $this->db->query(
            "SELECT person_id, start_date, end_date
             FROM known_absences
             WHERE person_id = :person_id",
            array(":person_id" => $person_id)
        );
        return $absences;
    }

    public function getAllPolicyPositions($policies)
    {
        $positions = $this->fetchPolicyPositionsByMethod($policies, "policy_position");

        return $positions;
    }

    public function policy_position($policy_id)
    {
        $position = $this->db->query(
            "SELECT score, divisions, date_min, date_max
            FROM partypolicy
            WHERE
                party = :party
                AND house = 1
                AND policy_id = :policy_id",
            array(
                ':party' => $this->hash,
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

    public function calculateAllPolicyPositions($policies)
    {
        $positions = $this->fetchPolicyPositionsByMethod($policies, "calculate_policy_position");

        return $positions;
    }

    public function calculate_policy_position($policy_id)
    {

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

        $party = $this->getParty();
        $memberships = $this->getMemberships();
        $absences = $this->getAbsences();

        $party_where = 'party = :party';
        $params = array(
            ':party' => $party
        );

        if ($party == 'Labour') {
            // need to get party id function
            $party_where = '( party = :party OR party = :party2 )';
            $params = array(
                ':party' => $party,
                ':party2' => 'Labour/Co-operative'
            );
        }

        foreach ($divisions as $division) {

            $division_id = $division['division_id'];
            $date = $division['division_date'];

            // this bit makes sure we only include divisions in comparison that are part
            // of the allowed cohort
            $in_member_range = false;
            $in_absence_range = false;
            foreach ($memberships as $membership) {
                $start_date = $membership["start_date"];
                $end_date = $membership["end_date"];
                if (($date <= $end_date) && ($date >= $start_date)) {
                    // this division occured within a comparison period
                    $in_member_range = true;
                }
            }

            foreach ($absences as $absence) {
                $start_date = $absence["start_date"];
                $end_date = $absence["end_date"];
                if (($date <= $end_date) && ($date >= $start_date)) {
                    // this division occured within an absence period
                    $in_absence_range = true;
                }
            }

            // if not in range, or is in an absence range
            // do not include this division in the comparison
            if (!$in_member_range || $in_absence_range) {
                continue;
            }

            $weights = $this->get_vote_scores($division['policy_vote']);
            $params[':division_id'] = $division_id;
            $params[':division_date'] = $date;

            # join on the membership a person had at the time in question
            # exclude any absent vote if someone was during a period of known absence
            # this means a cohorts position is calculated based on people who were a member of the
            # party at the time, and were not effectively absent
            $votes = $this->db->query(
                "SELECT count(*) as num_votes, vote
                FROM persondivisionvotes
                JOIN member ON persondivisionvotes.person_id = member.person_id
                WHERE
                    $party_where
                    AND member.house = 1
                    AND division_id = :division_id
                    AND member.entered_house <= :division_date
                    AND member.left_house >= :division_date
                    AND NOT
                    (persondivisionvotes.vote = 'absent' and persondivisionvotes.person_id in 
                        ( SELECT person_id 
                          FROM known_absences
                          WHERE start_date <= :division_date
                          AND end_date >= :division_date
                        )
                    )

                GROUP BY vote",
                $params
            );

            $num_votes = 0;
            foreach ($votes as $vote) {
                $vote_dir = $vote['vote'];
                if ($vote_dir == '') {
                    continue;
                }
                if ($vote_dir == 'tellno') {
                    $vote_dir = 'no';
                }
                if ($vote_dir == 'tellaye') {
                    $vote_dir = 'aye';
                }

                $num_votes += $vote['num_votes'];
                $score += ($vote['num_votes'] * $weights[$vote_dir]);
            }
            # For range, only care if there were results
            if ($votes->rows()) {
                if (!$date_min || $date_min > $date) {
                    $date_min = $date;
                }
                if (!$date_max || $date_max < $date) {
                    $date_max = $date;
                }
                $num_divisions++;
            }

            $total_votes += $num_votes;
            $max_score += $num_votes * max(array_values($weights));
        }

        if ($total_votes == 0) {
            return null;
        }

        // this implies that all the divisions in the policy have a policy
        // position of absent so we set weight to -1 to indicate we can't
        // really say what the parties position is.
        if ($max_score == 0) {
            $weight = -1;
        } else {
            $weight = 1 - ($score / $max_score);
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

    public function cache_position($position)
    {
        $this->db->query(
            "REPLACE INTO partypolicy
                (party, house, policy_id, score, divisions, date_min, date_max)
                VALUES (:party, 1, :policy_id, :score, :divisions, :date_min, :date_max)",
            array(
                ':score' => $position['score'],
                ':party' => $this->hash,
                ':policy_id' => $position['policy_id'],
                ':divisions' => $position['divisions'],
                ':date_min' => $position['date_min'],
                ':date_max' => $position['date_max'],
            )
        );
    }

    private function fetchPolicyPositionsByMethod($policies, $method)
    {
        $positions = array();

        $party = $this->getParty();
        if ($party == 'Independent') {
            return $positions;
        }

        foreach ($policies->getPolicies() as $policy_id => $policy_text) {
            $data = $this->$method($policy_id);
            if ($data === null) {
                continue;
            }

            $data['policy_id'] = $policy_id;
            $data['desc'] = $policy_text;
            $positions[$policy_id] = $data;
        }

        return $positions;
    }

    private function get_vote_scores($vote)
    {
        $absent = 1;
        $both = 1;
        $agree = 10;

        if (stripos($vote, '3') !== false) {
            $agree = 50;
            $absent = 25;
            $both = 25;
        }

        $scores = array(
            'absent' => $absent,
            'both' => $both
        );

        if (stripos($vote, 'aye') !== false) {
            $scores['aye'] = $agree;
            $scores['no'] = 0;
        } else if (stripos($vote, 'no') !== false) {
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

    public static function getCohorts()
    {
        $db = new \ParlDB;
        $cohort_list = $db->query(
            "SELECT DISTINCT cohort_hash FROM cohort_assignments"
        );

        $cohorts = array();
        foreach ($cohort_list as $row) {
            $cohort = $row['cohort_hash'];
            $cohorts[] = $cohort;
        }

        return $cohorts;
    }

    public static function getCohortQuery(){
        $membership_query = '
        select member_periods.person_id as person_id,
        md5(concat(start_party, "|", membership_key, "|", IFNULL(absence_key,""))) as cohort_hash
        from 
            (select
            person_id,
            REPLACE(COALESCE(m2party), "/Co-operative", "") as "start_party",
            GROUP_CONCAT(concat(entered_house,":", left_house) ORDER BY entered_house) as "membership_key"
            from
            member

inner join
(
select party m2party, person_id m2pid
from member
where
            house = :house and entered_house > :cut_off
and entered_house = (select min(entered_house) from member m3 where house=:house and entered_house > :cut_off and member.person_id=m3.person_id)
) m2
ON member.person_id = m2.m2pid

            where
            house = :house and entered_house > :cut_off
            group by person_id
            order by person_id
            )as member_periods
        left join 
            (select
            person_id,
            GROUP_CONCAT(concat(start_date,":", end_date)) as "absence_key"
            from
            known_absences
            group by person_id
            order by person_id, start_date
            ) as absences on member_periods.person_id = absences.person_id';
        return $membership_query;
    }


    public static function getHashforPerson($person_id){
        // given a person id, return the hash for that cohort
        $db = new \ParlDB;
        $row = $db->query("SELECT cohort_hash
        FROM cohort_assignments
        WHERE person_id = :person_id",
        array(":person_id" => $person_id))->first();

        if ($row) {
            return $row["cohort_hash"];
        } else {
            return null;
        }
    }

    public static function calculatePositions($quiet=true){
        // get current hashes available
        $cohorts = PartyCohort::getCohorts();
        $db = new \ParlDB;
        $policies = new Policies;
        $n_cohorts = count($cohorts);

        // iterate through all hashes and create policy positions
        $cohort_count = 0;
        foreach ( $cohorts as $cohort ) {

            $cohort = new PartyCohort($cohort, true);

            $positions = $cohort->calculateAllPolicyPositions($policies);

            $cohort_count++;

            $db->conn->beginTransaction();
            foreach ( $positions as $position ) {
                $cohort->cache_position( $position );
            }
            $db->conn->commit();
            if (!$quiet) {
                print("$cohort_count/$n_cohorts\n");
            }
        }

    }

    public static function populateCohorts()
    {
        // create the cohort_assignments table from the query
        $db = new \ParlDB;
        $house = 1;
        $cut_off = '1997-01-01';

        $membership_query = self::getCohortQuery();
        $insert_query = "INSERT INTO cohort_assignments (person_id, cohort_hash) $membership_query";

        $db->query("DELETE FROM cohort_assignments");

        $q = $db->query(
            $insert_query,
            array(
                ':house' => $house,
                ":cut_off" => $cut_off
            )
        );

    }
}

<?php
/**
 * Policy Positions
 *
 * @package TheyWorkForYou
 */

namespace MySociety\TheyWorkForYou;

class Divisions {

    /**
     * Member
     */

    private $member;

    /**
     * Constructor
     *
     * @param Member   $member   The member to get positions for.
     */

    public function __construct(Member $member)
    {
        $this->member = $member;
        $this->db = new \ParlDB;
    }

    public function getMemberDivisionsForPolicy($policyID) {
        $q = $this->db->query(
            "SELECT policy_id, division_title, division_date, division_number, vote
            FROM policydivisions JOIN memberdivisionvotes USING(division_id)
            WHERE member_id = :member_id AND policy_id = :policy_id
            ORDER by policy_id, division_date",
            array(':member_id' => $this->member->person_id, ':policy_id' => $policyID)
        );

        return $this->divisionsByPolicy($q);
    }

    public function getMemberAllDivisionsByPolicy() {
        $q = $this->db->query(
            "SELECT policy_id, division_title, division_date, division_number, vote
            FROM policydivisions JOIN memberdivisionvotes USING(division_id)
            WHERE member_id = :member_id
            ORDER by policy_id, division_date",
            array(':member_id' => $this->member->person_id)
        );

        return $this->divisionsByPolicy($q);
    }

    private function divisionsByPolicy($q) {
        $policies = array();

        for ($n=0; $n<$q->rows(); $n++) {
            $division = array();
            $division['text'] = $q->field($n, 'division_title');
            $division['date'] = $q->field($n, 'division_date');
            $division['vote'] = $q->field($n, 'vote');
            $policy_id = $q->field($n, 'policy_id');
            if ( !array_key_exists($policy_id, $policies) ) {
                $policies[$policy_id] = array(
                    'policy_id' => $policy_id,
                    'divisions' => array($division)
                );
            } else {
                $policies[$policy_id]['divisions'][] = $division;
            }
        };

        return $policies;
    }
}

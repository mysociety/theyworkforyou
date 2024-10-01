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

class PartyCohort {
    private $db;
    private $party;
    private $hash;

    public function __construct($person_id, $party) {
        // treat Labour and Labour/Co-operative the same as that's how
        // people view them and it'll confuse the results otherwise

        $this->party = $party;
        $this->hash = "$person_id-$party";
        $this->db = new \ParlDB();
    }

    public function getAllPolicyPositions($policies) {
        $positions = [];

        $party = $this->party;
        if ($party == 'Independent') {
            return $positions;
        }

        foreach ($policies->getPolicies() as $policy_id => $policy_text) {
            $data = $this->policy_position($policy_id);
            if ($data === null) {
                continue;
            }

            $data['policy_id'] = $policy_id;
            $data['desc'] = $policy_text;
            $positions[$policy_id] = $data;
        }

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
            [
                ':party' => $this->hash,
                ':policy_id' => $policy_id,
            ]
        )->first();

        if ($position) {
            $position['position'] = score_to_strongly($position['score']);
            return $position;
        } else {
            return null;
        }
    }
}

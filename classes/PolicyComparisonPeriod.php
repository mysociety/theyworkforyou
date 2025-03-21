<?php

/**
 * Party Cohort Class
 *
 * @package TheyWorkForYou
 */

namespace MySociety\TheyWorkForYou;

/**
 * PolicyComparisonPeriod
 * This class handles the comparison period for a policy
 */

class PolicyComparisonPeriod {
    public int $id;
    public string $slug;
    public string $description;
    public string $start_date;
    public string $end_date;
    public int $chamber_id;


    public function lslug(): string {
        return strtolower($this->slug);
    }

    /**
     * Get periods we have information for a given house and person.
     *
     * @param int $house
     * @return PolicyComparisonPeriod[]
     */
    public static function getComparisonPeriodsForPerson(int $person_id, int $house): array {
        $db = new \ParlDB();
        $sql = "SELECT DISTINCT
                    pp.slug as slug
                FROM policyvotedistribution pd
                JOIN policycomparisonperiod pp ON pd.period_id = pp.id
                WHERE pd.person_id = :person_id
                AND pd.chamber_id = :house
        ";
        $params = ['house' => $house, 'person_id' => $person_id];
        $rows = $db->query($sql, $params);
        $periods = [];
        foreach ($rows as $row) {
            $periods[] = new PolicyComparisonPeriod($row['slug'], $house);
        }
        return $periods;

    }

    public function __construct(string $period_slug, int $house) {

        $db = new \ParlDB();
        // need to join to policyorganisationsto get the party_id
        // need to join to policycomparisonperiod to get slug to id
        $sql = "SELECT * FROM policycomparisonperiod where slug = :period_slug and chamber_id = :house";

        $params = ['period_slug' => $period_slug, 'house' => $house];

        $row = $db->query($sql, $params)->first();
        if (!$row) {
            throw new \Exception("PolicyComparisonPeriod not found for slug: $period_slug and house: $house");
        }

        $this->id = (int) $row['id'];
        $this->slug = $row['slug'];
        $this->description = $row['description'];
        $this->start_date = $row['start_date'];
        $this->end_date = $row['end_date'];
        $this->chamber_id = (int) $row['chamber_id'];
    }
}

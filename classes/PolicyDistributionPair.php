<?php

/**
 * Party Cohort Class
 *
 * @package TheyWorkForYou
 */

namespace MySociety\TheyWorkForYou;

/**
 * Policy DistributionPair
 * Brings together the member_distribution (their own policy score)
 * And the comporable members total and score.
 */

class PolicyDistributionPair {
    public ?PolicyDistribution $member_distribution;
    public ?PolicyDistribution $comparison_distribution;
    public int $policy_id;
    public string $policy_desc;
    public bool $covid_affected;


    public function __construct(
        ?PolicyDistribution $member_distribution,
        ?PolicyDistribution $comparison_distribution
    ) {
        $this->member_distribution = $member_distribution;
        $this->comparison_distribution = $comparison_distribution;
        $this->policy_id = $this->getPolicyId();


        $policies_obj = new Policies();
        $policies_obj->getCovidAffected();
        $this->covid_affected = in_array($this->policy_id, $policies_obj->getCovidAffected());

        $this->policy_desc = $policies_obj->getPolicies()[$this->policy_id];

    }

    public function getMoreDetailsLink(): string {
        $person_id = $this->getPersonId();
        ;
        $policy_id = $this->getPolicyId();
        $period = strtolower($this->getVotingPeriod());
        $party_slug = $this->member_distribution->party_slug;
        return TWFY_VOTES_URL . "/person/$person_id/policies/commons/$party_slug/$period/$policy_id";
    }

    public function getPersonId(): int {
        return $this->member_distribution->person_id;
    }


    public function getPartySlug(): string {
        return $this->member_distribution->party_slug;
    }

    public function getVotingPeriod(): string {
        return $this->member_distribution->period_slug;
    }

    public function getPolicyId(): int {
        if ($this->member_distribution) {
            return $this->member_distribution->policy_id;
        }
        if ($this->comparison_distribution) {
            return $this->comparison_distribution->policy_id;
        }
        throw new \Exception('No policy ID found for this pair');
    }

    public function scoreDifference(): float {

        if (!$this->comparison_distribution) {
            return 0;
        }

        return $this->member_distribution->distance_score - $this->comparison_distribution->distance_score;
    }


    /**
     * Calculates the significance score difference.
     *
     * This function determines whether there is a significant difference
     * in scores between two policy distributions.
     * This is used to see if it should be highlighted separately as a difference with the party.
     * A different is significant is a members and the comparison score are
     * strongly directional in different directions.
     * e.g. as distance is between 0 and 1
     * if the member score is less than 0.4 and the comparison score is greater than 0.6
     * or
     * if the member score is greater than 0.6 and the comparison score is less than 0.4
     * Significant differences
     * @return bool Returns true if a significant difference exists, otherwise false.
     */
    public function sigScoreDifference(): bool {

        if (!$this->comparison_distribution) {
            return false;
        }

        $member_score = $this->member_distribution->distance_score;
        $comparison_score = $this->comparison_distribution->distance_score;

        if ($this->member_distribution->noDataAvailable()) {
            return false;
        }

        if ($member_score < 0.4 && $comparison_score > 0.6) {
            return true;
        }
        if ($member_score > 0.6 && $comparison_score < 0.4) {
            return true;
        }
        return false;

    }


    /**
     * Retrieves the policy distribution pairs for a specific person, party, period, and chamber.
     *
     * @param int $person_id The ID of the person.
     * @param string $party_slug The slug of the party.
     * @param string $period_slug The slug of the period.
     * @param int $house The ID of the house (default is HOUSE_TYPE_COMMONS).
     * @return PolicyDistributionPair[] An array of PolicyDistributionPair objects.
     */
    public static function getPersonDistributions(int $person_id, string $party_slug, string $period_slug, int $house = HOUSE_TYPE_COMMONS): array {

        $distributions = PolicyDistribution::getPersonDistributions($person_id, $party_slug, $period_slug, $house);

        // group the distributions by policy_id
        $grouped_distributions = [];
        foreach ($distributions as $distribution) {
            $grouped_distributions[$distribution->policy_id][] = $distribution;
        }

        $pairs = [];
        foreach ($grouped_distributions as $policy_id => $policy
        ) {
            $member_distribution = null;
            $comparison_distribution = null;
            foreach ($policy as $distribution) {
                if ($distribution->is_target) {
                    $member_distribution = $distribution;
                } else {
                    $comparison_distribution = $distribution;
                }
            }
            $pairs[] = new PolicyDistributionPair($member_distribution, $comparison_distribution);
        }
        return $pairs;
    }

}

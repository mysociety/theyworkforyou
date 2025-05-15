<?php

/**
 * Party Cohort Class
 *
 * @package TheyWorkForYou
 */

namespace MySociety\TheyWorkForYou;

/**
 * Policy DistributionCollection
 * This brings together a set of a person's policy distributions for a given period and party.
 * It covers items in the same 'group' (health, social, etc)
 */

class PolicyDistributionCollection {
    public string $group_slug;
    public string $group_name;
    public string $comparison_period_slug;
    public int $person_id;
    public string $party_slug;
    /** @var PolicyDistributionPair[] */
    public array $policy_pairs;


    public function getPairfromPolicyID(int $policy_id): ?PolicyDistributionPair {
        foreach ($this->policy_pairs as $pair) {
            if ($pair->policy_id == $policy_id) {
                return $pair;
            }
        }
        return null;
    }

    public function __construct(
        string $group_slug,
        string $group_name,
        string $comparison_period_slug,
        int $person_id,
        string $party_slug,
        array $policy_pairs
    ) {
        $this->group_slug = $group_slug;
        $this->group_name = $group_name;
        $this->comparison_period_slug = $comparison_period_slug;
        $this->person_id = $person_id;
        $this->party_slug = $party_slug;
        $this->policy_pairs = $policy_pairs;
    }

    public function latestUpdate(array $latest_dates) {
        $latest_date = null;
        foreach ($this->policy_pairs as $pair) {
            $date = $latest_dates[$pair->policy_id] ?? null;
            if ($date && (!$latest_date || $date > $latest_date)) {
                $latest_date = $date;
            }
        }
        return $latest_date;
    }


    /**
     * Retrieves an array of PolicyDistributionCollection objects for a specific person.
     *
     * @param array $allowed_sets An array of allowed policy sets.
     * @param int $person_id The ID of the person.
     * @param string $party_slug The slug of the person's political party.
     * @param string $period_slug The slug representing the time period.
     * @param int $house The house type (default is HOUSE_TYPE_COMMONS).
     *
     * @return PolicyDistributionCollection[] An array of PolicyDistributionCollection objects.
     */
    public static function getPersonDistributions(array $allowed_sets, int $person_id, string $party_slug, string $period_slug, int $house = HOUSE_TYPE_COMMONS): array {

        $pairs = PolicyDistributionPair::getPersonDistributions($person_id, $party_slug, $period_slug, $house);

        $policies = new Policies();

        $collections = [];

        foreach ($policies->getSets() as $set_slug => $policy_ids) {
            if (!in_array($set_slug, $allowed_sets)) {
                continue;
            }
            $group_name = $policies->getSetDescriptions()[$set_slug];
            $group_slug = $set_slug;
            $comparison_period_slug = $period_slug;
            $policy_pairs = array_filter($pairs, function ($pair) use ($policy_ids) {
                $is_policy_valid = in_array($pair->getPolicyID(), $policy_ids);
                $has_own_distribution = $pair->member_distribution !== null && !$pair->member_distribution->noDataAvailable();
                return $is_policy_valid && $has_own_distribution;
            });
            $collection = new PolicyDistributionCollection($group_slug, $group_name, $comparison_period_slug, $person_id, $party_slug, $policy_pairs);

            $collections[] = $collection;
        }
        return $collections;
    }

    /**
     * Retrieves the significant policy distributions from an array of PolicyDistributionCollection objects.
     *
     * @param PolicyDistributionCollection[] $distributions An array of PolicyDistributionCollection objects.
     * @return ?PolicyDistributionCollection An array of significant PolicyDistributionCollection objects.
     */
    public static function getSignificantDistributions(array $distributions): ?PolicyDistributionCollection {

        if (empty($distributions)) {
            return null;
        }

        $significant_pairs = [];

        foreach ($distributions as $distribution) {
            foreach ($distribution->policy_pairs as $pair) {
                if ($pair->sigScoreDifference()) {
                    $significant_pairs[] = $pair;
                }
            }
        }

        // possibly we've picked up a duplicate distribution if it was in multiple sets
        // so dedupe based on the policy_id

        $significant_pairs = array_values(array_unique($significant_pairs, SORT_REGULAR));

        return new PolicyDistributionCollection(
            $distributions[0]->group_slug,
            $distributions[0]->group_name,
            $distributions[0]->comparison_period_slug,
            $distributions[0]->person_id,
            $distributions[0]->party_slug,
            $significant_pairs
        );
    }
}

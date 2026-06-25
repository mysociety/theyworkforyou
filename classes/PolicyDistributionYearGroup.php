<?php

/**
 * Policy Distribution YearG roup
 *
 * @package TheyWorkForYou
 */

namespace MySociety\TheyWorkForYou;

/**
 * PolicyDistributionYearGroup
 * A set of policy pairs that share a recency window (based on the year the
 * member most recently voted on the policy). Used to add a time structure
 * beneath each policy group in the all-time voting summary.
 */

class PolicyDistributionYearGroup {
    public int $low_year;
    public int $high_year;
    /** @var PolicyDistributionPair[] */
    public array $policy_pairs;

    public function __construct(int $low_year, int $high_year, array $policy_pairs) {
        $this->low_year = $low_year;
        $this->high_year = $high_year;
        $this->policy_pairs = $policy_pairs;
    }

    /**
     * Time phrase describing the year group, designed to read after "Last voted on",
     * e.g. "between 2015 and 2019" or "in 2024".
     */
    public function label(): string {
        if ($this->low_year === $this->high_year) {
            return "in {$this->high_year}";
        } else {
            return "between {$this->low_year} and {$this->high_year}";
        }
    }
}

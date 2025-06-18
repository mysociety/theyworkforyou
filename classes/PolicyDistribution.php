<?php

/**
 * Party Cohort Class
 *
 * @package TheyWorkForYou
 */

namespace MySociety\TheyWorkForYou;

/**
 * Policy Distribution
 * This contains all the calculated information about the
 * number of votes and agreements relevant to the policy
 * distribution of a person (or their comparable MPs).
 * $is_target is 1 if the person is the target of the comparison,
 * 0 if they are the comparison.
 */

class PolicyDistribution {
    public int $id;
    public int $policy_id;
    public int $person_id;
    public int $period_id;
    public int $chamber_id;
    public ?int $party_id;
    public int $is_target;
    public float $num_votes_same;
    public float $num_strong_votes_same;
    public float $num_votes_different;
    public float $num_strong_votes_different;
    public float $num_votes_absent;
    public float $num_strong_votes_absent;
    public float $num_votes_abstain;
    public float $num_strong_votes_abstain;
    public float $num_agreements_same;
    public float $num_strong_agreements_same;
    public float $num_agreements_different;
    public float $num_strong_agreements_different;
    public int $start_year;
    public int $end_year;
    public float $distance_score;
    public string $party_slug;
    public string $period_slug;
    public string $party_name;
    public string $period_description;

    public function totalVotes(): float {
        return $this->num_votes_same + $this->num_votes_different + $this->num_strong_votes_same + $this->num_strong_votes_different;
    }

    public function getVerboseDescription(): string {

        $items = [];

        if ($this->num_strong_votes_same) {
            $items[] = $this->num_strong_votes_same . make_plural(" vote", $this->num_strong_votes_same) . " for";
        }
        if ($this->num_strong_votes_different) {
            $items[] = $this->num_strong_votes_different . make_plural(" vote", $this->num_strong_votes_different) . " against";
        }
        if ($this->num_strong_agreements_same) {
            $items[] = $this->num_strong_agreements_same . make_plural(" agreement", $this->num_strong_agreements_same . "for");
        }
        if ($this->num_strong_agreements_different) {
            $items[] = $this->num_strong_agreements_different . make_plural(" agreement", $this->num_strong_agreements_different . "against");
        }
        if ($this->num_strong_votes_absent) {
            $items[] = $this->num_strong_votes_absent . make_plural(" absence", $this->num_strong_votes_absent);
        }
        if ($this->num_strong_votes_abstain) {
            $items[] = $this->num_strong_votes_abstain . make_plural(" abstention", $this->num_strong_votes_abstain);
        }
        if ($this->start_year == $this->end_year) {
            $items[] = "in " . $this->start_year . ".";
        } else {
            $items[] = "between " . $this->start_year . " and " . $this->end_year . ".";
        }

        return implode(", ", $items);
    }

    public function getVerboseScoreLower(): string {
        $verbose_score = $this->getVerboseScore();
        return strtolower($verbose_score);
    }

    public function noDataAvailable(): bool {
        return $this->distance_score == -1;
    }

    public function getVerboseScore(): string {

        // Special case for when there's exactly one vote
        // e.g. weird to say 'consistently' for an event that's happened once
        if ($this->totalVotes() == 1) {
            if ($this->distance_score == 0) {
                return "Voted for";
            } elseif ($this->distance_score == 1) {
                return "Voted against";
            }
        }

        // Regular cases for multiple votes or scores between 0 and 1
        $desc = "";

        if ($this->distance_score >= 0 && $this->distance_score <= 0.05) {
            $desc = "Consistently voted for";
        } elseif ($this->distance_score > 0.05 && $this->distance_score <= 0.15) {
            $desc = "Almost always voted for";
        } elseif ($this->distance_score > 0.15 && $this->distance_score <= 0.3) {
            $desc = "Generally voted for";
        } elseif ($this->distance_score > 0.3 && $this->distance_score <= 0.4) {
            $desc = "Tended to vote for";
        } elseif ($this->distance_score > 0.4 && $this->distance_score <= 0.6) {
            $desc = "Voted a mixture of for and against";
        } elseif ($this->distance_score > 0.6 && $this->distance_score <= 0.7) {
            $desc = "Tended to vote against";
        } elseif ($this->distance_score > 0.7 && $this->distance_score <= 0.85) {
            $desc = "Generally voted against";
        } elseif ($this->distance_score > 0.85 && $this->distance_score <= 0.95) {
            $desc = "Almost always voted against";
        } elseif ($this->distance_score > 0.95 && $this->distance_score <= 1) {
            $desc = "Consistently voted against";
        } elseif ($this->distance_score == -1) {
            $desc = "No data available";
        } else {
            throw new \InvalidArgumentException("Score must be between 0 and 1");
        }
        // for alignments in the middle - we'll include a bit more information to indicate the scale of the split.
        if ($this->distance_score > 0.3 && $this->distance_score < 0.7) {
            $alignment_score = round((1 - $this->distance_score) * 100, 0);
            $desc .= " (alignment score: $alignment_score%)";
        }
        return $desc;
    }

    /**
     * Retrieves the policy distributions for a specific person, party, period, and chamber.
     *
     * @param int $person_id The ID of the person.
     * @param string $party_slug The slug of the party.
     * @param string $period_slug The slug of the period.
     * @param int $house The ID of the house (default is HOUSE_TYPE_COMMONS).
     * @return PolicyDistribution[] An array of PolicyDistribution objects.
     */
    public static function getPersonDistributions(int $person_id, string $party_slug, string $period_slug, int $house = HOUSE_TYPE_COMMONS): array {
        $db = new \ParlDB();
        // need to join to policyorganisationsto get the party_id
        // need to join to policycomparisonperiod to get slug to id
        // need to special case the query for independent mps because the results for this are in a null party_id
        if ($party_slug == "independent") {
            $sql = "SELECT
                        pd.*,
                        \"independent\" as party_slug,
                        \"Independent\" as party_name,
                        pp.slug as period_slug,
                        pp.description as period_description
                    FROM policyvotedistribution pd
                    JOIN policycomparisonperiod pp ON pd.period_id = pp.id
                    WHERE pd.person_id = :person_id
                    AND pd.party_id IS NULL
                    AND pp.slug = :period_slug
                    AND pd.chamber_id = :house
                    AND pp.chamber_id = :house
                    ";
            $params = ['person_id' => $person_id,
                'period_slug' => $period_slug,
                'house' => $house];
        } else {
            $sql = "SELECT
                        pd.*,
                        po.slug as party_slug,
                        po.name as party_name,
                        pp.slug as period_slug,
                        pp.description as period_description
                    FROM policyvotedistribution pd
                    JOIN policycomparisonperiod pp ON pd.period_id = pp.id
                    JOIN policyorganization po ON pd.party_id = po.id
                    WHERE pd.person_id = :person_id
                    AND po.slug = :party_slug
                    AND pp.slug = :period_slug
                    AND pd.chamber_id = :house
                    AND pp.chamber_id = :house
                    ";

            $params = ['person_id' => $person_id,
                'party_slug' => $party_slug,
                'period_slug' => $period_slug,
                'house' => $house];
        }

        $rows = $db->query($sql, $params);

        $distributions = [];
        foreach ($rows as $row) {
            $distributions[] = self::fromRow($row);
        }

        return $distributions;
    }

    public static function fromRow(array $row): PolicyDistribution {
        $pd = new PolicyDistribution();
        $pd->id = (int) $row['id'];
        $pd->policy_id = (int) $row['policy_id'];
        $pd->person_id = (int) $row['person_id'];
        $pd->period_id = (int) $row['period_id'];
        $pd->chamber_id = (int) $row['chamber_id'];
        $pd->party_id = (int) $row['party_id'];
        $pd->is_target = (int) $row['is_target'];
        $pd->num_votes_same = (float) $row['num_votes_same'];
        $pd->num_strong_votes_same = (float) $row['num_strong_votes_same'];
        $pd->num_votes_different = (float) $row['num_votes_different'];
        $pd->num_strong_votes_different = (float) $row['num_strong_votes_different'];
        $pd->num_votes_absent = (float) $row['num_votes_absent'];
        $pd->num_strong_votes_absent = (float) $row['num_strong_votes_absent'];
        $pd->num_votes_abstain = (float) $row['num_votes_abstain'];
        $pd->num_strong_votes_abstain = (float) $row['num_strong_votes_abstain'];
        $pd->num_agreements_same = (float) $row['num_agreements_same'];
        $pd->num_strong_agreements_same = (float) $row['num_strong_agreements_same'];
        $pd->num_agreements_different = (float) $row['num_agreements_different'];
        $pd->num_strong_agreements_different = (float) $row['num_strong_agreements_different'];
        $pd->start_year = (int) $row['start_year'];
        $pd->end_year = (int) $row['end_year'];
        $pd->distance_score = (float) $row['distance_score'];
        $pd->party_slug = $row['party_slug'];
        $pd->period_slug = $row['period_slug'];
        $pd->party_name = $row['party_name'];
        $pd->period_description = $row['period_description'];
        return $pd;
    }
}

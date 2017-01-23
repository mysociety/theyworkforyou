<?php
/**
 * Policy Positions
 *
 * @package TheyWorkForYou
 */

namespace MySociety\TheyWorkForYou;

/**
 * Policy Positions
 *
 * Provides a list of policy positions of a given Member, plus supplementary
 * information such as additional links.
 */

class PolicyPositions {

    /**
     * Member
     */

    private $member;

    /**
     * Policies
     */

    private $policies;

    /**
     * Positions
     *
     * Array of positions held by the member.
     */

    public $positions = array();

    public $positionsById = array();

    /**
     * 'Since' String
     */
    public $sinceString;

    /**
     * 'More Links' String
     */
    public $moreLinksString;

    /**
     * Constructor
     *
     * @param Policies $policies The list of policies to get the positions for.
     * @param Member   $member   The member to get positions for.
     * @param int      $limit    The number of policies to limit the list to.
     */

    public function __construct(Policies $policies, Member $member, $limit = NULL)
    {
        $this->policies = $policies;
        $this->member = $member;
        $this->divisions = new \MySociety\TheyWorkForYou\Divisions($member, $this, $policies);

        // Do the actual getting of positions
        $this->getMemberPolicyPositions($limit);
    }

    /**
     * Person Voting Record
     *
     * Populates this object's policy positions array.
     *
     * @param int $limit The number of results to limit the output to.
     */

    private function getMemberPolicyPositions ($limit = NULL) {

        // Make sure member info has actually been set.
        if (count($this->member->extra_info) === 0) {
            throw new \Exception('Member extra information has not been loaded; cannot find policy positions.');
        }

        $policies = $this->policies->getArray();

        $member_houses = $this->member->houses();

        // Determine the policy limit.
        if ($limit !== NULL AND is_int($limit))
        {
            $policy_limit = $limit;
        } else {
            $policy_limit = count($policies);
        }

        // Set the current policy count to 0
        $i = 0;

        $this->positions = array();

        // Loop around all the policies.
        foreach ($policies as $policy) {
            // Are we still within the policy limit?
            if ($i < $policy_limit) {
                if (isset($policy[2]) && $policy[2] && !in_array(HOUSE_TYPE_COMMONS, $member_houses))
                    continue;

                $dream_info = $this->displayDreamComparison($policy[0], $policy[1]);

                // don't return votes where they haven't voted on a strong division
                // if we're limiting the number of votes
                if ( $limit && !empty($dream_info) && !$dream_info['has_strong'] ) {
                    continue;
                }


                // Make sure the dream actually exists
                if (!empty($dream_info)) {
                    $this->positions[] = array(
                        'policy_id' => $policy[0],
                        'policy' => $policy[1],
                        'desc' => $dream_info['full_sentence'],
                        'has_strong' => $dream_info['has_strong'],
                        'position' => $dream_info['position'],
                        'summary' => '' $this->divisions->getMemberDivsionSummaryForPolicy($policy[0])
                    );
                    $this->positionsById[$policy[0]] = array(
                        'policy_id' => $policy[0],
                        'policy' => $policy[1],
                        'desc' => $dream_info['full_sentence'],
                        'position' => $dream_info['position'],
                        'has_strong' => $dream_info['has_strong'],
                        'score' => $dream_info['score'],
                    );
                    $i++;
                }

            } else {
                // We're over the policy limit, no sense still going, break out of the foreach.
                break ;
            }
        }

        // Set the 'since' string
        $this->sinceString = $this->generateSinceString();

        // Generate the 'more' links
        $this->moreLinksString = $this->generateMoreLinksString();

    }

    /**
     * displayDreamComparison
     *
     * Returns an array with one key: "full_sentence".
     *
     * The "full_sentence" element is a string, beginning with a lower case
     * letter, suitable for either displaying after a person’s name, eg:
     *
     *     "Lord Lordson consistently voted against [some policy]"
     *
     * or being passed into ucfirst() and displayed as a sentence on its
     * own, where the person's name is implied by context, eg:
     *
     *     "Consistently voted against [some policy]"
     *
     */

    private function displayDreamComparison($dreamid, $policy_description, $inverse=false) {
        $out = array();

        $extra_info = $this->member->extra_info();

        if (isset($extra_info["public_whip_dreammp${dreamid}_distance"])) {
            if ($extra_info["public_whip_dreammp${dreamid}_both_voted"] == 0) {
                $consistency = 'has never voted on';
                $dmpscore = -1;
            } else {
                $dmpscore = floatval($extra_info["public_whip_dreammp${dreamid}_distance"]);
                if ($inverse)
                    $dmpscore = 1.0 - $dmpscore;
                $consistency = score_to_strongly($dmpscore);
            }
            $has_strong = 0;
            if (isset($extra_info["public_whip_dreammp${dreamid}_has_strong_vote"]) && $extra_info["public_whip_dreammp${dreamid}_has_strong_vote"] == 1) {
                $has_strong = 1;
            }
            $full_sentence = $consistency . ' ' . $policy_description;
            $out = array( 'full_sentence' => $full_sentence, 'score' => $dmpscore, 'position' => $consistency, 'has_strong' => $has_strong );
        }

        return $out;
    }

    private function generateSinceString()
    {

        $member_houses = $this->member->houses();
        $entered_house = $this->member->entered_house();
        $current_member = $this->member->current_member();

        if (count($this->policies) > 0) {
            if (in_array(HOUSE_TYPE_COMMONS, $member_houses) AND
                $entered_house[HOUSE_TYPE_COMMONS]['date'] > '2001-06-07'
            ) {
                $since = '';
            } elseif (!in_array(HOUSE_TYPE_COMMONS, $member_houses) AND
                in_array(HOUSE_TYPE_LORDS, $member_houses) AND
                $entered_house[HOUSE_TYPE_LORDS]['date'] > '2001-06-07'
            ) {
                $since = '';
            } elseif ($this->member->isDead()) {
                $since = '';
            } else {
                $since = ' since 2001';
            }
            # If not current MP/Lord, but current MLA/MSP, need to say voting record is when MP
            if (!$current_member[HOUSE_TYPE_COMMONS] AND
                !$current_member[HOUSE_TYPE_LORDS] AND
                ( $current_member[HOUSE_TYPE_SCOTLAND] OR $current_member[HOUSE_TYPE_NI] )
            ) {
                $since .= ' whilst an MP';
            }

            return $since;
        }
    }

    private function generateMoreLinksString()
    {

        $extra_info = $this->member->extra_info;

        // Links to full record at Guardian and Public Whip
        $record = array();
        if (isset($extra_info['guardian_howtheyvoted'])) {
            $record[] = '<a href="' . $extra_info['guardian_howtheyvoted'] .
                '" title="At The Guardian">well-known issues</a> <small>(from the Guardian)</small>';
        }
        if (
            ( isset($extra_info['public_whip_division_attendance']) AND
            $extra_info['public_whip_division_attendance'] != 'n/a' )
            OR
            ( isset($extra_info['Lpublic_whip_division_attendance']) AND
            $extra_info['Lpublic_whip_division_attendance'] != 'n/a' )
        ) {
            $record[] = '<a href="http://www.publicwhip.org.uk/mp.php?id=uk.org.publicwhip/member/' .
                $this->member->member_id() .
                '&amp;showall=yes#divisions" title="At Public Whip">their full voting record on Public Whip</a>';
        }

        if (count($record) > 0) {
            return 'More on ' . implode(' &amp; ', $record);
        } else {
            return '';
        }
    }

}

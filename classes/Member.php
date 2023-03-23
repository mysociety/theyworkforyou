<?php
/**
 * Member Class
 *
 * @package TheyWorkForYou
 */

namespace MySociety\TheyWorkForYou;

/**
 * Member
 */

class Member extends \MEMBER {

    /**
     * Is Dead
     *
     * Determine if the member has died or not.
     *
     * @return boolean If the member is dead or not.
     */

    public function isDead() {

        $left_house = $this->left_house();

        if ($left_house) {

            // This member has left a house, and might be dead. See if they are.

            // Types of house to test for death.
            $house_types = array(
                HOUSE_TYPE_COMMONS,
                HOUSE_TYPE_LORDS,
                HOUSE_TYPE_SCOTLAND,
                HOUSE_TYPE_NI,
                HOUSE_TYPE_WALES,
                HOUSE_TYPE_LONDON_ASSEMBLY,
            );

            foreach ($house_types as $house_type) {

                if (in_array($house_type, $left_house) and
                    $left_house[$house_type]['reason'] and
                    $left_house[$house_type]['reason'] == 'Died'
                ) {

                    // This member has left a house because of death.
                    return true;
                }

            }

        }

        // If we get this far the member hasn't left a house due to death, and
        // is presumably alive.
        return false;

    }

 
    /**
     * Cohort Key
     *
     * Gets a key that defines the periods and party a member should be compared against
     *
     * @return string of party and entry dates
     */

    public function cohortKey($house = HOUSE_TYPE_COMMONS) {
        // get the hash_id for the cohort this member belongs to
        $person_id = $this->person_id();
        return PartyCohort::getHashforPerson($person_id);
    }

    public function cohortPartyComparisonDirection() {
        // Is this MP and their cohort compared against the
        // first or last party they have?
        // By default, mirroring the partycohort query, 
        // this is the first party. 
        // However, this is ignored for the Speaker, and additional
        // individual overrides can be added below.
        // This makes most sense when a party switch was a long time ago.
        // As long as this person is in a cohort of 1 and has a unique set 
        // of memberships (which is likely for edge cases) it doesn't matter
        // that their original party is what is used when in the party cohort
        // construction query. 

        $person_id = $this->person_id();

        $direction = "first";
        if ($this->party() == "Speaker") {
            $direction = "last";
        }

        // MPs who have switched parties but should be compared against their
        // current party can go here.
        $use_last_party = array(10172, 14031, 25873);

        if (in_array($person_id, $use_last_party)) {
            $direction = "last";
        }

        return $direction;
    }

    public function currentPartyComparison(){
        # Simplify the current party when being compared to the original
        # Stops co-op and labour being seen as different
        $party = $this->party;
        if ( $party == 'Labour/Co-operative' ) {
            $party = 'Labour';
        }
        return $party;
    }

    public function cohortParty($house = HOUSE_TYPE_COMMONS){
        // The party being compared against for party comparison purposes
        // Unless specified by the condition in cohortPartyComparisonDirection
        // This is the first, not last, party a person has.

        $person_id = $this->person_id();
        $db = new \ParlDB;

        $cohort_direction = $this->cohortPartyComparisonDirection();

        if ($cohort_direction == "first") {
            $direction = " ASC";
        } else {
            $direction = " DESC";
        }

        $row = $db->query("SELECT party from member
        where house = :house
        and person_id = :person_id
        and party != ''
        order by entered_house
        " . $direction, array(":house" => $house,
                 ":person_id" => $person_id))->first();
        if ($row) {
            $party = $row["party"];
            if ( $party == 'Labour/Co-operative' ) {
                $party = 'Labour';
            }
            return $party;
        } else {
            return null;
        }

    }

    /*
     * Determine if the member is a new member of a house where new is
     * within the last 6 months.
     *
     * @param int house - identifier for the house, defaults to 1 for westminster
     *
     * @return boolean
     */


    public function isNew($house = HOUSE_TYPE_COMMONS) {
        $date_entered = $this->getEntryDate($house);

        if ($date_entered) {
            $date_entered = new \DateTime($date_entered);
            $now = new \DateTime();

            $diff = $date_entered->diff($now);
            if ( $diff->y == 0 && $diff->m <= 6 ) {
                return true;
            }
        }

        return false;
    }

    /*
     * Get the date the person first entered a house. May not be for their current seat.
     *
     * @param int house - identifier for the house, defaults to 1 for westminster
     *
     * @return string - blank if no entry date for that house otherwise in YYYY-MM-DD format
     */

    public function getEntryDate($house = HOUSE_TYPE_COMMONS) {
        $date_entered = '';

        $entered_house = $this->entered_house($house);

        if ( $entered_house ) {
            $date_entered = $entered_house['date'];
        }

        return $date_entered;
    }

    /*
     * Get the date the person last left the house.
     *
     * @param int house - identifier for the house, defaults to 1 for westminster
     *
     * @return string - 9999-12-31 if they are still in that house otherwise in YYYY-MM-DD format
     */

    public function getLeftDate($house = HOUSE_TYPE_COMMONS) {
        $date_left = '';

        $left_house = $this->left_house($house);

        if ( $left_house ) {
            $date_left = $left_house['date'];
        }

        return $date_left;
    }


    public function getEUStance() {
        if (array_key_exists('eu_ref_stance', $this->extra_info())) {
            return $this->extra_info()['eu_ref_stance'];
        }

        return false;
    }

    /**
    * Image
    *
    * Return a URL for the member's image.
    *
    * @return string The URL of the member's image.
    */

    public function image() {

        $is_lord = $this->house(HOUSE_TYPE_LORDS);
        if ($is_lord) {
            list($image,$size) = Utility\Member::findMemberImage($this->person_id(), false, 'lord');
        } else {
            list($image,$size) = Utility\Member::findMemberImage($this->person_id(), false, true);
        }

        // We can determine if the image exists or not by testing if size is set
        if ($size !== null) {
            $exists = true;
        } else {
            $exists = false;
        }

        return array(
            'url' => $image,
            'size' => $size,
            'exists' => $exists
        );

    }

    public function getMostRecentMembership() {
        $departures = $this->left_house();

        usort(
            $departures,
            function ($a, $b) {
                if ( $a['date'] == $b['date'] ) {
                    return 0;
                } else if ( $a['date'] < $b['date'] ) {
                    return -1;
                } else {
                    return 1;
                }
            }
        );

        $latest_membership = array_slice($departures, -1)[0];
        $latest_membership['current'] = ($latest_membership['date'] == '9999-12-31');
        $latest_entrance = $this->entered_house($latest_membership['house']);
        $latest_membership['start_date'] = $latest_entrance['date'];
        $latest_membership['end_date'] = $latest_membership['date'];
        $latest_membership['rep_name'] = $this->getRepNameForHouse($latest_membership['house']);

        return $latest_membership;
    }

    /**
    * Offices
    *
    * Return an array of Office objects held (or previously held) by the member.
    *
    * @param string $include_only  Restrict the list to include only "previous" or "current" offices.
    * @param bool   $ignore_committees Ignore offices that appear to be committee memberships.
    *
    * @return array An array of Office objects.
    */

    public function offices($include_only = null, $ignore_committees = false) {

        $out = array();

        if (array_key_exists('office', $this->extra_info())) {
            $office = $this->extra_info();
            $office = $office['office'];

            foreach ($office as $row) {
                if ( $officeObject = $this->getOfficeObject($include_only, $ignore_committees, $row) ) {
                    $out[] = $officeObject;
                }
            }
        }

        return $out;

    }

    private function getOfficeObject($include_only, $ignore_committees, $row) {
        if (!$this->includeOffice($include_only, $row['to_date'])) {
            return null;
        }
        if ($ignore_committees && strpos($row['moffice_id'], 'Committee')) {
            return null;
        }

        $officeObject = new Office;
        $officeObject->title = prettify_office($row['position'], $row['dept']);
        $officeObject->from_date = $row['from_date'];
        $officeObject->to_date = $row['to_date'];
        $officeObject->source = $row['source'];
        return $officeObject;
    }

    private function includeOffice($include_only, $to_date) {
        $include_office = true;

        // If we should only include previous offices, and the to date is in the future, suppress this office.
        if ($include_only == 'previous' and $to_date == '9999-12-31') {
            $include_office = false;
        }

        // If we should only include previous offices, and the to date is in the past, suppress this office.
        if ($include_only == 'current' and $to_date != '9999-12-31') {
            $include_office = false;
        }

        return $include_office;
    }

    /**
    * Get Other Parties String
    *
    * Return a readable list of party changes for this member.
    *
    * @return string|null A readable list of the party changes for this member.
    */

    public function getOtherPartiesString() {

        if (!empty($this->other_parties) && $this->party != 'Speaker' && $this->party != 'Deputy Speaker') {
            $output = 'Party was ';
            $other_parties = array();
            foreach ($this->other_parties as $r) {
                $other_parties[] = $r['from'] . ' until ' . format_date($r['date'], SHORTDATEFORMAT);
            }
            $output .= join('; ', $other_parties);
            return $output;
        } else {
            return null;
        }

    }

    /**
    * Get Other Constituencies String
    *
    * Return a readable list of other constituencies for this member.
    *
    * @return string|null A readable list of the other constituencies for this member.
    */

    public function getOtherConstituenciesString() {

        if ($this->other_constituencies) {
            return 'Also represented ' . join('; ', array_keys($this->other_constituencies));
        } else {
            return null;
        }

    }

    /**
    * Get Entered/Left Strings
    *
    * Return an array of readable strings covering when people entered or left
    * various houses. Returns an array since it's possible for a member to have
    * done several of these things.
    *
    * @return array An array of strings of when this member entered or left houses.
    */
    public function getEnterLeaveStrings() {
        $output = array();

        $output[] = $this->entered_house_line(HOUSE_TYPE_LORDS, gettext('House of Lords'));

        if (isset($this->left_house[HOUSE_TYPE_COMMONS]) && isset($this->entered_house[HOUSE_TYPE_LORDS])) {
            $string = '<strong>';
            $string .= sprintf(gettext('Previously MP for %s until %s'), $this->left_house[HOUSE_TYPE_COMMONS]['constituency'], $this->left_house[HOUSE_TYPE_COMMONS]['date_pretty']);
            $string .= '</strong>';
            if ($this->left_house[HOUSE_TYPE_COMMONS]['reason']) {
                $string .= ' &mdash; ' . $this->left_house[HOUSE_TYPE_COMMONS]['reason'];
            }
            $output[] = $string;
        }

        $output[] = $this->left_house_line(HOUSE_TYPE_LORDS, gettext('House of Lords'));

        if (isset($this->extra_info['lordbio'])) {
            $output[] = '<strong>Positions held at time of appointment:</strong> ' . $this->extra_info['lordbio'] .
                ' <small>(from <a href="' .
                $this->extra_info['lordbio_from'] . '">Number 10 press release</a>)</small>';
        }

        $output[] = $this->entered_house_line(HOUSE_TYPE_COMMONS, gettext('House of Commons'));

        # If they became a Lord, we handled this above.
        if ($this->house(HOUSE_TYPE_COMMONS) && !$this->current_member(HOUSE_TYPE_COMMONS) && !$this->house(HOUSE_TYPE_LORDS)) {
            $output[] = $this->left_house_line(HOUSE_TYPE_COMMONS, gettext('House of Commons'));
        }

        $output[] = $this->entered_house_line(HOUSE_TYPE_NI, gettext('Assembly'));
        $output[] = $this->left_house_line(HOUSE_TYPE_NI, gettext('Assembly'));
        $output[] = $this->entered_house_line(HOUSE_TYPE_SCOTLAND, gettext('Scottish Parliament'));
        $output[] = $this->left_house_line(HOUSE_TYPE_SCOTLAND, gettext('Scottish Parliament'));
        $output[] = $this->entered_house_line(HOUSE_TYPE_WALES, gettext('Welsh Parliament'));
        $output[] = $this->left_house_line(HOUSE_TYPE_WALES, gettext('Welsh Parliament'));
        $output[] = $this->entered_house_line(HOUSE_TYPE_LONDON_ASSEMBLY, gettext('London Assembly'));
        $output[] = $this->left_house_line(HOUSE_TYPE_LONDON_ASSEMBLY, gettext('London Assembly'));

        $output = array_values(array_filter($output));
        return $output;
    }

    private function entered_house_line($house, $house_name) {
        if (isset($this->entered_house[$house]['date'])) {
            $string = "<strong>";
            if (strlen($this->entered_house[$house]['date_pretty'])==4) {
                $string .= sprintf(gettext("Entered the %s in %s"), $house_name, $this->entered_house[$house]['date_pretty']);
            } else {
                $string .= sprintf(gettext("Entered the %s on %s"), $house_name, $this->entered_house[$house]['date_pretty']);
            }
            $string .= '</strong>';
            if ($this->entered_house[$house]['reason']) {
                $string .= ' &mdash; ' . $this->entered_house[$house]['reason'];
            }
            return $string;
        }
    }

    private function left_house_line($house, $house_name) {
        if ($this->house($house) && !$this->current_member($house)) {
            $string = "<strong>";
            if (strlen($this->left_house[$house]['date_pretty'])==4) {
                $string .= sprintf(gettext("Left the %s in %s"), $house_name, $this->left_house[$house]['date_pretty']);
            } else {
                $string .= sprintf(gettext("Left the %s on %s"), $house_name, $this->left_house[$house]['date_pretty']);
            }
            $string .= '</strong>';
            if ($this->left_house[$house]['reason']) {
                $string .= ' &mdash; ' . $this->left_house[$house]['reason'];
            }
            return $string;
        }
    }

    public function getPartyPolicyDiffs($partyCohort, $policiesList, $positions, $only_diffs = false) {
        $policy_diffs = array();
        $party_positions = $partyCohort->getAllPolicyPositions($policiesList);

        if ( !$party_positions ) {
            return $policy_diffs;
        }

        foreach ( $positions->positionsById as $policy_id => $details ) {
            if ( $details['has_strong'] && $details['score'] != -1 && isset($party_positions[$policy_id])) {
                $mp_score = $details['score'];
                $party_position = $party_positions[$policy_id];
                $party_score = $party_position['score'];
                $year_min = substr($party_position['date_min'], 0, 4);
                $year_max = substr($party_position['date_max'], 0, 4);
                if ($year_min == $year_max) {
                    $party_voting_summary = sprintf("%d votes, in %d", $party_position['divisions'], $year_min);
                } else {
                    $party_voting_summary = sprintf("%d votes, between %d–%d", $party_position['divisions'], $year_min, $year_max);
                }

                $score_diff = $this->calculatePolicyDiffScore($mp_score, $party_score);

                // skip anything that isn't a yes vs no diff
                if ( $only_diffs && $score_diff < 2 ) {
                    continue;
                }
                $policy_diffs[$policy_id] = [
                    'policy_text' => $details['policy'],
                    'score_difference' => $score_diff,
                    'person_position' => $details['position'],
                    'summary' => $details['summary'],
                    'party_position' => $party_position['position'],
                    'party_voting_summary' => $party_voting_summary,
                ];
            }
        }

        uasort($policy_diffs, function($a, $b) {
            return $b['score_difference'] - $a['score_difference'];
        });

        return $policy_diffs;
    }

    private function calculatePolicyDiffScore( $mp_score, $party_score ) {
        $score_diff = abs($mp_score - $party_score);
        // if they are on opposite sides of mixture of for and against
        if (
            ( $mp_score < 0.4 && $party_score > 0.6 ) ||
            ( $mp_score > 0.6 && $party_score < 0.4 )
        ) {
            $score_diff += 2;
        // if on is mixture of for and against and one is for/against
        } else if (
            ( $mp_score > 0.4 && $mp_score < 0.6 && ( $party_score > 0.6 || $party_score < 0.4 ) ) ||
            ( $party_score > 0.4 && $party_score < 0.6 && ( $mp_score > 0.6 || $mp_score < 0.4 ) )
        ) {
            $score_diff += 1;
        }

        return $score_diff;
    }

    public static function getRegionalList($postcode, $house, $type) {
        $db = new \ParlDB;

        $mreg = array();
        $constituencies = \MySociety\TheyWorkForYou\Utility\Postcode::postcodeToConstituencies($postcode);
        if ( isset($constituencies[$type]) ) {
            $cons_name = $constituencies[$type];
            $query_base = "SELECT member.person_id, title, lordofname, given_name, family_name, constituency, house
                FROM member, person_names
                WHERE
                member.person_id = person_names.person_id
                AND person_names.type = 'name'
                AND constituency = :cons_name
                AND house = :house
                AND left_house >= start_date
                AND left_house <= end_date";
            $q = $db->query("$query_base AND left_reason = 'still_in_office'",
                array(
                    ':house' => $house,
                    ':cons_name' => $cons_name
                )
            );
            if ( !$q->rows() && ($dissolution = Dissolution::db()) ) {
                $q = $db->query("$query_base AND $dissolution[query]",
                    array(
                        ':house' => $house,
                        ':cons_name' => $cons_name,
                    ) + $dissolution['params']
                );
            }

            foreach ($q as $row) {
                $name = member_full_name($house, $row['title'], $row['given_name'], $row['family_name'], $row['lordofname']);
                $mreg[] = array(
                    'person_id' => $row['person_id'],
                    'name' => $name,
                    'house' => $row['house'],
                    'constituency' => $row['constituency']
                );
            }
        }

        return $mreg;
    }

    public static function getRepNameForHouse($house) {
        switch ( $house ) {
            case HOUSE_TYPE_COMMONS:
                $name = 'MP';
                break;
            case HOUSE_TYPE_LORDS:
                $name = 'Peer';
                break;
            case HOUSE_TYPE_NI:
                $name = 'MLA';
                break;
            case HOUSE_TYPE_SCOTLAND:
                $name = 'MSP';
                break;
            case HOUSE_TYPE_WALES:
                $name = 'MS';
                break;
            case HOUSE_TYPE_LONDON_ASSEMBLY:
                $name = 'London Assembly Member';
                break;
            case HOUSE_TYPE_ROYAL:
                $name = 'Member of royalty';
                break;
            default:
                $name = '';
        }
        return $name;
    }

}

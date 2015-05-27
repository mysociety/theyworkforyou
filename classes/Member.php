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

    private $priority_offices = array(
        'The Prime Minister',
        'The Deputy Prime Minister ',
        'Leader of Her Majesty\'s Official Opposition',
        'The Chancellor of the Exchequer',
    );

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
            );

            foreach ($house_types as $house_type) {

                if (in_array($house_type, $left_house) AND
                    $left_house[$house_type]['reason'] AND
                    $left_house[$house_type]['reason'] == 'Died'
                ) {

                    // This member has left a house because of death.
                    return TRUE;
                }

            }

        }

        // If we get this far the member hasn't left a house due to death, and
        // is presumably alive.
        return FALSE;

    }

    /*
     * Determine if the member is a new member of a house where new is
     * within the last 6 months.
     *
     * @param int house - identifier for the house, defaults to 1 for westminster
     *
     * @return boolean
     */

    public function isNew($house = 1) {
        $date_entered = $this->getEntryDate($house);

        if ($date_entered) {
            $date_entered = new \DateTime($date_entered);
            $now = new \DateTime();

            $diff = $date_entered->diff($now);
            if ( $diff->y == 0 && $diff->m <= 6 ) {
                return TRUE;
            }
        }

        return FALSE;
    }

    /*
     * Get the date the person first entered a house. May not be for their current seat.
     *
     * @param int house - identifier for the house, defaults to 1 for westminster
     *
     * @return string - blank if no entry date for that house otherwise in YYYY-MM-DD format
     */

    public function getEntryDate($house = 1) {
        $date_entered = '';

        $entered_house = $this->entered_house($house);

        if ( $entered_house ) {
            $date_entered = $entered_house['date'];
        }

        return $date_entered;
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
        if ($size !== NULL) {
            $exists = TRUE;
        } else {
            $exists = FALSE;
        }

        return array(
            'url' => $image,
            'size' => $size,
            'exists' => $exists
        );

    }

    // TODO: some of this probably wants to go elsewhere
    public function getMostRecentMembership() {
        $last_cons = '';
        $last_house = null;
        $last_party = null;
        if ( $this->current_member_anywhere() ) {
            $houses = array_keys(array_filter($this->current_member(), 'strlen'));
            $last_cons = $this->constituency;
            $last_party = $this->party;
            $last_house = min($houses);
        } else {
            $max_date = null;
            foreach ( array_keys($this->left_house) as $house ) {
                if ($this->left_house[$house]['date'] > $max_date ) {
                    $max_date = $this->left_house[$house]['date'];
                    $last_cons = $this->left_house[$house]['constituency'];
                    $last_party = $this->left_house[$house]['party'];
                    $last_house = $house;
                }
            }
        }
        return array(
            'entered_house' => $this->entered_house($last_house)['date'],
            'left_house' => $this->left_house($last_house)['date'],
            'cons' => $last_cons,
            'party' => $last_party,
            'house' => $last_house,
            'rep_name' => $this->getRepNameForHouse($last_house)
        );
    }

    /**
    * Offices
    *
    * Return an array of Office objects held (or previously held) by the member.
    *
    * @param string $include_only  Restrict the list to include only "previous" or "current" offices.
    * @param bool   $priority_only Restrict the list to include only positions in the $priority_offices list.
    *
    * @return array An array of Office objects.
    */

    public function offices($include_only = NULL, $priority_only = FALSE) {

        $out = array();

        if (array_key_exists('office', $this->extra_info())) {
            $office = $this->extra_info();
            $office = $office['office'];

            foreach ($office as $row) {

                // Reset the inclusion of this position
                $include_office = TRUE;

                // If we should only include previous offices, and the to date is in the future, suppress this office.
                if ($include_only == 'previous' AND $row['to_date'] == '9999-12-31') {
                    $include_office = FALSE;
                }

                // If we should only include previous offices, and the to date is in the past, suppress this office.
                if ($include_only == 'current' AND $row['to_date'] != '9999-12-31') {
                    $include_office = FALSE;
                }

                $office_title = prettify_office($row['position'], $row['dept']);

                if (($priority_only AND in_array($office_title, $this->priority_offices))
                    OR !$priority_only) {
                    if ($include_office) {
                        $officeObject = new Office;

                        $officeObject->title = $office_title;

                        $officeObject->from_date = $row['from_date'];
                        $officeObject->to_date = $row['to_date'];

                        $officeObject->source = $row['source'];

                        $out[] = $officeObject;
                    }
                }
            }
        }

        return $out;

    }

    /**
    * Get Other Parties String
    *
    * Return a readable list of party changes for this member.
    *
    * @return string|null A readable list of the party changes for this member.
    */

    public function getOtherPartiesString() {

        if ($this->other_parties && $this->party != 'Speaker' && $this->party != 'Deputy Speaker') {
            $output = 'Changed party ';
            foreach ($this->other_parties as $r) {
                $other_parties[] = 'from ' . $r['from'] . ' on ' . format_date($r['date'], SHORTDATEFORMAT);
            }
            $output .= join('; ', $other_parties);
            return $output;
        } else {
            return NULL;
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
            return NULL;
        }

    }

    /**
    * Get Entered/Left Strings
    *
    * Return an array of readable strings covering when people entered or left
    * various houses. Returns an array since it's *possible* (although unlikely)
    * for a member to have done several of these things.
    *
    * @return array An array of strings of when this member entered or left houses.
    */
    public function getEnterLeaveStrings() {

        $output = array();

        if (isset($this->left_house[HOUSE_TYPE_COMMONS]) && isset($this->entered_house[HOUSE_TYPE_LORDS])) {
            $string = '<strong>Entered the House of Lords ';
            if (strlen($this->entered_house[HOUSE_TYPE_LORDS]['date_pretty'])==4) {
                $string .= 'in ';
            } else {
                $string .= 'on ';
            }
            $string .= $this->entered_house[HOUSE_TYPE_LORDS]['date_pretty'].'</strong>';
            $string .= '</strong>';
            if ($this->entered_house[HOUSE_TYPE_LORDS]['reason']) {
                $string .= ' &mdash; ' . $this->entered_house[HOUSE_TYPE_LORDS]['reason'];
            }

            $output[] = $string;

            $string = '<strong>Previously MP for ';
            $string .= $this->left_house[HOUSE_TYPE_COMMONS]['constituency'] . ' until ';
            $string .= $this->left_house[HOUSE_TYPE_COMMONS]['date_pretty'].'</strong>';
            if ($this->left_house[HOUSE_TYPE_COMMONS]['reason']) {
                $string .= ' &mdash; ' . $this->left_house[HOUSE_TYPE_COMMONS]['reason'];
            }

            $output[] = $string;

        } elseif (isset($this->entered_house[HOUSE_TYPE_LORDS]['date'])) {
            $string = '<strong>Became a Lord ';
            if (strlen($this->entered_house[HOUSE_TYPE_LORDS]['date_pretty'])==4)
                $string .= 'in ';
            else
                $string .= 'on ';
            $string .= $this->entered_house[HOUSE_TYPE_LORDS]['date_pretty'].'</strong>';
            if ($this->entered_house[HOUSE_TYPE_LORDS]['reason']) {
                $string .= ' &mdash; ' . $this->entered_house[HOUSE_TYPE_LORDS]['reason'];
            }

            $output[] = $string;
        }

        if ($this->house(HOUSE_TYPE_LORDS) && !$this->current_member(HOUSE_TYPE_LORDS)) {
            $string = '<strong>Left House of Lords on '.$this->left_house[HOUSE_TYPE_LORDS]['date_pretty'].'</strong>';
            if ($this->left_house[HOUSE_TYPE_LORDS]['reason']) {
                $string .= ' &mdash; ' . $this->left_house[HOUSE_TYPE_LORDS]['reason'];
            }

            $output[] = $string;
        }

        if (isset($extra_info['lordbio'])) {
            $string = '<strong>Positions held at time of appointment:</strong> ' . $extra_info['lordbio'] .
                ' <small>(from <a href="' .
                $extra_info['lordbio_from'] . '">Number 10 press release</a>)</small>';

            $output[] = $string;
        }

        if (isset($this->entered_house[HOUSE_TYPE_COMMONS]['date'])) {
            $string = '<strong>Entered House of Commons on ';
            $string .= $this->entered_house[HOUSE_TYPE_COMMONS]['date_pretty'].'</strong>';
            if ($this->entered_house[HOUSE_TYPE_COMMONS]['reason']) {
                $string .= ' &mdash; ' . $this->entered_house[HOUSE_TYPE_COMMONS]['reason'];
            }

            $output[] = $string;
        }

        # If they became a Lord, we handled this above.
        if ($this->house(HOUSE_TYPE_COMMONS) && !$this->current_member(HOUSE_TYPE_COMMONS) && !$this->house(HOUSE_TYPE_LORDS)) {
            $string = '<strong>Left Parliament ';
            if (strlen($this->left_house[HOUSE_TYPE_COMMONS]['date_pretty'])==4) {
                $string .= 'in ';
            } else {
                $string .= 'on ';
            }
            $string .= $this->left_house[HOUSE_TYPE_COMMONS]['date_pretty'].'</strong>';
            if ($this->left_house[HOUSE_TYPE_COMMONS]['reason']) {
                $string .= ' &mdash; ' . $this->left_house[HOUSE_TYPE_COMMONS]['reason'];
            }

            $output[] = $string;
        }

        if (isset($this->entered_house[HOUSE_TYPE_NI]['date'])) {
            $string = '<strong>Entered the Assembly on ';
            $string .= $this->entered_house[HOUSE_TYPE_NI]['date_pretty'].'</strong>';
            if ($this->entered_house[HOUSE_TYPE_NI]['reason']) {
                $string .= ' &mdash; ' . $this->entered_house[HOUSE_TYPE_NI]['reason'];
            }

            $output[] = $string;
        }

        if ($this->house(HOUSE_TYPE_NI) && !$this->current_member(HOUSE_TYPE_NI)) {
            $string = '<strong>Left the Assembly on '.$this->left_house[HOUSE_TYPE_NI]['date_pretty'].'</strong>';
            if ($this->left_house[HOUSE_TYPE_NI]['reason']) {
                $string .= ' &mdash; ' . $this->left_house[HOUSE_TYPE_NI]['reason'];
            }

            $output[] = $string;
        }

        if (isset($this->entered_house[HOUSE_TYPE_SCOTLAND]['date'])) {
            $string = '<strong>Entered the Scottish Parliament on ';
            $string .= $this->entered_house[HOUSE_TYPE_SCOTLAND]['date_pretty'].'</strong>';
            if ($this->entered_house[HOUSE_TYPE_SCOTLAND]['reason']) {
                $string .= ' &mdash; ' . $this->entered_house[HOUSE_TYPE_SCOTLAND]['reason'];
            }

            $output[] = $string;
        }

        if ($this->house(HOUSE_TYPE_SCOTLAND) && !$this->current_member(HOUSE_TYPE_SCOTLAND)) {
            $string = '<strong>Left the Scottish Parliament on '.$this->left_house[HOUSE_TYPE_SCOTLAND]['date_pretty'].'</strong>';
            if ($this->left_house[HOUSE_TYPE_SCOTLAND]['reason']) {
                $string .= ' &mdash; ' . $this->left_house[HOUSE_TYPE_SCOTLAND]['reason'];
            }

            $output[] = $string;
        }

        return $output;
    }

    public static function getRegionalList($postcode, $house, $type) {
        $db = new \ParlDB;

        $dissolution_dates = array(
            3 => '2011-03-24',
            4 => '2011-03-23'
        );
        $mreg = array();
        $constituencies = postcode_to_constituencies($postcode);
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
            if ( !$q->rows() ) {
                $q = $db->query("$query_base AND left_house = :dissolution_date",
                    array(
                        ':house' => $house,
                        ':cons_name' => $cons_name,
                        ':dissolution_date' => $dissolution_dates[$house]
                    )
                );
            }

            for ($i = 0; $i < $q->rows; $i++) {
                $name = member_full_name($house, $q->field($i, 'title'),
                    $q->field($i, 'given_name'), $q->field($i, 'family_name'),
                    $q->field($i, 'lordofname'));

                $mreg[] = array(
                    'person_id' 	=> $q->field($i, 'person_id'),
                    'name' => $name,
                    'house' => $q->field($i, 'house'),
                    'constituency' 	=> $q->field($i, 'constituency')
                );
            }
        }

        return $mreg;
    }

    public static function getRepNameForHouse($house) {
        switch ( $house ) {
        case 1:
            $name = 'MP';
            break;
        case 2:
            $name = 'Peer';
            break;
        case 3:
            $name = 'MLA';
            break;
        case 4:
            $name = 'MSP';
            break;
        default:
            $name = '';
        }
        return $name;
    }

}

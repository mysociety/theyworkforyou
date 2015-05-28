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

        if (!empty($this->other_parties) && $this->party != 'Speaker' && $this->party != 'Deputy Speaker') {
            $output = 'Party was ';
            $other_parties = array();
            foreach ($this->other_parties as $r) {
                $other_parties[] = $r['from'] . ' until ' . format_date($r['date'], SHORTDATEFORMAT);
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
    * various houses. Returns an array since it's possible for a member to have
    * done several of these things.
    *
    * @return array An array of strings of when this member entered or left houses.
    */
    public function getEnterLeaveStrings() {
        $output = array();

        $output[] = $this->entered_house_line(HOUSE_TYPE_LORDS, 'House of Lords');

        if (isset($this->left_house[HOUSE_TYPE_COMMONS]) && isset($this->entered_house[HOUSE_TYPE_LORDS])) {
            $string = '<strong>Previously MP for ';
            $string .= $this->left_house[HOUSE_TYPE_COMMONS]['constituency'] . ' until ';
            $string .= $this->left_house[HOUSE_TYPE_COMMONS]['date_pretty'] . '</strong>';
            if ($this->left_house[HOUSE_TYPE_COMMONS]['reason']) {
                $string .= ' &mdash; ' . $this->left_house[HOUSE_TYPE_COMMONS]['reason'];
            }
            $output[] = $string;
        }

        $output[] = $this->left_house_line(HOUSE_TYPE_LORDS, 'House of Lords');

        if (isset($this->extra_info['lordbio'])) {
            $output[] = '<strong>Positions held at time of appointment:</strong> ' . $this->extra_info['lordbio'] .
                ' <small>(from <a href="' .
                $this->extra_info['lordbio_from'] . '">Number 10 press release</a>)</small>';
        }

        $output[] = $this->entered_house_line(HOUSE_TYPE_COMMONS, 'House of Commons');

        # If they became a Lord, we handled this above.
        if ($this->house(HOUSE_TYPE_COMMONS) && !$this->current_member(HOUSE_TYPE_COMMONS) && !$this->house(HOUSE_TYPE_LORDS)) {
            $output[] = $this->left_house_line(HOUSE_TYPE_COMMONS, 'House of Commons');
        }

        $output[] = $this->entered_house_line(HOUSE_TYPE_NI, 'Assembly');
        $output[] = $this->left_house_line(HOUSE_TYPE_NI, 'Assembly');
        $output[] = $this->entered_house_line(HOUSE_TYPE_SCOTLAND, 'Scottish Parliament');
        $output[] = $this->left_house_line(HOUSE_TYPE_SCOTLAND, 'Scottish Parliament');

        $output = array_values(array_filter($output));
        return $output;
    }

    private function entered_house_line($house, $house_name) {
        if (isset($this->entered_house[$house]['date'])) {
            $string = "<strong>Entered the $house_name ";
            $string .= strlen($this->entered_house[$house]['date_pretty'])==4 ? 'in ' : 'on ';
            $string .= $this->entered_house[$house]['date_pretty'] . '</strong>';
            if ($this->entered_house[$house]['reason']) {
                $string .= ' &mdash; ' . $this->entered_house[$house]['reason'];
            }
            return $string;
        }
    }

    private function left_house_line($house, $house_name) {
        if ($this->house($house) && !$this->current_member($house)) {
            $string = "<strong>Left the $house_name ";
            $string .= strlen($this->left_house[$house]['date_pretty'])==4 ? 'in ' : 'on ';
            $string .= $this->left_house[$house]['date_pretty'] . '</strong>';
            if ($this->left_house[$house]['reason']) {
                $string .= ' &mdash; ' . $this->left_house[$house]['reason'];
            }
            return $string;
        }
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

}

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

    /**
    * Image
    *
    * Return a URL for the member's image.
    *
    * @return string The URL of the member's image.
    */

    public function image() {

        $is_lord = in_array(HOUSE_TYPE_LORDS, $this->houses());
        if ($is_lord) {
            list($image,$sz) = find_rep_image($this->person_id(), false, 'lord');
        } else {
            list($image,$sz) = find_rep_image($this->person_id(), false, true);
        }
        return $image;

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

}

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

}

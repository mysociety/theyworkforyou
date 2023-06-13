<?php

namespace MySociety\TheyWorkForYou\Utility;

/**
 * House Utilities
 *
 * Utility functions related to house types
 */

class House
{
    public static function division_house_name_to_number($name) {
        $name_to_number = array(
            'commons' => HOUSE_TYPE_COMMONS,
            'lords' => HOUSE_TYPE_LORDS,
            'scotland' => HOUSE_TYPE_SCOTLAND,
            'pbc' => HOUSE_TYPE_COMMONS,
            'senedd' => HOUSE_TYPE_WALES,
        );

        return $name_to_number[$name];
    }

    public static function house_to_members($house) {
        $house_to_members = array(
            HOUSE_TYPE_COMMONS => array(
                'singular' => 'MP',
                'plural'   => 'MPs'
            ),
            HOUSE_TYPE_LORDS => array(
                'singular' => 'Member of the House of Lords',
                'plural'   => 'Members of the House of Lords'
            ),
            HOUSE_TYPE_NI => array(
                'singular' => 'MLA',
                'plural'   => 'MLAs'
            ),
            HOUSE_TYPE_SCOTLAND => array(
                'singular' => 'MSP',
                'plural'   => 'MSPs'
            ),
            HOUSE_TYPE_WALES => array(
                'singular' => gettext('MS'),
                'plural'   => gettext('MSs')
            ),
            HOUSE_TYPE_LONDON_ASSEMBLY => array(
                'singular' => 'Member of the London Assembly',
                'plural'   => 'Members of the London Assembly'
            )
        );

        return $house_to_members[$house];
    }

    public static function getCountryDetails($house) {
        $details = array(
            HOUSE_TYPE_COMMONS => array (
                'country' => 'UK',
                'assembly' => 'uk-commons',
                'location' => '&ndash; in the House of Commons',
                'cons_type' => 'WMC',
                'assembly_name' => 'House of Commons',
            ),
            HOUSE_TYPE_NI => array (
                'country' => 'NORTHERN IRELAND',
                'assembly' => 'ni',
                'location' => '&ndash; in the Northern Ireland Assembly',
                'cons_type' => 'NIE',
                'assembly_name' => 'Northern Ireland Assembly',
            ),
            HOUSE_TYPE_SCOTLAND => array (
                'country' => 'SCOTLAND',
                'assembly' => 'scotland',
                'location' => '&ndash; in the Scottish Parliament',
                'cons_type' => 'SPC',
                'assembly_name' => 'Scottish Parliament',
            ),
            HOUSE_TYPE_WALES => array (
                'country' => 'WALES',
                'assembly' => 'wales',
                'location' => '&ndash; in the Senedd',
                'cons_type' => 'WAC',
                'assembly_name' => 'Senedd',
            ),
            HOUSE_TYPE_LORDS => array (
                'country' => 'UK',
                'assembly' => 'uk-lords',
                'location' => '&ndash; in the House of Lords',
                'cons_type' => '',
                'assembly_name' => 'House of Lords',
            ),
            HOUSE_TYPE_LONDON_ASSEMBLY => array (
                'country' => 'UK',
                'assembly' => 'london-assembly',
                'location' => '&ndash; in the London Assembly',
                'cons_type' => 'LAS',
                'assembly_name' => 'London Assembly',
            )
        );
        if (!array_key_exists($house, $details)) {
            return array('', '', '', '', '');
        }

        $detail = $details[$house];
        return array($detail['country'], $detail['location'], $detail['assembly'], $detail['cons_type'], $detail['assembly_name']);
    }

    public static function majorToHouse($major) {
        $major_to_house = array(
            1 => array(HOUSE_TYPE_COMMONS),
            2 => array(HOUSE_TYPE_COMMONS),
            3 => array(HOUSE_TYPE_COMMONS, HOUSE_TYPE_LORDS),
            4 => array(HOUSE_TYPE_COMMONS, HOUSE_TYPE_LORDS),
            5 => array(HOUSE_TYPE_NI),
            6 => array(HOUSE_TYPE_COMMONS),
            7 => array(HOUSE_TYPE_SCOTLAND),
            8 => array(HOUSE_TYPE_SCOTLAND),
            9 => array(HOUSE_TYPE_LONDON_ASSEMBLY),
            10 => array(HOUSE_TYPE_WALES),
            11 => array(HOUSE_TYPE_WALES),
            101 => array(HOUSE_TYPE_LORDS),
        );

        return $major_to_house[$major];
    }
}

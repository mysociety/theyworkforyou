<?php

namespace MySociety\TheyWorkForYou\Utility;

/**
 * House Utilities
 *
 * Utility functions related to house types
 */

class House {
    public static function division_house_name_to_number($name) {
        $name_to_number = [
            'commons' => HOUSE_TYPE_COMMONS,
            'lords' => HOUSE_TYPE_LORDS,
            'scotland' => HOUSE_TYPE_SCOTLAND,
            'pbc' => HOUSE_TYPE_COMMONS,
            'senedd' => HOUSE_TYPE_WALES,
        ];

        return $name_to_number[$name];
    }

    public static function house_to_members($house) {
        $house_to_members = [
            HOUSE_TYPE_COMMONS => [
                'singular' => 'MP',
                'plural'   => 'MPs',
            ],
            HOUSE_TYPE_LORDS => [
                'singular' => 'Member of the House of Lords',
                'plural'   => 'Members of the House of Lords',
            ],
            HOUSE_TYPE_NI => [
                'singular' => 'MLA',
                'plural'   => 'MLAs',
            ],
            HOUSE_TYPE_SCOTLAND => [
                'singular' => 'MSP',
                'plural'   => 'MSPs',
            ],
            HOUSE_TYPE_WALES => [
                'singular' => gettext('MS'),
                'plural'   => gettext('MSs'),
            ],
            HOUSE_TYPE_LONDON_ASSEMBLY => [
                'singular' => 'Member of the London Assembly',
                'plural'   => 'Members of the London Assembly',
            ],
        ];

        return $house_to_members[$house];
    }

    /**
     * Introductory copy for the committees section of a member's page.
     *
     * Each parliament calls these bodies something slightly different and
     * gives them different jobs, so the wording is per house rather than the
     * Westminster description being shown to everybody.
     *
     * @return array A list of paragraphs.
     */
    public static function committeeIntro($house) {
        $intros = [
            HOUSE_TYPE_COMMONS => [
                gettext('In the UK Parliament, committees are groups of MPs or Peers who examine specific issues in more detail than can be done in debates.'),
                gettext('Some committees focus on checking the government\'s decisions and spending, while others investigate specific topics and proposed legislation.'),
            ],
            HOUSE_TYPE_LORDS => [
                gettext('In the UK Parliament, committees are groups of MPs or Peers who examine specific issues in more detail than can be done in debates.'),
                gettext('Some committees focus on checking the government\'s decisions and spending, while others investigate specific topics and proposed legislation.'),
            ],
            HOUSE_TYPE_SCOTLAND => [
                gettext('In the Scottish Parliament, committees are groups of MSPs who scrutinise the work of the Scottish Government, examine proposed legislation and conduct inquiries.'),
                gettext('Much of the Parliament\'s detailed work happens in committee rather than in the chamber.'),
            ],
            HOUSE_TYPE_WALES => [
                gettext('In the Senedd, committees are groups of Members who scrutinise the work of the Welsh Government, examine proposed legislation and conduct inquiries.'),
                gettext('Much of the Senedd\'s detailed work happens in committee rather than in the chamber.'),
            ],
            HOUSE_TYPE_NI => [
                gettext('In the Northern Ireland Assembly, statutory committees advise and assist each Minister, and scrutinise the work of their department.'),
                gettext('Standing and ad hoc committees deal with the running of the Assembly and with particular pieces of business.'),
            ],
        ];

        return $intros[$house] ?? [];
    }

    /**
     * What this house calls its informal cross-party membership groups.
     */
    public static function groupsName($house) {
        if ($house == HOUSE_TYPE_SCOTLAND || $house == HOUSE_TYPE_WALES) {
            return gettext('Cross-Party Groups');
        }
        return gettext('All-Party Parliamentary Groups');
    }

    public static function getCountryDetails($house) {
        $details = [
            HOUSE_TYPE_COMMONS =>  [
                'country' => 'UK',
                'assembly' => 'uk-commons',
                'location' => '&ndash; in the House of Commons',
                'cons_type' => 'WMC',
                'assembly_name' => 'House of Commons',
            ],
            HOUSE_TYPE_NI =>  [
                'country' => 'NORTHERN IRELAND',
                'assembly' => 'ni',
                'location' => '&ndash; in the Northern Ireland Assembly',
                'cons_type' => 'NIE',
                'assembly_name' => 'Northern Ireland Assembly',
            ],
            HOUSE_TYPE_SCOTLAND =>  [
                'country' => 'SCOTLAND',
                'assembly' => 'scotland',
                'location' => '&ndash; in the Scottish Parliament',
                'cons_type' => 'SPC',
                'assembly_name' => 'Scottish Parliament',
            ],
            HOUSE_TYPE_WALES =>  [
                'country' => 'WALES',
                'assembly' => 'wales',
                'location' => '&ndash; in the Senedd',
                'cons_type' => 'WAC',
                'assembly_name' => 'Senedd',
            ],
            HOUSE_TYPE_LORDS =>  [
                'country' => 'UK',
                'assembly' => 'uk-lords',
                'location' => '&ndash; in the House of Lords',
                'cons_type' => '',
                'assembly_name' => 'House of Lords',
            ],
            HOUSE_TYPE_LONDON_ASSEMBLY =>  [
                'country' => 'UK',
                'assembly' => 'london-assembly',
                'location' => '&ndash; in the London Assembly',
                'cons_type' => 'LAS',
                'assembly_name' => 'London Assembly',
            ],
        ];
        if (!array_key_exists($house, $details)) {
            return ['', '', '', '', ''];
        }

        $detail = $details[$house];
        return [$detail['country'], $detail['location'], $detail['assembly'], $detail['cons_type'], $detail['assembly_name']];
    }

    public static function majorToHouse($major) {
        $major_to_house = [
            1 => [HOUSE_TYPE_COMMONS],
            2 => [HOUSE_TYPE_COMMONS],
            3 => [HOUSE_TYPE_COMMONS, HOUSE_TYPE_LORDS],
            4 => [HOUSE_TYPE_COMMONS, HOUSE_TYPE_LORDS],
            5 => [HOUSE_TYPE_NI],
            6 => [HOUSE_TYPE_COMMONS],
            7 => [HOUSE_TYPE_SCOTLAND],
            8 => [HOUSE_TYPE_SCOTLAND],
            9 => [HOUSE_TYPE_LONDON_ASSEMBLY],
            10 => [HOUSE_TYPE_WALES],
            11 => [HOUSE_TYPE_WALES],
            101 => [HOUSE_TYPE_LORDS],
        ];

        return $major_to_house[$major];
    }
}

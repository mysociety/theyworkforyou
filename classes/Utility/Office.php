<?php

namespace MySociety\TheyWorkForYou\Utility;

/**
 * Office Utilities
 *
 * Utility functions related to offices
 */

class Office
{

    /**
     * Prettify Office
     *
     * Takes an office, and returns a prettified version.
     *
     * @param string $pos  The name of the office
     * @param string $dept The name of the department
     *
     * @return string The prettified office
     */
    public static function prettifyOffice($pos, $dept) {
        $lookup = array(
            'Prime Minister, HM Treasury' => 'Prime Minister',
            'Secretary of State, Foreign & Commonwealth Office' => 'Foreign Secretary',
            'Secretary of State, Home Office' => 'Home Secretary',
            'Minister of State (Energy), Department of Trade and Industry'
                => 'Minister for energy, Department of Trade and Industry',
            'Minister of State (Pensions), Department for Work and Pensions'
                => 'Minister for pensions, Department for Work and Pensions',
            'Parliamentary Secretary to the Treasury, HM Treasury'
                => 'Chief Whip (technically Parliamentary Secretary to the Treasury)',
            "Treasurer of Her Majesty's Household, HM Household"
                => "Deputy Chief Whip (technically Treasurer of Her Majesty's Household)",
            'Comptroller, HM Household' => 'Government Whip (technically Comptroller, HM Household)',
            'Vice Chamberlain, HM Household' => 'Government Whip (technically Vice Chamberlain, HM Household)',
            'Lords Commissioner, HM Treasury' => 'Government Whip (technically a Lords Commissioner, HM Treasury)',
            'Assistant Whip, HM Treasury' => 'Assistant Whip (funded by HM Treasury)',
            'Lords in Waiting, HM Household' => 'Government Whip (technically a Lord in Waiting, HM Household)',
        );
        // TODO: This should probably be a slightly more strict evaluation
        if ($pos) { # Government post, or Chairman of Select Committee
            $pretty = $pos;
            if ($dept && $dept != 'No Department') $pretty .= ", $dept";
            if (array_key_exists($pretty, $lookup))
                $pretty = $lookup[$pretty];
        } else { # Member of Select Committee
            $pretty = "Member, $dept";
        }
        return $pretty;
    }

}

<?php

namespace MySociety\TheyWorkForYou\Utility;

/**
 * Party Utilities
 *
 * Utility functions related to parties
 */

class Party
{

    // From http://news.bbc.co.uk/nol/shared/bsp/hi/vote2004/css/styles.css
    private $party_colours = array(
        "Conservative" => "#333399",
        "DU" => "#cc6666",
        "Ind" => "#eeeeee",
        "Ind Con" => "#ddddee",
        "Ind Lab" => "#eedddd",
        "Ind UU" => "#ccddee",
        "Labour" => "#cc0000",
        "Lab/Co-op" => "#cc0000",
        "LDem" => "#f1cc0a", #"#ff9900",
        "PC" => "#33CC33",
        "SDLP" => "#8D9033",
        "SF" => "#2B7255",
        "SNP" => "#FFCC00",
        "UKU" => "#99CCFF",
        "UU" => "#003677",

        "Speaker" => "#999999",
        "Deputy Speaker" => "#999999",
        "CWM" => "#999999",
        "DCWM" => "#999999",
        "SPK" => "#999999",
    );

    /**
     * Get Party Colour
     *
     * Takes a party name and returns the party's colour.
     *
     * @param string $party The name of the party to find a colour for.
     *
     * @return string The hex colour code for the given party.
     */

    public static function partyToColour($party) {
        if (isset(self::$party_colours[$party])) {
            return self::$party_colours[$party];
        } else {
            return "#eeeeee";
        }
    }

}

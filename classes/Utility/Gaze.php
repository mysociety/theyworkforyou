<?php

namespace MySociety\TheyWorkForYou\Utility;

/**
 * Gaze commonlib Wrapper
 *
 * Provides a wrapper for Gaze functions within commonlib.
 */

class Gaze
{

    /**
     * Get Country by IP Address
     *
     * Takes an IP address and looks it up against Gaze to return the country.
     */

    public static function getCountryByIp($ip)
    {

        include_once INCLUDESPATH . '../../commonlib/phplib/gaze.php';

        if (defined('OPTION_GAZE_URL') && OPTION_GAZE_URL) {
            return gaze_get_country_from_ip($ip);
        } else {
            return NULL;
        }

    }

}

<?php

namespace MySociety\TheyWorkForYou\Utility;

/**
 * Validation Utilities
 *
 * Utility functions for validating pieces of data.
 */

class Validation
{

    /**
     * Validate Email Address
     *
     * Far from foolproof, but better than nothing.
     *
     * @param string $string The email address to validate.
     *
     * @return bool Is the email valid or not?
     */
    public static function validateEmail($string) {
        if (!preg_match('/^[-!#$%&\'*+\\.\/0-9=?A-Z^_`a-z{|}~]+'.
            '@'.
            '[-!#$%&\'*.\\+\/0-9=?A-Z^_`a-z{|}~]+\.'.
            '[-!#$%&\'*+\\.\/0-9=?A-Z^_`a-z{|}~]+$/', $string)) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * Validate Postcode
     *
     * See http://www.govtalk.gov.uk/gdsc/html/noframes/PostCode-2-1-Release.htm
     *
     * @param string $postcode The postcode to validate.
     *
     * @return bool Is the postcode valid or not?
     */
    public static function validatePostcode($postcode) {

        $postcode = trim($postcode);

        $in  = 'ABDEFGHJLNPQRSTUWXYZ';
        $fst = 'ABCDEFGHIJKLMNOPRSTUWYZ';
        $sec = 'ABCDEFGHJKLMNOPQRSTUVWXY';
        $thd = 'ABCDEFGHJKSTUW';
        $fth = 'ABEHMNPRVWXY';
        $num = '0123456789';
        $nom = '0123456789';
        $gap = '\s\.';

        if (    preg_match("/^[$fst][$num][$gap]*[$nom][$in][$in]$/i", $postcode) ||
                preg_match("/^[$fst][$num][$num][$gap]*[$nom][$in][$in]$/i", $postcode) ||
                preg_match("/^[$fst][$sec][$num][$gap]*[$nom][$in][$in]$/i", $postcode) ||
                preg_match("/^[$fst][$sec][$num][$num][$gap]*[$nom][$in][$in]$/i", $postcode) ||
                preg_match("/^[$fst][$num][$thd][$gap]*[$nom][$in][$in]$/i", $postcode) ||
                preg_match("/^[$fst][$sec][$num][$fth][$gap]*[$nom][$in][$in]$/i", $postcode)
            ) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Validate URL
     *
     * @param string $url The URL to validate.
     *
     * @return bool Is the URL valid or not?
     */
    public static function validateUrl($url) {
        $return = false;
        if (preg_match("/^(http|https|ftp):\/\/(([A-Z0-9][A-Z0-9_-]*)(\.[A-Z0-9][A-Z0-9_-]*)+)(:(\d+))?/i", $url)) {
            $return = true;
        }
        return $return;
    }

}

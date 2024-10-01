<?php

namespace MySociety\TheyWorkForYou\Utility;

/**
 * Postcode Utilities
 *
 * Utility functions related to postcodes
 */
class Postcode {
    /**
     * Postcode To Constituency
     */

    public static function postcodeToConstituency($postcode) {
        return self::postcodeToConstituenciesInternal($postcode, true);
    }

    /**
     * Postcode To Constituencies
     */

    public static function postcodeToConstituencies($postcode) {
        return self::postcodeToConstituenciesInternal($postcode, false);
    }

    /**
     * Postcode To Constituencies (Internal)
     *
     * @param boolean $mp_only
     */

    private static function postcodeToConstituenciesInternal($postcode, $mp_only) {
        global $last_postcode, $last_postcode_value;

        $postcode = preg_replace('#[^a-z0-9]#i', '', $postcode);
        $postcode = self::canonicalisePostcode($postcode);

        if ($last_postcode == $postcode) {
            $return_value = $mp_only ? $last_postcode_value['WMC'] : $last_postcode_value;
            twfy_debug("TIME", "Postcode $postcode looked up last time, is " . (is_array($return_value) ? implode(', ', $return_value) : $return_value));
            return $return_value;
        }

        if (!validate_postcode($postcode)) {
            return '';
        }

        $ret = self::postcodeFetchFromDb($postcode);
        if (!$ret) {
            $ret = self::postcodeFetchFromMapit($postcode);
        }

        if (is_string($ret)) {
            return $ret;
        }

        $last_postcode = $postcode;
        $last_postcode_value = $ret;
        return $mp_only ? $ret['WMC'] : $ret;
    }

    /**
     * Fetch Postcode Information from DB
     *
     * @param string $postcode
     */

    private static function postcodeFetchFromDb($postcode) {
        $db = new \ParlDB();
        $q = $db->query('select name from postcode_lookup where postcode = :postcode', [
            ':postcode' => $postcode,
        ])->first();

        if ($q) {
            $name = $q['name'];
            $country = '';
            $parts = explode(';', $name);
            if (count($parts) > 1) {
                $country = $parts[0];
                $name = $parts[1];
            }
            $name = explode('|', $name);
            if ($country == 'W') {
                return ['WMC' => $name[0], 'WAC' => $name[1], 'WAE' => $name[2]];
            } elseif ($country == 'S' || count($name) == 3) {
                return ['WMC' => $name[0], 'SPC' => $name[1], 'SPE' => $name[2]];
            } elseif ($country == 'N' || count($name) == 2) {
                return ['WMC' => $name[0], 'NIE' => $name[1]];
            } else {
                return ['WMC' => $name[0]];
            }
        }
    }

    /**
     * Fetch Postcode Information from MapIt
     *
     * @param string $postcode
     */

    private static function postcodeFetchFromMapit($postcode) {
        if (!defined('OPTION_MAPIT_URL') || !OPTION_MAPIT_URL) {
            return '';
        }
        $filename = 'postcode/' . rawurlencode($postcode);
        $ch = curl_init(OPTION_MAPIT_URL . $filename);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 20);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        $file = curl_exec($ch);
        if (curl_errno($ch)) {
            $errno = curl_errno($ch);
            trigger_error("Postcode database: " . $errno . ' ' . curl_error($ch), E_USER_WARNING);
            return 'CONNECTION_TIMED_OUT';
        }
        curl_close($ch);

        $r = json_decode($file, true);
        if (!$r) {
            trigger_error("Postcode database is not working. Content:\n" . $file . ", request: " . $filename, E_USER_WARNING);
            return '';
        }
        if (isset($r['error']) || !isset($r['areas'])) {
            return '';
        }
        $areas = [];
        foreach ($r['areas'] as $row) {
            if (in_array($row['type'], ['WMC', 'SPC', 'SPE', 'NIE', 'WAC', 'WAE'])) {
                $areas[$row['type']] = $row['name'];
            }
        }

        if (!isset($areas['WMC'])) {
            return '';
        }

        # Normalise name - assume SP and NI are already so...
        $normalised = Constituencies::normaliseConstituencyName(strtolower($areas['WMC']));
        if ($normalised) {
            $areas['WMC'] = $normalised;
            if (isset($areas['SPC'])) {
                $serialized = "S;$areas[WMC]|$areas[SPC]|$areas[SPE]";
            } elseif (isset($areas['NIE'])) {
                $serialized = "N;$areas[WMC]|$areas[NIE]";
            } elseif (isset($areas['WAC'])) {
                $serialized = "W;$areas[WMC]|$areas[WAC]|$areas[WAE]";
            } else {
                $serialized = "E;$areas[WMC]";
            }
            $db = new \ParlDB();
            $db->query(
                'replace into postcode_lookup values(:postcode, :serialized)',
                [
                    ':postcode' => $postcode,
                    ':serialized' => $serialized,
                ]
            );
        } else {
            return '';
        }

        return $areas;
    }

    /**
     * Canonicalise Postcode
     *
     * Take a postcode and turn it into a tidied, uppercased canonical version.
     */

    public static function canonicalisePostcode($pc) {
        $pc = str_replace(' ', '', $pc);
        $pc = trim($pc);
        $pc = strtoupper($pc);
        $pc = preg_replace('#(\d[A-Z]{2})#', ' $1', $pc);
        return $pc;
    }

}

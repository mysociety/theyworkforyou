<?php

namespace MySociety\TheyWorkForYou\Utility;

/**
 * Hansard Utilities
 *
 * Utility functions related to content
 */

class Hansard {
    public static function get_gid_from_url($url) {
        $gid = null;
        $parts = parse_url($url);
        parse_str($parts['query'], $query);

        if ($query['id']) {
            if (strpos($parts['path'], 'lords') !== false) {
                $gid = 'uk.org.publicwhip/lords/';
            } elseif (strpos($parts['path'], 'whall') !== false) {
                $gid = 'uk.org.publicwhip/westminhall/';
            } else {
                $gid = 'uk.org.publicwhip/debate/';
            }
            $gid .= $query['id'];
        }
        return $gid;
    }


    public static function gid_to_url($gid) {
        if (!$gid) {
            return '';
        }
        global $hansardmajors;
        $db = new \ParlDB();

        $q = $db->query("SELECT major FROM hansard WHERE gid = :gid", [ ':gid' => $gid ])->first();
        $url_gid = fix_gid_from_db($gid);
        $url = new \MySociety\TheyWorkForYou\Url($hansardmajors[$q['major']]['page']);
        $url->insert(['id' => $url_gid]);
        return $url->generate();
    }
}

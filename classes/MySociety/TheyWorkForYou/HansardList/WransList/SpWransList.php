<?php
/**
 * SpWransList Class
 *
 * @package TheyWorkForYou
 */

namespace MySociety\TheyWorkForYou\HansardList\WransList;

class SpWransList extends \MySociety\TheyWorkForYou\HansardList\WransList {
    public $major = 8;
    public $listpage = 'spwrans';
    public $commentspage = 'spwrans';
    public $gidprefix = 'uk.org.publicwhip/spwa/';

    public function get_gid_from_spid($spid) {
        // Fix the common errors of S.0 instead of S.O and leading
        // zeros in the numbers:
        $fixed_spid = preg_replace('/(S[0-9]+)0-([0-9]+)/','${1}O-${2}',$spid);
        $fixed_spid = preg_replace('/(S[0-9]+\w+)-0*([0-9]+)/','${1}-${2}',$fixed_spid);
        $q = $this->db->query(
            "select mentioned_gid from mentions where gid = :gid_from_spid and (type = 4 or type = 6)",
            array(':gid_from_spid' => 'uk.org.publicwhip/spq/' . $fixed_spid)
        );
        $gid = $q->field(0, 'mentioned_gid');
        if ($gid) return $gid;
        return null;
    }
    public function old_get_gid_from_spid($spid) {
        $q = $this->db->query(
            "select gid from hansard where gid like :gid_like",
            array(':gid_like' => 'uk.org.publicwhip/spwa/%.' . $spid . '.h')
        );
        $gid = $q->field(0, 'gid');
        if ($gid) return str_replace('uk.org.publicwhip/spwa/', '', $gid);
        return null;
    }
}

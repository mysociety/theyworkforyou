<?php

namespace MySociety\TheyWorkForYou;

class Gid {
    public $gid;
    private $db;

    public function __construct($gid) {
        $this->gid = $gid;
        $this->db = new \ParlDB();
    }

    public function checkForRedirect() {
        $q = $this->db->query(
            "SELECT gid_to FROM gidredirect WHERE gid_from = :gid",
            [':gid' => $this->gid]
        )->first();

        if (!$q) {
            return $this->gid;
        } else {
            do {
                $gid = $q['gid_to'];
                $q = $this->db->query(
                    "SELECT gid_to FROM gidredirect WHERE gid_from = :gid",
                    [':gid' => $gid]
                )->first();
            } while ($q);
            return $gid;
        }
    }
}

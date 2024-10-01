<?php
/**
 * Party Class
 *
 * @package TheyWorkForYou
 */

namespace MySociety\TheyWorkForYou;

/**
 * Party
 */

class Party {
    public $name;

    private $db;

    public function __construct($name) {
        // treat Labour and Labour/Co-operative the same as that's how
        // people view them and it'll confuse the results otherwise
        if ($name == 'Labour/Co-operative') {
            $name = 'Labour';
        }
        $this->name = $name;
        $this->db = new \ParlDB();
    }

    public function getCurrentMemberCount($house) {
        $dissolution = Dissolution::dates();
        if (isset($dissolution[$house])) {
            $date = $dissolution[$house];
        } else {
            $date = date('Y-m-d');
        }
        $member_count = $this->db->query(
            "SELECT count(*) as num_members
            FROM member
            WHERE
                party = :party
                AND house = :house
                AND entered_house <= :date
                AND left_house >= :date",
            [
                ':party' => $this->name,
                ':house' => $house,
                ':date' => $date,
            ]
        )->first();
        if ($member_count) {
            $num_members = $member_count['num_members'];
            return $num_members;
        } else {
            return 0;
        }
    }

    public static function getParties() {
        $db = new \ParlDB();

        $party_list = $db->query(
            "SELECT DISTINCT party FROM member WHERE party <> ''"
        );

        $parties = [];
        foreach ($party_list as $row) {
            $party = $row['party'];
            if (
                !$party
                || $party == 'Independent'
                || $party == 'Crossbench'
            ) {
                continue;
            }
            $parties[] = $party;
        }

        return $parties;
    }
}

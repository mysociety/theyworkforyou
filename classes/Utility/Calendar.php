<?php

namespace MySociety\TheyWorkForYou\Utility;

/**
 * Calendar Utilities
 *
 * Utility functions related to calendars
 */

class Calendar {
    public static function minFutureDate() {
        $db = new \ParlDB();
        $q = $db->query('SELECT MIN(event_date) AS m FROM future WHERE event_date >= DATE(NOW()) AND deleted = 0')->first();
        return $q['m'];
    }

    private static function fetchQuery($where, $order_by = '', $params = null) {
        $query = "SELECT pn.person_id, pn.given_name, pn.family_name, pn.lordofname, pn.title AS name_title, member.house,
            future.*
            FROM future
            LEFT JOIN future_people ON future.id = future_people.calendar_id AND witness = 0
            LEFT JOIN member ON future_people.person_id = member.person_id AND member.left_house = (SELECT MAX(left_house) from member where member.person_id = future_people.person_id)
            LEFT JOIN person_names pn ON future_people.person_id = pn.person_id AND pn.type = 'name' AND pn.end_date = (SELECT MAX(end_date) from person_names where person_names.person_id = future_people.person_id)
            WHERE $where";
        if ($order_by) {
            $query .= " ORDER BY $order_by";
        }
        $db = new \ParlDB();
        return $db->query($query, $params);
    }

    public static function fetchFuture() {
        $date = date('Y-m-d');
        $q = self::fetchQuery("event_date >= :date AND deleted = 0", "event_date, chamber, pos", [':date' => $date]);
        return self::tidyData($q);
    }

    public static function fetchDate($date) {
        global $DATA, $PAGE, $this_page;

        $q = self::fetchQuery("event_date = '$date' AND deleted = 0", "chamber, pos");

        if (!$q->rows()) {
            if ($date >= date('Y-m-d')) {
                $PAGE->error_message('There is currently no information available for that date.', false, 404);
            } else {
                $PAGE->error_message('There is no information available for that date.', false, 404);
            }

            return [];
        }

        $DATA->set_page_metadata($this_page, 'date', $date);

        return self::tidyData($q);
    }

    public static function fetchItem($id) {
        $q = self::fetchQuery("future.id = $id AND deleted=0");
        return self::tidyData($q);
    }

    private static function tidyData($q) {
        $data = [];
        $seen = [];
        $people = [];
        foreach ($q as $row) {
            if ($row['person_id']) {
                $name = member_full_name($row['house'], $row['name_title'], $row['given_name'], $row['family_name'], $row['lordofname']);
                $people[$row['id']][$row['person_id']] = $name;
            }
        }
        foreach ($q as $row) {
            if (isset($seen[$row['id']])) {
                continue;
            }
            if (isset($people[$row['id']])) {
                $row['person_id'] = $people[$row['id']];
            }
            $data[$row['event_date']][$row['chamber']][] = $row;
            $seen[$row['id']] = true;
        }
        return $data;
    }

    public static function displayEntry($e) {
        [$title, $meta] = self::meta($e);

        if (strstr($e['chamber'], 'Select Committee')) {
            print '<dt class="sc" id="cal' . $e['id'] . '">';
        } else {
            print '<li id="cal' . $e['id'] . '">';
        }

        print "$title ";

        if ($meta) {
            print '<span>' . join('; ', $meta) . '</span>';
        }

        if (strstr($e['chamber'], 'Select Committee')) {
            print "</dt>\n";
        } else {
            print "</li>\n";
        }

        if ($e['witnesses']) {
            print "<dd>";
            print 'Witnesses: <ul><li>' . str_replace("\n", '<li>', $e['witnesses']) . '</ul>';
            print "</dd>\n";
        }
    }

    public static function meta($e) {
        if ($e['committee_name']) {
            $title = $e['committee_name'];
            if ($e['title'] == 'to consider the Bill') {
            } elseif ($e['title']) {
                $title .= ': ' . $e['title'];
            }
        } else {
            $title = $e['title'];
            if ($people = $e['person_id']) {
                foreach ($people as $pid => $name) {
                    $title .= " &#8211; <a href='/mp/?p=$pid'>$name</a>";
                }
            }
        }

        $meta = [];

        if ($d = $e['debate_type']) {

            if ($d == 'Adjournment') {
                $d = 'Adjournment debate';
            }

            $meta[] = $d;
        }

        if ($e['time_start'] || $e['location']) {

            if ($e['time_start']) {

                $time = format_time($e['time_start'], TIMEFORMAT);

                if ($e['time_end']) {
                    $time .= ' &#8211; ' . format_time($e['time_end'], TIMEFORMAT);
                }

                $meta[] = $time;
            }

            if ($e['location']) {
                $meta[] = $e['location'];
            }
        }

        return [$title, $meta];
    }

}

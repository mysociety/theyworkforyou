<?php

namespace MySociety\TheyWorkForYou\Utility;

/**
 * Calendar Utilities
 *
 * Utility functions related to calendars
 */

class Calendar
{

    public static function minFutureDate() {
        $db = new \ParlDB();
        $q = $db->query('SELECT MIN(event_date) AS m FROM future WHERE event_date >= DATE(NOW()) AND deleted = 0');

        return $q->field(0, 'm');
    }

    public static function fetchDate($date) {
        global $DATA, $PAGE, $this_page;
        $db = new \ParlDB();

        $q = $db->query("SELECT * FROM future
            LEFT JOIN future_people ON future.id = future_people.calendar_id AND witness = 0
            WHERE event_date = '$date'
            AND deleted = 0
            ORDER BY chamber, pos");

        if (!$q->rows()) {
            if ($date >= date('Y-m-d')) {
                $PAGE->error_message('There is currently no information available for that date.');
            } else {
                $PAGE->error_message('There is no information available for that date.');
            }

            return array();
        }

        $DATA->set_page_metadata($this_page, 'date', $date);

        $data = array();
        foreach ($q->data as $row) {
            $data[$row['event_date']][$row['chamber']][] = $row;
        }

        return $data;
    }

    public static function displayEntry($e) {
        list($title, $meta) = self::meta($e);

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
            print '<a href=" $e[link_calendar] "></a>';
            print '<a href=" $e[link_external] "></a>';
            print 'Witnesses: ' . $e['witnesses'];
            print "</dd>\n";
        }
    }

    public static function meta($e) {
        $private = false;
        if ($e['committee_name']) {
            $title = $e['committee_name'];
            if ($e['title'] == 'to consider the Bill') {
            } elseif ($e['title'] && $e['title'] != 'This is a private meeting.') {
                $title .= ': ' . $e['title'];
            } else {
                $private = true;
            }
        } else {
            $title = $e['title'];
            if ($pid = $e['person_id']) {
                $MEMBER = new \MEMBER(array( 'person_id' => $pid ));
                $name = $MEMBER->full_name();
                $title .= " &#8211; <a href='/mp/?p=$pid'>$name</a>";
            }
        }

        $meta = array();

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

        if ($private) {
            $meta[] = 'Private meeting';
        }

        return array($title, $meta);
    }

}

<?php

namespace MySociety\TheyWorkForYou\Utility;

/**
 * DateTime Utilities
 *
 * Utility functions for formatting and parsing dates and times.
 */

class DateTime
{

    private $format_date_months = array(
        '',
        'January',
        'February',
        'March',
        'April',
        'May',
        'June',
        'July',
        'August',
        'September',
        'October',
        'November',
        'December'
    );

    private $format_date_months_short = array(
        '',
        'Jan',
        'Feb',
        'Mar',
        'Apr',
        'May',
        'Jun',
        'Jul',
        'Aug',
        'Sep',
        'Oct',
        'Nov',
        'Dec'
    );

    /**
     * Format Timestamp
     *
     * Pass it a MYSQL TIMESTAMP (YYYYMMDDHHMMSS) and a PHP date format string
     * (eg, "Y-m-d H:i:s") and it returns a nicely formatted string according to
     * requirements.
     *
     * Because strtotime can't handle TIMESTAMPS.
     *
     * @param string $timestamp The MySQL TIMESTAMP to be formatted.
     * @param string $format    The desired format of the timestamp.
     *
     * @return string The formatted TIMESTAMP.
     */

    public static function formatTimestamp($timestamp, $format) {

        if (preg_match("/^(\d\d\d\d)(\d\d)(\d\d)(\d\d)(\d\d)(\d\d)$/", $timestamp, $matches)) {
            list($string, $year, $month, $day, $hour, $min, $sec) = $matches;

            return gmdate ($format, gmmktime($hour, $min, $sec, $month, $day, $year));
        } else {
            return "";
        }

    }

    /**
     * Format Date
     *
     * Pass it a date (YYYY-MM-DD) and a PHP date format string
     * (eg, "Y-m-d H:i:s") and it returns a nicely formatted string according to
     * requirements.
     *
     * @param string $date   The date to be formatted.
     * @param string $format The desired format of the date.
     *
     * @return string The formatted date.
     */
    public static function formatDate($date, $format) {

        if (preg_match("/^(\d\d\d\d)-(\d\d?)-(\d\d?)$/", $date, $matches)) {
            list($string, $year, $month, $day) = $matches;
            if ($year < 1902) { # gmdate fns only go back to Dec. 1901
                if ($format == SHORTDATEFORMAT) {
                    return ($day+0) . ' ' . self::$format_date_months_short[$month+0] . " $year";
                } else {
                    return ($day+0) . ' ' . self::$format_date_months[$month+0] . " $year";
                }
            }

            return gmdate ($format, gmmktime(0, 0, 0, $month, $day, $year));
        } else {
            return "";
        }

    }

    /**
     * Format Time
     *
     * Pass it a time (HH:MM:SS) and a PHP date format string (eg, "H:i") and it
     * returns a nicely formatted string according to requirements.
     *
     * @param string $time   The time to be formatted.
     * @param string $format The desired format of the time.
     *
     * @return string The formatted time.
     */
    public static function formatTime($time, $format) {

        if (preg_match("/^(\d\d):(\d\d):(\d\d)$/", $time, $matches)) {
            list($string, $hour, $min, $sec) = $matches;

            return gmdate ($format, gmmktime($hour, $min, $sec));
        } else {
            return "";
        }
    }

    /**
     * Relative Time
     *
     * Pass it a 'YYYY-MM-DD HH:MM:SS' and it will return something like "Two
     * hours ago", "Last week", etc.
     *
     * http://maniacalrage.net/projects/relative/
     *
     * @param string $time The date and time to be converted.
     *
     * @return string The relative time difference.
     */

    public static function relativeTime($datetime) {

        if (!preg_match("/\d\d\d\d-\d\d-\d\d \d\d\:\d\d\:\d\d/", $datetime)) {
            return '';
        }

        $in_seconds = strtotime($datetime);
        $now = time();

        $diff   =  $now - $in_seconds;
        $months =  floor($diff/2419200);
        $diff   -= $months * 2419200;
        $weeks  =  floor($diff/604800);
        $diff   -= $weeks*604800;
        $days   =  floor($diff/86400);
        $diff   -= $days * 86400;
        $hours  =  floor($diff/3600);
        $diff   -= $hours * 3600;
        $minutes = floor($diff/60);
        $diff   -= $minutes * 60;
        $seconds = $diff;


        if ($months > 0) {
            // Over a month old, just show the actual date.
            $date = substr($datetime, 0, 10);
            return self::formatDate($date, LONGDATEFORMAT);

        } else {
            $relative_date = '';
            if ($weeks > 0) {
                // Weeks and days
                $relative_date .= ($relative_date?', ':'').$weeks.' week'.($weeks>1?'s':'');
                $relative_date .= $days>0?($relative_date?', ':'').$days.' day'.($days>1?'s':''):'';
            } elseif ($days > 0) {
                // days and hours
                $relative_date .= ($relative_date?', ':'').$days.' day'.($days>1?'s':'');
                $relative_date .= $hours>0?($relative_date?', ':'').$hours.' hour'.($hours>1?'s':''):'';
            } elseif ($hours > 0) {
                // hours and minutes
                $relative_date .= ($relative_date?', ':'').$hours.' hour'.($hours>1?'s':'');
                $relative_date .= $minutes>0?($relative_date?', ':'').$minutes.' minute'.($minutes>1?'s':''):'';
            } elseif ($minutes > 0) {
                // minutes only
                $relative_date .= ($relative_date?', ':'').$minutes.' minute'.($minutes>1?'s':'');
            } else {
                // seconds only
                $relative_date .= ($relative_date?', ':'').$seconds.' second'.($seconds>1?'s':'');
            }
        }

        // Return relative date and add proper verbiage
        return $relative_date.' ago';

    }

    /**
     * Parse Date
     *
     * @todo: Figure out where `datetime_parse_local_date` comes from.
     */
    public static function parseDate($date) {
        return datetime_parse_local_date($date, time(), 'en', 'gb');
    }

}

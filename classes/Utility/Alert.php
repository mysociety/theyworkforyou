<?php

namespace MySociety\TheyWorkForYou\Utility;

/**
 * Alert Utilities
 *
 * Utility functions related to alerts
 */

class Alert {
    #XXX don't calculate this every time
    public static function sectionToTitle($section) {
        $section_map = [
            "uk" => gettext('All UK'),
            "debates" => gettext('House of Commons debates'),
            "whalls" => gettext('Westminster Hall debates'),
            "lords" => gettext('House of Lords debates'),
            "wrans" => gettext('Written answers'),
            "wms" => gettext('Written ministerial statements'),
            "standing" => gettext('Bill Committees'),
            "future" => gettext('Future Business'),
            "ni" => gettext('Northern Ireland Assembly Debates'),
            "scotland" => gettext('All Scotland'),
            "sp" => gettext('Scottish Parliament Debates'),
            "spwrans" => gettext('Scottish Parliament Written answers'),
            "wales" => gettext('Welsh parliament record'),
            "lmqs" => gettext('Questions to the Mayor of London'),
        ];

        return $section_map[$section];
    }
    public static function detailsToCriteria($details) {
        $criteria = [];

        if (!empty($details['keyword'])) {
            $criteria[] = $details['keyword'];
        }

        if (!empty($details['pid'])) {
            $criteria[] = 'speaker:' . $details['pid'];
        }

        if (!empty($details['search_section'])) {
            $criteria[] = 'section:' . $details['search_section'];
        }

        $criteria = join(' ', $criteria);
        return $criteria;
    }

    public static function forUser($email) {
        $db = new \ParlDB();
        $q = $db->query('SELECT * FROM alerts WHERE email = :email
            AND deleted != 1 ORDER BY created', [
            ':email' => $email,
        ]);

        $alerts = [];
        foreach ($q as $row) {
            $criteria = self::prettifyCriteria($row['criteria']);
            $parts = self::prettifyCriteria($row['criteria'], true);
            $token = $row['alert_id'] . '-' . $row['registrationtoken'];

            $status = 'confirmed';
            if (!$row['confirmed']) {
                $status = 'unconfirmed';
            } elseif ($row['deleted'] == 2) {
                $status = 'suspended';
            }

            $alert = [
                'token' => $token,
                'status' => $status,
                'criteria' => $criteria,
                'raw' => $row['criteria'],
                'keywords' => [],
                'exclusions' => [],
                'sections' => [],
            ];

            $alert = array_merge($alert, $parts);

            $alerts[] = $alert;
        }

        return $alerts;
    }

    public static function prettifyCriteria($alert_criteria, $as_parts = false) {
        $text = '';
        $parts = ['words' => [], 'sections' => [], 'exclusions' => []];
        if ($alert_criteria) {
            # check for phrases
            $alert_criteria = str_replace(' OR ', ' ', $alert_criteria);
            if (strpos($alert_criteria, '"') !== false) {
                # match phrases
                preg_match_all('/"([^"]*)"/', $alert_criteria, $phrases);
                # and then remove them from the criteria
                $alert_criteria = trim(preg_replace('/ +/', ' ', str_replace($phrases[0], "", $alert_criteria)));

                # and then create an array with the words and phrases
                $criteria = explode(' ', $alert_criteria);
                $criteria = array_merge($criteria, $phrases[1]);
            } else {
                $criteria = explode(' ', $alert_criteria);
            }
            $words = [];
            $exclusions = [];
            $sections = [];
            $sections_verbose = [];
            $spokenby = array_values(\MySociety\TheyWorkForYou\Utility\Search::speakerNamesForIDs($alert_criteria));

            foreach ($criteria as $c) {
                if (preg_match('#^section:(\w+)#', $c, $m)) {
                    $sections[] = $m[1];
                    $sections_verbose[] = self::sectionToTitle($m[1]);
                } elseif (strpos($c, '-') === 0) {
                    $exclusions[] = str_replace('-', '', $c);
                } elseif (!preg_match('#^speaker:(\d+)#', $c, $m)) {
                    $words[] = $c;
                }
            }
            if ($spokenby && count($words)) {
                $text = implode(' or ', $spokenby) . ' mentions [' . implode(' ', $words) . ']';
                $parts['spokenby'] = $spokenby;
                $parts['words'] = $words;
            } elseif (count($words)) {
                $text = '[' . implode(' ', $words) . ']' . ' is mentioned';
                $parts['words'] = $words;
            } elseif ($spokenby) {
                $text = implode(' or ', $spokenby) . " speaks";
                $parts['spokenby'] = $spokenby;
            }

            if ($sections) {
                $text = $text . " in " . implode(' or ', $sections_verbose);
                $parts['sections'] = $sections;
                $parts['sections_verbose'] = $sections_verbose;
            }

            $parts['exclusions'] = $exclusions;
        }
        if ($as_parts) {
            return $parts;
        }
        return $text;
    }

}

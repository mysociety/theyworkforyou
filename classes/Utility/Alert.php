<?php

namespace MySociety\TheyWorkForYou\Utility;

/**
 * Alert Utilities
 *
 * Utility functions related to alerts
 */

class Alert {
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
            $criteria = self::prettifyCriteria($row['criteria'], $row['ignore_speaker_votes']);
            $parts = self::prettifyCriteria($row['criteria'], $row['ignore_speaker_votes'], true);
            $token = $row['alert_id'] . '-' . $row['registrationtoken'];
            // simple_criteria is "First term, second, third (5 keywords)" or "First term"
            $num_terms = count($parts['words']);
            if ($num_terms > 3) {
                $extras = $num_terms - 3;
                $simple_criteria = implode(', ', array_slice($parts['words'], 0, 3)) . ' (' . sprintf(ngettext('+ %d more keyword', '+ %d more keywords', $extras), $extras) . ')';
            } elseif ($num_terms > 0 && $num_terms <= 3) {
                $simple_criteria = implode(', ', array_slice($parts['words'], 0, 3));
            } else {
                $simple_criteria = $criteria;
            }

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
                'simple_criteria' => $simple_criteria,
                'raw' => $row['criteria'],
                'ignore_speaker_votes' => $row['ignore_speaker_votes'],
                'keywords' => [],
                'exclusions' => [],
                'sections' => [],
            ];

            $alert = array_merge($alert, $parts);

            $alerts[] = $alert;
        }

        return $alerts;
    }

    public static function prettifyCriteria($alert_criteria, $ignore_speaker_votes = false, $as_parts = false) {
        $text = '';
        $parts = ['words' => [], 'sections' => [], 'exclusions' => [], 'match_all' => true, 'pid' => false];
        if ($alert_criteria) {
            # check for phrases
            if (strpos($alert_criteria, ' OR ') !== false) {
                $parts['match_all'] = false;
            }
            $alert_criteria = str_replace(' OR ', ' ', $alert_criteria);
            $alert_criteria = str_replace(['(', ')'], '', $alert_criteria);
            if (strpos($alert_criteria, '"') !== false) {
                # match phrases
                preg_match_all('/"([^"]*)"/', $alert_criteria, $phrases);
                # and then remove them from the criteria
                $alert_criteria = trim(preg_replace('/ +/', ' ', str_replace($phrases[0], "", $alert_criteria)));

                # and then create an array with the words and phrases
                $criteria = [];
                if ($alert_criteria != "") {
                    $criteria = explode(' ', $alert_criteria);
                }
                $criteria = array_merge($criteria, $phrases[1]);
            } else {
                $criteria = explode(' ', $alert_criteria);
            }
            $words = [];
            $exclusions = [];
            $sections = [];
            $sections_verbose = [];
            $speaker_parts = \MySociety\TheyWorkForYou\Utility\Search::speakerNamesForIDs($alert_criteria);
            $pids = array_keys($speaker_parts);
            $spokenby = array_values($speaker_parts);

            if (count($pids) == 1) {
                $parts['pid'] = $pids[0];
            }

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
                if ($ignore_speaker_votes) {
                    $text .= " (excluding votes)";
                }
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

<?php

namespace MySociety\TheyWorkForYou\Utility;

/**
 * Alert Utilities
 *
 * Utility functions related to alerts
 */

class Alert {
    public static function detailsToCriteria($details) {
        $criteria = [];

        if (!empty($details['keyword'])) {
            $criteria[] = $details['keyword'];
        }

        if (!empty($details['pid'])) {
            $criteria[] = 'speaker:' . $details['pid'];
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
            $token = $row['alert_id'] . '-' . $row['registrationtoken'];

            $status = 'confirmed';
            if (!$row['confirmed']) {
                $status = 'unconfirmed';
            } elseif ($row['deleted'] == 2) {
                $status = 'suspended';
            }

            $alerts[] = [
                'token' => $token,
                'status' => $status,
                'criteria' => $criteria,
                'raw' => $row['criteria'],
            ];
        }

        return $alerts;
    }

    public static function prettifyCriteria($alert_criteria) {
        $text = '';
        if ($alert_criteria) {
            $criteria = explode(' ', $alert_criteria);
            $words = [];
            $spokenby = array_values(\MySociety\TheyWorkForYou\Utility\Search::speakerNamesForIDs($alert_criteria));

            foreach ($criteria as $c) {
                if (!preg_match('#^speaker:(\d+)#', $c, $m)) {
                    $words[] = $c;
                }
            }
            if ($spokenby && count($words)) {
                $text = implode(' or ', $spokenby) . ' mentions [' . implode(' ', $words) . ']';
            } elseif (count($words)) {
                $text = '[' . implode(' ', $words) . ']' . ' is mentioned';
            } elseif ($spokenby) {
                $text = implode(' or ', $spokenby) . " speaks";
            }
        }
        return $text;
    }

}

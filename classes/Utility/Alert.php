<?php

namespace MySociety\TheyWorkForYou\Utility;

/**
 * Alert Utilities
 *
 * Utility functions related to alerts
 */

class Alert
{

    public static function detailsToCriteria($details) {
        $criteria = array();

        if (!empty($details['keyword'])) {
            $criteria[] = $details['keyword'];
        }

        if (!empty($details['pid'])) {
            $criteria[] = 'speaker:'.$details['pid'];
        }

        $criteria = join(' ', $criteria);
        return $criteria;
    }

    public static function manage($email) {
        $db = new \ParlDB;
        $q = $db->query('SELECT * FROM alerts WHERE email = :email
            AND deleted != 1 ORDER BY created', array(
                ':email' => $email
            ));
        $out = '';
        for ($i=0; $i<$q->rows(); ++$i) {
            $row = $q->row($i);
            $criteria = explode(' ',$row['criteria']);
            $ccc = array();
            $current = true;
            foreach ($criteria as $c) {
                if (preg_match('#^speaker:(\d+)#',$c,$m)) {
                    $MEMBER = new \MEMBER(array('person_id'=>$m[1]));
                    $ccc[] = 'spoken by ' . $MEMBER->full_name();
                    if (!$MEMBER->current_member_anywhere()) {
                        $current = false;
                    }
                } else {
                    $ccc[] = $c;
                }
            }
            $criteria = join(' ',$ccc);
            $token = $row['alert_id'] . '-' . $row['registrationtoken'];
            $action = '<form action="/alert/" method="post"><input type="hidden" name="t" value="'.$token.'">';
            if (!$row['confirmed']) {
                $action .= '<input type="submit" name="action" value="Confirm">';
            } elseif ($row['deleted']==2) {
                $action .= '<input type="submit" name="action" value="Resume">';
            } else {
                $action .= '<input type="submit" name="action" value="Suspend"> <input type="submit" name="action" value="Delete">';
            }
            $action .= '</form>';
            $out .= '<tr><td>' . $criteria . '</td><td align="center">' . $action . '</td></tr>';
            if (!$current) {
                $out .= '<tr><td colspan="2"><small>&nbsp;&mdash; <em>not a current member of any body covered by TheyWorkForYou</em></small></td></tr>';
            }
        }
        if ($out) {
            print '<table cellpadding="3" cellspacing="0"><tr><th>Criteria</th><th>Action</th></tr>' . $out . '</table>';
        } else {
            print '<p>You currently have no email alerts set up. You can create alerts <a href="/alert/">here</a>.</p>';
        }
    }

}

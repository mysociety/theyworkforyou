<?php

namespace MySociety\TheyWorkForYou\Utility;

/**
 * Alert Utilities
 *
 * Utility functions related to alerts
 */

class Alert
{

    public static function confirmationAdvert($details) {
        global $THEUSER;

        $adverts = array(
            #array('hfymp0', '<h2 style="border-top: dotted 1px #999999; padding-top:0.5em; margin-bottom:0">Get email from your MP in the future</h2> <p style="font-size:120%;margin-top:0;">and have a chance to discuss what they say in a public forum [button]Sign up to HearFromYourMP[/button]'),
            array('hfymp1', '<h2 style="border-top: dotted 1px #999999; padding-top:0.5em; margin-bottom:0">Get email from your MP in the future</h2> <p style="font-size:120%;margin-top:0;">and have a chance to discuss what they say in a public forum [form]Sign up to HearFromYourMP[/form]'),
            #array('fms0', '<p>Got a local problem like potholes or flytipping in your street?<br><a href="http://www.fixmystreet.com/">Report it at FixMyStreet</a></p>'),
            #array('gny0', '<h2>Are you a member of a local group&hellip;</h2> <p>&hellip;which uses the internet to coordinate itself, such as a neighbourhood watch? If so, please help the charity that runs TheyWorkForYou by <a href="http://www.groupsnearyou.com/add/about/">adding some information about it</a> to our new site, GroupsNearYou.</p>'),
            #array('twfy_alerts0', ''),
        );

        if ($THEUSER->isloggedin()) {
            $advert_shown = crosssell_display_advert('twfy', $details['email'], $THEUSER->firstname() . ' ' . $THEUSER->lastname(), $THEUSER->postcode(), $adverts);
        } else {
            $advert_shown = crosssell_display_advert('twfy', $details['email'], '', '', $adverts);
        }
        if ($advert_shown == 'other-twfy-alert-type') {
            if ($details['pid']) {
                $advert_shown = 'twfy-alert-word';
    ?>
<p>Did you know that TheyWorkForYou can also email you when a certain word or phrases is mentioned in parliament? For example, it could mail you when your town is mentioned, or an issue you care about. Don't rely on the newspapers to keep you informed about your interests - find out what's happening straight from the horse's mouth.
<a href="/alert/"><strong>Sign up for an email alert</strong></a></p>
    <?php       } else {
                $advert_shown = 'twfy-alert-person';
    ?>
<p>Did you know that TheyWorkForYou can also email you when a certain MP or Lord contributes in parliament? Don't rely on the newspapers to keep you informed about someone you're interested in - find out what's happening straight from the horse's mouth.
<a href="/alert/"><strong>Sign up for an email alert</strong></a></p>
    <?php       }
        }
        return $advert_shown;
    }


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
                    $MEMBER = new \MySociety\TheyWorkForYou\Member(array('person_id'=>$m[1]));
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
            print '<p>You currently have no email alerts set up.</p>';
        }
    }

}

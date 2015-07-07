<?php
/**
 * User Class
 *
 * @package TheyWorkForYou
 */

namespace MySociety\TheyWorkForYou;

/**
 * User
 */

class User {
    public function getMP($mp_url, $cons_type, $mp_house) {
        $mp_url = new \URL($mp_url);
        $mp_data = array();
        global $THEUSER;

        if ($THEUSER->has_postcode()) {
            // User is logged in and has a postcode, or not logged in with a cookied postcode.

            // (We don't allow the user to search for a postcode if they
            // already have one set in their prefs.)

            // this is for people who have e.g. an English postcode looking at the
            // Scottish homepage
            try {
                $constituencies = postcode_to_constituencies($THEUSER->postcode());
                if ( isset($constituencies[$cons_type]) ) {
                    $constituency = $constituencies[$cons_type];
                    $MEMBER = new Member(array('constituency'=>$constituency, 'house'=> $mp_house));
                }
            } catch ( MemberException $e ) {
                return $mp_data;
            }

            if (isset($MEMBER) && $MEMBER->valid) {
                $mp_data['name'] = $MEMBER->full_name();
                $mp_data['party'] = $MEMBER->party();
                $mp_data['constituency'] = $MEMBER->constituency();
                $left_house = $MEMBER->left_house();
                $mp_data['former'] = '';
                if ($left_house[$mp_house]['date'] != '9999-12-31') {
                    $mp_data['former'] = 'former';
                }
                $mp_data['postcode'] = $THEUSER->postcode();
                $mp_data['mp_url'] = $mp_url->generate();
                $mp_data['change_url'] = $this->getPostCodeChangeURL();

                list($image, ) = Utility\Member::findMemberImage($MEMBER->person_id(), true, true);
                $mp_data['image'] = $image;
            }
        }

        return $mp_data;
    }

    private function getPostCodeChangeURL() {
        global $THEUSER;
        $CHANGEURL = new \URL('userchangepc');
        if ($THEUSER->isloggedin()) {
            $CHANGEURL = new \URL('useredit');
        }

        return $CHANGEURL->generate();
    }


}

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
    public function getRep($cons_type, $mp_house) {
        global $THEUSER;
        if ( !$THEUSER->has_postcode() ) {
            return array();
        }

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
            return array();
        }

        if (isset($MEMBER) && $MEMBER->valid) {
            return $this->constructMPData($MEMBER, $THEUSER, $mp_house);
        }

        return array();
    }

    private function constructMPData($member, $user, $mp_house) {
        $mp_data = array();
        $mp_data['name'] = $member->full_name();
        $mp_data['party'] = $member->party();
        $mp_data['constituency'] = $member->constituency();
        $left_house = $member->left_house();
        $mp_data['former'] = '';
        if ($left_house[$mp_house]['date'] != '9999-12-31') {
            $mp_data['former'] = 'former';
        }
        $mp_data['postcode'] = $user->postcode();
        $mp_data['mp_url'] = $member->url();
        $mp_data['change_url'] = $this->getPostCodeChangeURL();

        $image = $member->image();
        $mp_data['image'] = $image['url'];

        return $mp_data;
    }

    public function getRegionalReps($cons_type, $mp_house) {
        global $THEUSER;

        $mreg = array();
        if ($THEUSER->isloggedin() && $THEUSER->postcode() != '' || $THEUSER->postcode_is_set()) {
            $reps = \MySociety\TheyWorkForYou\Member::getRegionalList($THEUSER->postcode, $mp_house, $cons_type);
            foreach ( $reps as $rep ) {
                $member = new \MySociety\TheyWorkForYou\Member(array('person_id' => $rep['person_id']));
                $mreg[$rep['person_id']] = $this->constructMPData($member, $THEUSER, $mp_house);
            }
        }

        return $mreg;
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

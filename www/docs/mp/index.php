<?php

/*
 * index.php
 *
 * For displaying info about a person for a postcode or constituency.
 *
 * This page accepts either 'm' (a member_id), 'pid' (a person_id),
 * 'c' (a postcode or constituency), or 'n' (a name).
 *
 * First, we check to see if a person_id's been submitted.
 * If so, we display that person.
 *
 * Else, we check to see if a member_id's been submitted.
 * If so, we display that person.
 *
 * Otherwise, we then check to see if a postcode's been submitted.
 * If it's valid we put it in a cookie.
 *
 * If no postcode, we check to see if a constituency's been submitted.
 *
 * If neither has been submitted, we see if either the user is logged in
 * and has a postcode set or the user has a cookied postcode from a previous
 * search.
 *
 * If we have a valid constituency after all this, we display its MP.
 *
 * Either way, we print the forms.
 */

// Disable the old PAGE class.
$new_style_template = TRUE;

// Include all the things this page needs.
include_once '../../includes/easyparliament/init.php';
include_once INCLUDESPATH . 'postcode.inc';
include_once INCLUDESPATH . 'technorati.php';
include_once '../api/api_getGeometry.php';
include_once '../api/api_getConstituencies.php';

// Ensure that page type is set
if (get_http_var('pagetype')) {
    $pagetype = get_http_var('pagetype');
} else {
    $pagetype = 'profile';
}

// Set the PID, name and constituency.
$pid = get_http_var('pid') != '' ? get_http_var('pid') : get_http_var('p');
$name = strtolower(str_replace('_', ' ', get_http_var('n')));
$constituency = strtolower(str_replace('_', ' ', get_http_var('c')));

// Fix for names with non-ASCII characters
if ($name == 'sion simon') $name = 'si\xf4n simon';
if ($name == 'sian james') $name = 'si\xe2n james';
if ($name == 'lembit opik') $name = 'lembit \xf6pik';
if ($name == 'bairbre de brun') $name = 'bairbre de br\xfan';
if ($name == 'daithi mckay') $name = 'daith\xed mckay';
if ($name == 'caral ni chuilin') $name = 'car\xe1l n\xed chuil\xedn';
if ($name == 'caledon du pre') $name = 'caledon du pr\xe9';
if ($name == 'sean etchingham') $name = 'se\xe1n etchingham';
if ($name == 'john tinne') $name = 'john tinn\xe9';
if ($name == 'renee short') $name = 'ren\xe9e short';

// Fix for common misspellings, name changes etc
$name_fix = array(
    'a j beith' => 'alan beith',
    'micky brady' => 'mickey brady',
    'daniel rogerson' => 'dan rogerson',
    'andrew slaughter' => 'andy slaughter',
    'robert wilson' => array('rob wilson', 'reading east'),
    'james mcgovern' => 'jim mcgovern',
    'patrick mcfadden' => 'pat mcfadden',
    'chris leslie' => 'christopher leslie',
    'joseph meale' => 'alan meale',
    'james sheridan' => 'jim sheridan',
    'chinyelu onwurah' => 'chi onwurah',
    'steve rotherham' => 'steve rotheram',
    'michael weatherley' => 'mike weatherley',
    'louise bagshawe' => 'louise mensch',
    'andrew sawford' => 'andy sawford',
);

if (array_key_exists($name, $name_fix)) {
    if (is_array($name_fix[$name])) {
        if ($constituency == $name_fix[$name][1]) {
            $name = $name_fix[$name][0];
        }
    } else {
        $name = $name_fix[$name];
    }
}

// Fixes for Ynys Mon, and a Unicode URL
if ($constituency == 'ynys mon') $constituency = "ynys m\xf4n";
if (preg_match("#^ynys m\xc3\xb4n#i", $constituency)) {
    $constituency = "ynys m\xf4n";
}

// If this is a request for recent appearances, redirect to search results
if (get_http_var('recent')) {
    if ($THEUSER->postcode_is_set() && !$pid) {
        $MEMBER = new MySociety\TheyWorkForYou\Member(array('postcode' => $THEUSER->postcode(), 'house' => HOUSE_TYPE_COMMONS));
        if ($MEMBER->person_id()) {
            $pid = $MEMBER->person_id();
        }
    }
    if ($pid) {
        $URL = new URL('search');
        $URL->insert( array('pid'=>$pid, 'pop'=>1) );
        header('Location: http://' . DOMAIN . $URL->generate('none'));
        exit;
    }
}

/////////////////////////////////////////////////////////
// DETERMINE TYPE OF REPRESENTITIVE
if (get_http_var('peer')) $this_page = 'peer';
elseif (get_http_var('royal')) $this_page = 'royal';
elseif (get_http_var('mla')) $this_page = 'mla';
elseif (get_http_var('msp')) $this_page = 'msp';
else $this_page = 'mp';

// Try all this, because things may go wrong and we can catch it all at the end.
try {

    /////////////////////////////////////////////////////////
    // CANONICAL PERSON ID
    if (is_numeric($pid))
    {

        // Normal, plain, displaying an MP by person ID.
        $MEMBER = new MySociety\TheyWorkForYou\Member(array('person_id' => $pid));

        // If the member ID doesn't exist then the object won't have it set.
        if ($MEMBER->member_id)
        {
            // Ensure that we're actually at the current, correct and canonical URL for the person. If not, redirect.
            // No need to worry about other URL syntax forms for vote pages, they shouldn't happen.
            switch ($pagetype) {

                case 'votes':

                    if (str_replace('/mp/', '/' . $this_page . '/', get_http_var('url')) !== urldecode($MEMBER->url(FALSE)) . '/votes')
                    {
                        exit ('redirecting!');
                        member_redirect($MEMBER, 301, 'votes');
                    }

                    break;

                case 'profile':
                default:

                    if (str_replace('/mp/', '/' . $this_page . '/', get_http_var('url')) !== urldecode($MEMBER->url(FALSE)))
                    {
                        member_redirect($MEMBER);
                    }

                    break;

            }
        }
        else
        {
            throw new MySociety\TheyWorkForYou\MemberException('Sorry, that ID number wasn\'t recognised.');
        }
    }

    /////////////////////////////////////////////////////////
    // MEMBER ID
    elseif (is_numeric(get_http_var('m')))
    {
        // Got a member id, redirect to the canonical MP page, with a person id.
        $MEMBER = new MySociety\TheyWorkForYou\Member(array('member_id' => get_http_var('m')));
        member_redirect($MEMBER);

    }

    /////////////////////////////////////////////////////////
    // CHECK SUBMITTED POSTCODE

    elseif (get_http_var('pc') != '')
    {
        // User has submitted a postcode, so we want to display that.
        $pc = get_http_var('pc');
        $pc = preg_replace('#[^a-z0-9]#i', '', $pc);
        if (validate_postcode($pc)) {
            twfy_debug ('MP', "MP lookup by postcode");
            $constituency = strtolower(postcode_to_constituency($pc));
            if ($constituency == "connection_timed_out") {
                throw new MySociety\TheyWorkForYou\MemberException('Sorry, we couldn&rsquo;t check your postcode right now, as our postcode lookup server is under quite a lot of load.');
            } elseif ($constituency == "") {
                twfy_debug ('MP', "Can't display an MP, as submitted postcode didn't match a constituency");
                throw new MySociety\TheyWorkForYou\MemberException('Sorry, '._htmlentities($pc) .' isn&rsquo;t a known postcode');
            } else {
                // Redirect to the canonical MP page, with a person id.
                $MEMBER = new MySociety\TheyWorkForYou\Member(array('constituency' => $constituency, 'house' => HOUSE_TYPE_COMMONS));
                if ($MEMBER->person_id()) {
                    // This will cookie the postcode.
                    $THEUSER->set_postcode_cookie($pc);
                }
                member_redirect($MEMBER, 302);
            }
        } else {
            twfy_debug ('MP', "Can't display an MP because the submitted postcode wasn't of a valid form.");
            throw new MySociety\TheyWorkForYou\MemberException('Sorry, '._htmlentities($pc) .' isn&rsquo;t a valid postcode');
        }

    }

    /////////////////////////////////////////////////////////
    // DOES THE USER HAVE A POSTCODE ALREADY SET (SCOTLAND)?
    // (Either in their logged-in details or in a cookie from a previous search.)

    elseif ($this_page == 'msp' && $THEUSER->postcode_is_set() && $name == '' && $constituency == '')
    {
        $this_page = 'yourmsp';
        if (postcode_is_scottish($THEUSER->postcode())) {
            regional_list($THEUSER->postcode(), 'SPC', 'msp');
            exit;
        } else {
            throw new MySociety\TheyWorkForYou\MemberException('Your set postcode is not in Scotland.');
        }
    }

    /////////////////////////////////////////////////////////
    // DOES THE USER HAVE A POSTCODE ALREADY SET (NI)?
    // (Either in their logged-in details or in a cookie from a previous search.)
    elseif ($this_page == 'mla' && $THEUSER->postcode_is_set() && $name == '' && $constituency == '')
    {
        $this_page = 'yourmla';
        if (postcode_is_ni($THEUSER->postcode())) {
            regional_list($THEUSER->postcode(), 'NIE', 'mla');
            exit;
        } else {
            throw new MySociety\TheyWorkForYou\MemberException('Your set postcode is not in Northern Ireland.');
        }
    }

    /////////////////////////////////////////////////////////
    // DOES THE USER HAVE A POSTCODE ALREADY SET (WESTMINISTER)?
    // (Either in their logged-in details or in a cookie from a previous search.)
    elseif ($THEUSER->postcode_is_set() && $name == '' && $constituency == '')
    {
        $MEMBER = new MySociety\TheyWorkForYou\Member(array('postcode' => $THEUSER->postcode(), 'house' => HOUSE_TYPE_COMMONS));
        member_redirect($MEMBER, 302);
    }

    /////////////////////////////////////////////////////////
    // NAME AND CONSTITUENCY
    elseif ($name && $constituency)
    {
        $MEMBER = new MySociety\TheyWorkForYou\Member(array('name'=>$name, 'constituency'=>$constituency));

        // If this person is not unique in name, don't redirect and instead wait to show list
        if (!is_array($MEMBER->person_id()))
        {
            twfy_debug ('MP', 'Redirecting for member found by name and constituency');
            member_redirect($MEMBER);
        }
    }

    /////////////////////////////////////////////////////////
    // NAME ONLY
    elseif ($name)
    {
        $MEMBER = new MySociety\TheyWorkForYou\Member(array('name' => $name));

        // Edge case for Elizabeth II
        if ($name !== 'elizabeth the second') {

            // Only attempt further detection if this isn't the Queen.

            if (preg_match('#^(mr|mrs|ms)#', $name)) {
                member_redirect($MEMBER);
            }

            // If this person is not unique in name, don't redirect and instead wait to show list
            if (!is_array($MEMBER->person_id()))
            {
                twfy_debug ('MP', 'Redirecting for MP found by name only');
                member_redirect($MEMBER);
            }

        }
    }

    /////////////////////////////////////////////////////////
    // CONSTITUENCY ONLY
    elseif ($constituency)
    {
        $MEMBER = new MySociety\TheyWorkForYou\Member(array('constituency' => $constituency, 'house' => HOUSE_TYPE_COMMONS));
        member_redirect($MEMBER);
    }

    /////////////////////////////////////////////////////////
    // UNABLE TO IDENTIFY MP
    else
    {
        // No postcode, member_id or person_id to use.
        twfy_debug ('MP', "We don't have any way of telling what MP to display");
        throw new MySociety\TheyWorkForYou\MemberException('Sorry, but we can&rsquo;t tell which representative to display.');
    }

    /////////////////////////////////////////////////////////
    // DISPLAY A LIST OF REPRESENTATIVES

    if (!DEVSITE) {
        header('Cache-Control: max-age=900');
    }

    if (isset($MEMBER) && is_array($MEMBER->person_id())) {

        $cs = $MEMBER->constituency();
        $c = 0;
        foreach ($MEMBER->person_id() as $id) {
            $data['mps'][] = array(
                    'url'  => WEBPATH . 'mp/?pid='.$id,
                    'name' => ucwords(strtolower($name)) . ', ' . $cs[$c++]
                );
        }

        $MPSURL = new \URL('mps');

        $data['all_mps_url'] = $MPSURL->generate();

        // Send the output for rendering
        MySociety\TheyWorkForYou\Renderer::output('mp/list', $data);

    /////////////////////////////////////////////////////////
    // DISPLAY A REPRESENTATIVE

    } elseif (isset($MEMBER) && $MEMBER->person_id()) {

        twfy_debug_timestamp("before load_extra_info");
        $MEMBER->load_extra_info(true);
        twfy_debug_timestamp("after load_extra_info");

        // Basic name, title and description
        $member_name = ucfirst($MEMBER->full_name());
        $title = $member_name;
        $desc = "Read $member_name's contributions to Parliament, including speeches and questions";

        // Enhance description if this is a current member
        if ($MEMBER->current_member_anywhere())
            $desc .= ', investigate their voting record, and get email alerts on their activity';

        // Enhance title if this is a member of the Commons
        if ($MEMBER->house(HOUSE_TYPE_COMMONS)) {
            if (!$MEMBER->current_member(1)) {
                $title .= ', former';
            }
            $title .= ' MP';
            if ($MEMBER->constituency()) $title .= ', ' . $MEMBER->constituency();
        }

        // Enhance title if this is a member of NIA
        if ($MEMBER->house(HOUSE_TYPE_NI)) {
            if ($MEMBER->house(HOUSE_TYPE_COMMONS) || $MEMBER->house(HOUSE_TYPE_LORDS)) {
                $desc = str_replace('Parliament', 'Parliament and the Northern Ireland Assembly', $desc);
            } else {
                $desc = str_replace('Parliament', 'the Northern Ireland Assembly', $desc);
            }
            if (!$MEMBER->current_member(HOUSE_TYPE_NI)) {
                $title .= ', former';
            }
            $title .= ' MLA';
            if ($MEMBER->constituency()) $title .= ', ' . $MEMBER->constituency();
        }

        // Enhance title if this is a member of Scottish Parliament
        if ($MEMBER->house(HOUSE_TYPE_SCOTLAND)) {
            if ($MEMBER->house(HOUSE_TYPE_COMMONS) || $MEMBER->house(HOUSE_TYPE_LORDS)) {
                $desc = str_replace('Parliament', 'the UK and Scottish Parliaments', $desc);
            } else {
                $desc = str_replace('Parliament', 'the Scottish Parliament', $desc);
            }
            $desc = str_replace(', and get email alerts on their activity', '', $desc);
            if (!$MEMBER->current_member(HOUSE_TYPE_SCOTLAND)) {
                $title .= ', former';
            }
            $title .= ' MSP, '.$MEMBER->constituency();
        }

        // Position if this is a member of the Commons
        if ($MEMBER->house(HOUSE_TYPE_COMMONS)) {
            if (!$MEMBER->current_member(1)) {
                $position_former = 'Former MP';
                if ($MEMBER->constituency()) $position_former .= ', ' . $MEMBER->constituency();
            } else {
                $position_current = 'MP';
                if ($MEMBER->constituency()) $position_current .= ', ' . $MEMBER->constituency();
            }
        }

        // Position if this is a member of NIA
        if ($MEMBER->house(HOUSE_TYPE_NI)) {
            if (!$MEMBER->current_member(HOUSE_TYPE_NI)) {
                $position_former = 'Former MLA';
                if ($MEMBER->constituency()) $position_former .= ', ' . $MEMBER->constituency();
            } else {
                $position_current = 'MLA';
                if ($MEMBER->constituency()) $position_current .= ', ' . $MEMBER->constituency();
            }
        }

        // Position if this is a member of Scottish Parliament
        if ($MEMBER->house(HOUSE_TYPE_SCOTLAND)) {
            if (!$MEMBER->current_member(HOUSE_TYPE_SCOTLAND)) {
                $position_former = 'Former MSP';
            } else {
                $position_current = 'MSP, '.$MEMBER->constituency();
            }
        }

        $current_offices = $MEMBER->offices('current', TRUE);
        $former_offices = $MEMBER->offices('previous', TRUE);

        // If this person has current named *priority* offices, they override the defaults

        if (count($current_offices) > 0){
            $position_current = implode('<br>', $current_offices);
        }

        // If this person has former named *priority* offices, they override the defaults
        if (count($former_offices) > 0){
            $position_former = implode('<br>', $former_offices);
        }

        // Set page metadata
        $DATA->set_page_metadata($this_page, 'title', $title);
        $DATA->set_page_metadata($this_page, 'meta_description', $desc);

        // Build the RSS link and add it to page data.
        $feedurl = $DATA->page_metadata('mp_rss', 'url') . $MEMBER->person_id() . '.rdf';
        if (file_exists(BASEDIR . '/' . $feedurl))
            $DATA->set_page_metadata($this_page, 'rss', $feedurl);

        // Prepare data for the template
        $data['full_name'] = $MEMBER->full_name();
        $data['person_id'] = $MEMBER->person_id();
        $data['member_id'] = $MEMBER->member_id();

        if (isset($position_current)) {
            $data['current_position'] = $position_current;
        } else {
            $data['current_position'] = NULL;
        }

        if (isset($position_former)) {
            $data['former_position'] = $position_former;
        } else {
            $data['former_position'] = NULL;
        }

        $data['constituency'] = $MEMBER->constituency();
        $data['party'] = $MEMBER->party_text();
        $data['party_short'] = $MEMBER->party();
        $data['current_member_anywhere'] = $MEMBER->current_member_anywhere();
        $data['current_member'] = $MEMBER->current_member();
        $data['the_users_mp'] = $MEMBER->the_users_mp();
        $data['user_postcode'] = $THEUSER->postcode;
        $data['houses'] = $MEMBER->houses();
        $data['member_url'] = $MEMBER->url();
        // If there's photo attribution information, copy it into data
        foreach (['photo_attribution_text', 'photo_attribution_link'] as $key) {
            if (isset($MEMBER->extra_info[$key])) {
                $data[$key] = $MEMBER->extra_info[$key];
            }
        }
        $data['image'] = $MEMBER->image();
        $data['member_summary'] = person_summary_description($MEMBER);
        $data['enter_leave'] = $MEMBER->getEnterLeaveStrings();
        $data['other_parties'] = $MEMBER->getOtherPartiesString();
        $data['other_constituencies'] = $MEMBER->getOtherConstituenciesString();
        $data['rebellion_rate'] = person_rebellion_rate($MEMBER);
        $data['recent_appearances'] = person_recent_appearances($MEMBER);
        $data['useful_links'] = person_useful_links($MEMBER);
        $data['topics_of_interest'] = person_topics($MEMBER);
        $data['current_offices'] = $MEMBER->offices('current');
        $data['previous_offices'] = $MEMBER->offices('previous');
        $data['register_interests'] = person_register_interests($MEMBER, $MEMBER->extra_info);

        # People who are or were MPs and Lords potentially have voting records, except Sinn Fein MPs
        $data['has_voting_record'] = ( ($MEMBER->house(HOUSE_TYPE_COMMONS) && $MEMBER->party() != 'SF') || $MEMBER->house(HOUSE_TYPE_LORDS) );
        # Everyone who is currently somewhere has email alert signup, apart from current Sinn Fein MPs who are not MLAs
        $data['has_email_alerts'] = ($MEMBER->current_member_anywhere() && !($MEMBER->current_member(HOUSE_TYPE_COMMONS) && $MEMBER->party() == 'SF' && !$MEMBER->current_member(HOUSE_TYPE_NI)));
        # Everyone has recent appearances apart from Sinn Fein MPs who were never MLAs
        $data['has_recent_appearances'] = !($MEMBER->house(HOUSE_TYPE_COMMONS) && $MEMBER->party() == 'SF' && !$MEMBER->house(HOUSE_TYPE_NI));
        # XXX This is current behaviour, but should probably now just be any recent MP
        $data['has_expenses'] = isset($MEMBER->extra_info['expenses2004_col1']) || isset($MEMBER->extra_info['expenses2006_col1']) || isset($MEMBER->extra_info['expenses2007_col1']) || isset($MEMBER->extra_info['expenses2008_col1']);

        // Set the expenses URL if we know it
        if (isset($MEMBER->extra_info['expenses_url'])) {
            $data['expenses_url_2004'] = $MEMBER->extra_info['expenses_url'];
        } else {
            $data['expenses_url_2004'] = 'http://mpsallowances.parliament.uk/mpslordsandoffices/hocallowances/allowances%2Dby%2Dmp/';
        }

        $data['constituency_previous_mps'] = constituency_previous_mps($MEMBER);
        $data['constituency_future_mps'] = constituency_future_mps($MEMBER);
        $data['public_bill_committees'] = person_pbc_membership($MEMBER);
        $data['numerology'] = person_numerology($MEMBER);

        $data['this_page'] = $this_page;
        $data['current_assembly'] = 'westminster';
        if ( $this_page == 'msp' || $this_page == 'yourmsp' ) {
            $data['current_assembly'] = 'scotland';
        } else if ( $this_page == 'mla' || $this_page == 'yourmla' ) {
            $data['current_assembly'] = 'ni';
        }


        /*

        $data['member_id'] = $MEMBER->member_id();
        $data['house_disp'] = $MEMBER->house_disp;

        */

        // Do any necessary extra work based on the page type, and send for rendering.
        switch ($pagetype) {

            case 'votes':

                $policiesList = new MySociety\TheyWorkForYou\Policies;

                // Generate voting segments
                $data['key_votes_segments'] = array(
                    array(
                        'key'   => 'social',
                        'title' => 'Social Issues',
                        'votes' => new MySociety\TheyWorkForYou\PolicyPositions(
                            $policiesList->limitToSet('social'), $MEMBER
                        )
                    ),
                    array(
                        'key'   => 'foreign',
                        'title' => 'Foreign Policy and Defence',
                        'votes' => new MySociety\TheyWorkForYou\PolicyPositions(
                            $policiesList->limitToSet('foreignpolicy'), $MEMBER
                        )
                    ),
                    array(
                        'key'   => 'welfare',
                        'title' => 'Welfare and Benefits',
                        'votes' => new MySociety\TheyWorkForYou\PolicyPositions(
                            $policiesList->limitToSet('welfare'), $MEMBER
                        )
                    ),
                    array(
                        'key'   => 'taxation',
                        'title' => 'Taxation and Employment',
                        'votes' => new MySociety\TheyWorkForYou\PolicyPositions(
                            $policiesList->limitToSet('taxation'), $MEMBER
                        )
                    ),
                    array(
                        'key'   => 'business',
                        'title' => 'Business and the Economy',
                        'votes' => new MySociety\TheyWorkForYou\PolicyPositions(
                            $policiesList->limitToSet('business'), $MEMBER
                        )
                    ),
                    array(
                        'key'   => 'health',
                        'title' => 'Health',
                        'votes' => new MySociety\TheyWorkForYou\PolicyPositions(
                            $policiesList->limitToSet('health'), $MEMBER
                        )
                    ),
                    array(
                        'key'   => 'education',
                        'title' => 'Education',
                        'votes' => new MySociety\TheyWorkForYou\PolicyPositions(
                            $policiesList->limitToSet('education'), $MEMBER
                        )
                    ),
                    array(
                        'key'   => 'reform',
                        'title' => 'Constitutional Reform',
                        'votes' => new MySociety\TheyWorkForYou\PolicyPositions(
                            $policiesList->limitToSet('reform'), $MEMBER
                        )
                    ),
                    array(
                        'key'   => 'home',
                        'title' => 'Home Affairs',
                        'votes' => new MySociety\TheyWorkForYou\PolicyPositions(
                            $policiesList->limitToSet('home'), $MEMBER
                        )
                    ),
                    array(
                        'key'   => 'misc',
                        'title' => 'Miscellaneous Topics',
                        'votes' => new MySociety\TheyWorkForYou\PolicyPositions(
                            $policiesList->limitToSet('misc'), $MEMBER
                        )
                    )
                );

                // Send the output for rendering
                MySociety\TheyWorkForYou\Renderer::output('mp/votes', $data);

                break;

            case 'profile':
            default:

                $policiesList = new MySociety\TheyWorkForYou\Policies;
                $policies = $policiesList->limitToSet('summary')->shuffle();

                // Generate limited voting record list
                $data['policyPositions'] = new MySociety\TheyWorkForYou\PolicyPositions($policies, $MEMBER, 6);

                // Send the output for rendering
                MySociety\TheyWorkForYou\Renderer::output('mp/profile', $data);

                break;

        }



    /////////////////////////////////////////////////////////
    // Catch and display when something has gone horribly wrong.

    } else {

        throw new MySociety\TheyWorkForYou\MemberException('You haven\'t provided a way of identifying which representative you want');

    }

} catch (MySociety\TheyWorkForYou\MemberException $e){

    // The message belongs in the output...
    $data['error'] = $e->getMessage();

    // Generate a URL to a full list to try get the user back on track.
    $MPSURL = new \URL('mps');
    $data['all_mps_url'] = $MPSURL->generate();

    // Render it!
    MySociety\TheyWorkForYou\Renderer::output('mp/error', $data);

    // We're done here
    exit();

}

/////////////////////////////////////////////////////////
// SUPPORTING FUNCTIONS

/**
 * Member Redirect
 *
 * Redirect to the canonical page for a member.
 */

function member_redirect (&$MEMBER, $code = 301, $pagetype = NULL) {
    // We come here after creating a MEMBER object by various methods.
    // Now we redirect to the canonical MP page, with a person_id.
    if ($MEMBER->person_id()) {
        $url = $MEMBER->url();
        $params = array();
        foreach ($_GET as $key => $value) {
            if (substr($key, 0, 4) == 'utm_' || $key == 'gclid')
                $params[] = "$key=$value";
        }
        if (count($params))
            $url .= '?' . join('&', $params);
        if ($pagetype !== NULL) {
            $pagetype = '/' . $pagetype;
        } else {
            $pagetype = '';
        }
        header('Location: ' . $url . $pagetype, true, $code );
        exit;
    }
}

/**
 * Person Positions Summary
 *
 * Generate the summary of this person's held positions.
 */

function person_summary_description ($MEMBER) {
    $entered_house = $MEMBER->entered_house();
    $current_member = $MEMBER->current_member();
    $left_house = $MEMBER->left_house();

    if (in_array(HOUSE_TYPE_ROYAL, $MEMBER->houses())) { # Royal short-circuit
        return '<strong>Acceded on ' . $entered_house[HOUSE_TYPE_ROYAL]['date_pretty']
            . '<br>Coronated on 2 June 1953</strong></li>';
    }
    $desc = '';
    foreach ($MEMBER->houses() as $house) {
        if ($house==HOUSE_TYPE_COMMONS && isset($entered_house[HOUSE_TYPE_LORDS]))
            continue; # Same info is printed further down

        if (!$current_member[$house]) $desc .= 'Former ';

        $party = $left_house[$house]['party'];
        $party_br = '';
        if (preg_match('#^(.*?)\s*\((.*?)\)$#', $party, $m)) {
            $party_br = $m[2];
            $party = $m[1];
        }
        if ($party != 'unknown')
            $desc .= _htmlentities($party);
        if ($party == 'Speaker' || $party == 'Deputy Speaker') {
            $desc .= ', and ';
            # XXX: Might go horribly wrong if something odd happens
            if ($party == 'Deputy Speaker') {
                $last = end($MEMBER->other_parties);
                $desc .= $last['from'] . ' ';
            }
        }
        if ($house==HOUSE_TYPE_COMMONS || $house==HOUSE_TYPE_NI || $house==HOUSE_TYPE_SCOTLAND) {
            $desc .= ' ';
            if ($house==HOUSE_TYPE_COMMONS) $desc .= '<abbr title="Member of Parliament">MP</abbr>';
            if ($house==HOUSE_TYPE_NI) $desc .= '<abbr title="Member of the Legislative Assembly">MLA</abbr>';
            if ($house==HOUSE_TYPE_SCOTLAND) $desc .= '<abbr title="Member of the Scottish Parliament">MSP</abbr>';
            if ($party_br) {
                $desc .= " ($party_br)";
            }
            $desc .= ' for ' . $left_house[$house]['constituency'];
        }
        if ($house==HOUSE_TYPE_LORDS && $party != 'Bishop') $desc .= ' Peer';
        $desc .= ', ';
    }
    $desc = preg_replace('#, $#', '', $desc);
    return $desc;
}

/**
 * Person Rebellion Rate
 *
 * How often has this person rebelled against their party?
 *
 * @param \MySociety\TheyWorkForYou\Member $member The member to calculate rebellion rate for.
 *
 * @return string A HTML summary of this person's rebellion rate.
 */

function person_rebellion_rate ($member) {

    // Rebellion string may be empty.
    $rebellion_string = '';

    if (isset($member->extra_info['public_whip_rebellions']) && $member->extra_info['public_whip_rebellions'] != 'n/a') {
        $displayed_stuff = 1;
        $rebels_term = 'rebelled';

        $rebellion_string = '<a href="http://www.publicwhip.org.uk/mp.php?id=uk.org.publicwhip/member/' . $member->member_id() . '#divisions" title="See more details at Public Whip"><strong>' . _htmlentities($member->extra_info['public_whip_rebel_description']) . ' ' . $rebels_term . '</strong></a> against their party';

        if (isset($member->extra_info['public_whip_rebelrank'])) {
            if ($member->extra_info['public_whip_data_date'] == 'complete') {
                $rebellion_string .= ' in their last parliament';
            } else {
                $rebellion_string .= ' in this parliament';
            }
        }
    }

    return $rebellion_string;

}

function person_recent_appearances($member) {
    global $DATA, $SEARCHENGINE, $this_page;

    $out = array();
    $out['appearances'] = array();

    //$this->block_start(array('id'=>'hansard', 'title'=>$title));
    // This is really far from ideal - I don't really want $PAGE to know
    // anything about HANSARDLIST / DEBATELIST / WRANSLIST.
    // But doing this any other way is going to be a lot more work for little
    // benefit unfortunately.
    twfy_debug_timestamp();

    $person_id= $member->person_id();

    global $memcache;
    if (!$memcache) {
        $memcache = new Memcache;
        $memcache->connect('localhost', 11211);
    }
    //$recent = $memcache->get(OPTION_TWFY_DB_NAME . ':recent_appear:' . $person_id);
    $recent = false;

    if (!$recent) {
	// Initialise the search engine
        $searchstring = "speaker:$person_id";
        $SEARCHENGINE = new \SEARCHENGINE($searchstring);

        $hansard = new MySociety\TheyWorkForYou\Hansard();
        $args = array (
            's' => $searchstring,
            'p' => 1,
            'num' => 3,
            'pop' => 1,
            'o' => 'd',
        );
        $results = $hansard->search($searchstring, $args);
        $recent = serialize($results['rows']);
        $memcache->set(OPTION_TWFY_DB_NAME . ':recent_appear:' . $person_id, $recent, MEMCACHE_COMPRESSED, 3600);
    }
    $out['appearances'] = unserialize($recent);
    twfy_debug_timestamp();

    $MOREURL = new \URL('search');
    $MOREURL->insert( array('pid'=>$person_id, 'pop'=>1) );

    $out['more_href'] = $MOREURL->generate() . '#n4';
    $out['more_text'] = 'More of ' . ucfirst($member->full_name()) . '\'s recent appearances';

    if ($rssurl = $DATA->page_metadata($this_page, 'rss')) {
        // If we set an RSS feed for this page.
        $HELPURL = new \URL('help');
        $out['additional_links'] = '<a href="' . WEBPATH . $rssurl . '" title="XML version of this person\'s recent appearances">RSS feed</a> (<a href="' . $HELPURL->generate() . '#rss" title="An explanation of what RSS feeds are for">?</a>)';
    }

    return $out;

}

function person_useful_links($member) {

    $links = $member->extra_info();

    $out = array();

    if (isset($links['maiden_speech'])) {
        $maiden_speech = fix_gid_from_db($links['maiden_speech']);
        $out[] = array(
                'href' => WEBPATH . 'debate/?id=' . $maiden_speech,
                'text' => 'Maiden speech'
        );
    }

    // BIOGRAPHY.
    global $THEUSER;
    if (isset($links['mp_website'])) {
        $out[] = array(
                'href' => $links['mp_website'],
                'text' => 'Personal website'
        );
    }

    if (isset($links['twitter_username'])) {
        $out[] = array(
                'href' => 'http://twitter.com/' . $links['twitter_username'],
                'text' => 'Twitter feed'
        );
    }

    if (isset($links['sp_url'])) {
        $out[] = array(
                'href' => $links['sp_url'],
                'text' => 'Page on the Scottish Parliament website'
        );
    }

    if (isset($links['guardian_biography'])) {
        $out[] = array(
                'href' => $links['guardian_biography'],
                'text' => 'Guardian profile'
        );
    }
    if (isset($links['wikipedia_url'])) {
        $out[] = array(
                'href' => $links['wikipedia_url'],
                'text' => 'Wikipedia page'
        );
    }

    if (isset($links['bbc_profile_url'])) {
        $out[] = array(
                'href' => $links['bbc_profile_url'],
                'text' => 'BBC News profile'
        );
    }

    if (isset($links['diocese_url'])) {
        $out[] = array(
                'href' => $links['diocese_url'],
                'text' => 'Diocese website'
        );
    }

    if ($member->house(HOUSE_TYPE_COMMONS)) {
        $out[] = array(
                'href' => 'http://www.edms.org.uk/mps/' . $member->person_id(),
                'text' => 'Early Day Motions signed by this MP'
        );
    }

    if (isset($links['journa_list_link'])) {
        $out[] = array(
                'href' => $links['journa_list_link'],
                'text' => 'Newspaper articles written by this MP'
        );
    }

    if (isset($links['guardian_election_results'])) {
        $out[] = array(
                'href' => $links['guardian_election_results'],
                'text' => 'Election results for ' . $member->constituency()
        );
    }

    return $out;
}

function person_topics($member) {
    $out = array();

    $extra_info = $member->extra_info();

    if (isset($extra_info['wrans_departments'])) {
        $subjects = explode(',', $extra_info['wrans_departments']);
        $out = array_merge($out, $subjects);
    }

    if (isset($extra_info['wrans_subjects'])) {
        $subjects = explode(',', $extra_info['wrans_subjects']);
        $out = array_merge($out, $subjects);
    }

    return $out;
}

function constituency_previous_mps($member) {
    if ($member->house(HOUSE_TYPE_COMMONS)) {
        return $member->previous_mps_array();
    } else {
        return array();
    }
}

function constituency_future_mps($member) {
    if ($member->house(HOUSE_TYPE_COMMONS)) {
        return $member->future_mps_array();
    } else {
        return array();
    }
}

function person_pbc_membership($member) {

    $extra_info = $member->extra_info();
    $out = array('info'=>'', 'data'=>array());

    # Public Bill Committees
    if (count($extra_info['pbc'])) {
        if ($member->party() == 'SNP') {
            $out['info'] = 'SNP MPs only attend sittings where the legislation pertains to Scotland.';
        }
        foreach ($extra_info['pbc'] as $bill_id => $arr) {
            $text = '';
            if ($arr['chairman']) {
                $text .= 'Chairman, ';
            }
            $text .= $arr['title'] . ' Committee';
            $out['data'][] = array(
                'href'      => '/pbc/' . $arr['session'] . '/' . urlencode($arr['title']),
                'text'      => $text,
                'attending' => $arr['attending'] . ' out of ' . $arr['outof']
            );
        }
    }

    return $out;
}

function person_numerology($member) {

    $extra_info = $member->extra_info();

    $out = array();

    $since_text = 'in the last year';
    $year_ago = date('Y-m-d', strtotime('now -1 year'));

    # Find latest entered house
    $entered_house = null;
    foreach ($member->entered_house() as $h => $eh) {
        if (!$entered_house || $eh['date'] > $entered_house) $entered_house = $eh['date'];
    }
    if ($entered_house > $year_ago)
        $since_text = 'since joining Parliament';

    $MOREURL = new \URL('search');
    $section = 'section:debates section:whall section:lords section:ni';
    $MOREURL->insert(array('pid'=>$member->person_id(), 's'=>$section, 'pop'=>1));
    if ($member->party() != 'SF') {
        if (display_stats_line('debate_sectionsspoken_inlastyear', 'Has spoken in <a href="' . $MOREURL->generate() . '">', 'debate', '</a> ' . $since_text, '', $extra_info)) {
            $out[] = display_stats_line('debate_sectionsspoken_inlastyear', 'Has spoken in <a href="' . $MOREURL->generate() . '">', 'debate', '</a> ' . $since_text, '', $extra_info);
        }

        $MOREURL->insert(array('pid'=>$member->person_id(), 's'=>'section:wrans', 'pop'=>1));
        // We assume that if they've answered a question, they're a minister
        $minister = 0; $Lminister = false;
        if (isset($extra_info['wrans_answered_inlastyear']) && $extra_info['wrans_answered_inlastyear'] > 0 && $extra_info['wrans_asked_inlastyear'] == 0)
            $minister = 1;
        if (isset($extra_info['Lwrans_answered_inlastyear']) && $extra_info['Lwrans_answered_inlastyear'] > 0 && $extra_info['Lwrans_asked_inlastyear'] == 0)
            $Lminister = true;
        if ($member->party() == 'SPK' || $member->party() == 'CWM' || $member->party() == 'DCWM') {
            $minister = 2;
        }
        if (display_stats_line('wrans_asked_inlastyear', 'Has received answers to <a href="' . $MOREURL->generate() . '">', 'written question', '</a> ' . $since_text, '', $extra_info, $minister, $Lminister)) {
            $out[] = display_stats_line('wrans_asked_inlastyear', 'Has received answers to <a href="' . $MOREURL->generate() . '">', 'written question', '</a> ' . $since_text, '', $extra_info, $minister, $Lminister);
        }
    }

    $wtt_displayed = display_writetothem_numbers(2008, $extra_info);
    if ($wtt_displayed) {
        $out[] = $wtt_displayed;
    }
    if (!$wtt_displayed) {
        $wtt_displayed = display_writetothem_numbers(2007, $extra_info);
        if ($wtt_displayed) {
            $out[] = $wtt_displayed;
        }
        if (!$wtt_displayed) {
            $wtt_displayed = display_writetothem_numbers(2006, $extra_info);
            if ($wtt_displayed) {
                $out[] = $wtt_displayed;
            }
            if (!$wtt_displayed) {
                $wtt_displayed = display_writetothem_numbers(2005, $extra_info);
                if ($wtt_displayed) {
                    $out[] = $wtt_displayed;
                }
            }
        }
    }

    $after_stuff = ' <small>(From Public Whip)</small>';
    if ($member->party() == 'SNP') {
        $after_stuff .= '<br><em>Note SNP MPs do not vote on legislation not affecting Scotland.</em>';
    } elseif ($member->party() == 'SPK' || $member->party() == 'CWM' || $member->party() == 'DCWM') {
        $after_stuff .= '<br><em>Speakers and deputy speakers cannot vote except to break a tie.</em>';
    }
    if ($member->party() != 'SF') {
        $when = 'in this Parliament with this affiliation';
        # Lords have one record per affiliation until they leave (ignoring name changes, sigh)
        if ($member->house_disp == HOUSE_TYPE_LORDS) {
            $when = 'in this House with this affiliation';
        }
        if (display_stats_line('public_whip_division_attendance', 'Has voted in <a href="http://www.publicwhip.org.uk/mp.php?id=uk.org.publicwhip/member/' . $member->member_id() . '&amp;showall=yes#divisions" title="See more details at Public Whip">', 'of vote', '</a> ' . $when, $after_stuff, $extra_info)) {
            $out[] = display_stats_line('public_whip_division_attendance', 'Has voted in <a href="http://www.publicwhip.org.uk/mp.php?id=uk.org.publicwhip/member/' . $member->member_id() . '&amp;showall=yes#divisions" title="See more details at Public Whip">', 'of vote', '</a> ' . $when, $after_stuff, $extra_info);
        }
        /*
        if ($member->chairmens_panel) {
            print '<br><em>Members of the Chairmen\'s Panel act for the Speaker when chairing things such as Public Bill Committees, and as such do not vote on Bills they are involved in chairing.</em>';
        }
        */

        if (display_stats_line('comments_on_speeches', 'People have made <a href="' . WEBPATH . 'comments/recent/?pid='.$member->person_id().'">', 'annotation', "</a> on this MP&rsquo;s speeches", '', $extra_info)) {
            $out[] = display_stats_line('comments_on_speeches', 'People have made <a href="' . WEBPATH . 'comments/recent/?pid='.$member->person_id().'">', 'annotation', "</a> on this MP&rsquo;s speeches", '', $extra_info);
        }
        if (display_stats_line('reading_age', 'This MP\'s speeches, in Hansard, are readable by an average ', '', ' year old, going by the <a href="http://en.wikipedia.org/wiki/Flesch-Kincaid_Readability_Test">Flesch-Kincaid Grade Level</a> score', '', $extra_info)) {
            $out[] = display_stats_line('reading_age', 'This MP\'s speeches, in Hansard, are readable by an average ', '', ' year old, going by the <a href="http://en.wikipedia.org/wiki/Flesch-Kincaid_Readability_Test">Flesch-Kincaid Grade Level</a> score', '', $extra_info);
        }
    }

    if (isset($extra_info['number_of_alerts'])) {

        $current_member = $member->current_member();

        $line = '<strong>' . _htmlentities($extra_info['number_of_alerts']) . '</strong> ' . ($extra_info['number_of_alerts']==1?'person is':'people are') . ' tracking ';
        if ($member->house_disp == HOUSE_TYPE_COMMONS) $line .= 'this MP';
        elseif ($member->house_disp == HOUSE_TYPE_LORDS) $line .= 'this peer';
        elseif ($member->house_disp == HOUSE_TYPE_NI) $line .= 'this MLA';
        elseif ($member->house_disp == HOUSE_TYPE_SCOTLAND) $line .= 'this MSP';
        elseif ($member->house_disp == HOUSE_TYPE_ROYAL) $line .= $member->full_name();
        if ($current_member[HOUSE_TYPE_ROYAL] || $current_member[HOUSE_TYPE_LORDS] || $current_member[HOUSE_TYPE_NI] || ($current_member[HOUSE_TYPE_COMMONS] && $member->party() != 'SF') || $current_member[HOUSE_TYPE_SCOTLAND]) {
            $line .= ' &mdash; <a href="' . WEBPATH . 'alert/?pid='.$member->person_id().'">email me updates on '. $member->full_name(). '&rsquo;s activity</a>';
        }

        $out[] = $line;
    }

    if ($member->party() != 'SF') {
        if (display_stats_line('three_word_alliterations', 'Has used three-word alliterative phrases (e.g. "she sells seashells") ', 'time', ' in debates', ' <small>(<a href="' . WEBPATH . 'help/#numbers">Why is this here?</a>)</small>', $extra_info)) {
            $line = display_stats_line('three_word_alliterations', 'Has used three-word alliterative phrases (e.g. "she sells seashells") ', 'time', ' in debates', ' <small>(<a href="' . WEBPATH . 'help/#numbers">Why is this here?</a>)</small>', $extra_info);
            if (isset($extra_info['three_word_alliteration_content'])) {
                $line .= "\n<!-- " . $extra_info['three_word_alliteration_content'] . " -->\n";
            }
            $out[] = $line;
        }
    }

    return $out;

}

function display_stats_line($category, $blurb, $type, $inwhat, $afterstuff, $extra_info, $minister = false, $Lminister = false) {
    $return = false;
    if (isset($extra_info[$category]))
        $return = display_stats_line_house(HOUSE_TYPE_COMMONS, $category, $blurb, $type, $inwhat, $extra_info, $minister, $afterstuff);
    if (isset($extra_info["L$category"]))
        $return = display_stats_line_house(HOUSE_TYPE_LORDS, "L$category", $blurb, $type, $inwhat, $extra_info, $Lminister, $afterstuff);
    return $return;
}

function display_stats_line_house($house, $category, $blurb, $type, $inwhat, $extra_info, $minister, $afterstuff) {
    if ($category == 'wrans_asked_inlastyear' || $category == 'debate_sectionsspoken_inlastyear' || $category =='comments_on_speeches' ||
        $category == 'Lwrans_asked_inlastyear' || $category == 'Ldebate_sectionsspoken_inlastyear' || $category =='Lcomments_on_speeches') {
        if ($extra_info[$category]==0) {
            $blurb = preg_replace('#<a.*?>#', '', $blurb);
            $inwhat = preg_replace('#<\/a>#', '', $inwhat);
        }
    }
    if ($house==HOUSE_TYPE_LORDS) $inwhat = str_replace('MP', 'Lord', $inwhat);
    $line = $blurb;
    $line .= '<strong>' . $extra_info[$category];
    if ($type) $line .= ' ' . make_plural($type, $extra_info[$category]);
    $line .= '</strong>';
    $line .= $inwhat;
    if ($minister===2) {
        $line .= ' &#8212; Speakers/ deputy speakers do not ask written questions';
    } elseif ($minister)
        $line .= ' &#8212; Ministers do not ask written questions';
    else {
        $type = ($house==HOUSE_TYPE_COMMONS?'MP':($house==HOUSE_TYPE_LORDS?'Lord':'MLA'));
        if (!get_http_var('rem') && isset($extra_info[$category . '_quintile'])) {
            $line .= ' &#8212; ';
            $q = $extra_info[$category . '_quintile'];
            if ($q == 0) {
                $line .= 'well above average';
            } elseif ($q == 1) {
                $line .= 'above average';
            } elseif ($q == 2) {
                $line .= 'average';
            } elseif ($q == 3) {
                $line .= 'below average';
            } elseif ($q == 4) {
                $line .= 'well below average';
            } else {
                $line .= '[Impossible quintile!]';
            }
            $line .= ' amongst ';
            $line .= $type . 's';
        } elseif (!get_http_var('rem') && isset($extra_info[$category . '_rank'])) {
            $line .= ' &#8212; ';
            #if (isset($extra_info[$category . '_rank_joint']))
            #   print 'joint ';
            $line .= make_ranking($extra_info[$category . '_rank']) . ' out of ' . $extra_info[$category . '_rank_outof'];
            $line .= ' ' . $type . 's';
        }
    }
    $line .= ".$afterstuff";
    return $line;
}

function display_writetothem_numbers($year, $extra_info) {
    if (isset($extra_info["writetothem_responsiveness_notes_$year"])) {
        return '<li>Responsiveness to messages sent via <a href="http://www.writetothem.com/stats/' . $year . '/mps">WriteToThem.com</a> in ' . $year . ': ' . $extra_info["writetothem_responsiveness_notes_$year"] . '.</li>';
    } elseif (isset($extra_info["writetothem_responsiveness_mean_$year"])) {
        $mean = $extra_info["writetothem_responsiveness_mean_$year"];

        $a = $extra_info["writetothem_responsiveness_fuzzy_response_description_$year"];
        if ($a == 'very low') $a = 'a very low';
        if ($a == 'low') $a = 'a low';
        if ($a == 'medium') $a = 'a medium';
        if ($a == 'high') $a = 'a high';
        if ($a == 'very high') $a = 'a very high';
        $extra_info["writetothem_responsiveness_fuzzy_response_description_$year"] = $a;

        return display_stats_line("writetothem_responsiveness_fuzzy_response_description_$year", 'Replied within 2 or 3 weeks to <a href="http://www.writetothem.com/stats/'.$year.'/mps" title="From WriteToThem.com">', "", "</a> <!-- Mean: " . $mean . " --> number of messages sent via WriteToThem.com during ".$year.", according to constituents", "", $extra_info);
    }

}

function person_register_interests($member, $extra_info) {
    if (!isset($extra_info['register_member_interests_html'])) {
        return;
    }

    $reg = array( 'date' => '', 'data' => '<p>Nil</p>' );
    if (isset($extra_info['register_member_interests_date'])) {
        $reg['date'] = format_date($extra_info['register_member_interests_date'], SHORTDATEFORMAT);
    }
    if ($extra_info['register_member_interests_html'] != '') {
        $reg['data'] = $extra_info['register_member_interests_html'];
    }
    return $reg;
}

function regional_list($pc, $area_type, $rep_type) {
    $constituencies = postcode_to_constituencies($pc);
    if ($constituencies == 'CONNECTION_TIMED_OUT') {
        throw new MySociety\TheyWorkForYou\MemberException('Sorry, we couldn\'t check your postcode right now, as our postcode lookup server is under quite a lot of load.');
    } elseif (!$constituencies) {
        throw new MySociety\TheyWorkForYou\MemberException('Sorry, ' . htmlentities($pc) . ' isn\'t a known postcode');
    } elseif (!isset($constituencies[$area_type])) {
        throw new MySociety\TheyWorkForYou\MemberException(htmlentities($pc) . ' does not appear to be a valid postcode');
    }
    global $PAGE;
    $a = array_values($constituencies);
    $db = new ParlDB;
    $q = $db->query("SELECT person_id, first_name, last_name, constituency, house FROM member
        WHERE constituency IN ('" . join("','", $a) . "')
        AND left_reason = 'still_in_office' AND house in (" . HOUSE_TYPE_NI . "," . HOUSE_TYPE_SCOTLAND . ")");
    $current = true;
    if (!$q->rows()) {
        # XXX No results implies dissolution, fix for 2011.
        $current = false;
        $q = $db->query("SELECT person_id, first_name, last_name, constituency, house FROM member
            WHERE constituency IN ('" . join("','", $a) . "')
            AND ( (house=" . HOUSE_TYPE_NI . " AND left_house='2011-03-24') OR (house=" . HOUSE_TYPE_SCOTLAND . " AND left_house='2011-03-23') )");
        }
    $mcon = array(); $mreg = array();
    for ($i=0; $i<$q->rows(); $i++) {
        $house = $q->field($i, 'house');
        $pid = $q->field($i, 'person_id');
        $name = $q->field($i, 'first_name') . ' ' . $q->field($i, 'last_name');
        $cons = $q->field($i, 'constituency');
        if ($house == HOUSE_TYPE_COMMONS) {
            continue;
        } elseif ($house == HOUSE_TYPE_NI) {
            $mreg[] = $q->row($i);
        } elseif ($house == HOUSE_TYPE_SCOTLAND) {
            if ($cons == $constituencies['SPC']) {
                $mcon = $q->row($i);
            } elseif ($cons == $constituencies['SPE']) {
                $mreg[] = $q->row($i);
            }
        } else {
            throw new MySociety\TheyWorkForYou\MemberException('Odd result returned!' . $house);
        }
    }
    if ($rep_type == 'msp') {
        if ($current) {
            $data['members_statement'] = '<p>You have one constituency MSP (Member of the Scottish Parliament) and multiple region MSPs.</p>';
            $data['members_statement'] .= '<p>Your <strong>constituency MSP</strong> is <a href="/msp/?p=' . $mcon['person_id'] . '">';
            $data['members_statement'] .= $mcon['first_name'] . ' ' . $mcon['last_name'] . '</a>, MSP for ' . $mcon['constituency'];
            $data['members_statement'] .= '.</p> <p>Your <strong>' . $constituencies['SPE'] . ' region MSPs</strong> are:</p>';
        } else {
            $data['members_statement'] = '<p>You had one constituency MSP (Member of the Scottish Parliament) and multiple region MSPs.</p>';
            $data['members_statement'] .= '<p>Your <strong>constituency MSP</strong> was <a href="/msp/?p=' . $mcon['person_id'] . '">';
            $data['members_statement'] .= $mcon['first_name'] . ' ' . $mcon['last_name'] . '</a>, MSP for ' . $mcon['constituency'];
            $data['members_statement'] .= '.</p> <p>Your <strong>' . $constituencies['SPE'] . ' region MSPs</strong> were:</p>';
        }
    } else {
        if ($current) {
            $data['members_statement'] = '<p>You have multiple MLAs (Members of the Legislative Assembly) who represent you in ' . $constituencies['NIE'] . '. They are:</p>';
        } else {
            $data['members_statement'] = '<p>You had multiple MLAs (Members of the Legislative Assembly) who represented you in ' . $constituencies['NIE'] . '. They were:</p>';
        }
    }

    foreach($mreg as $reg) {
        $data['members'][] = array (
            'url' => '/' . $rep_type . '/?p=' . $reg['person_id'],
            'name' => $reg['first_name'] . ' ' . $reg['last_name']
        );

    }

    // Send the output for rendering
    MySociety\TheyWorkForYou\Renderer::output('mp/regional_list', $data);

}

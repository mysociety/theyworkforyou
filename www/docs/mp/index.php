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

// Disable the old PAGE or NEWPAGE classes.
$new_style_template = TRUE;

// Include all the things this page needs.
include_once '../../includes/easyparliament/init.php';
include_once INCLUDESPATH . 'easyparliament/member.php';
include_once INCLUDESPATH . 'postcode.inc';
include_once INCLUDESPATH . 'technorati.php';
include_once '../api/api_getGeometry.php';
include_once '../api/api_getConstituencies.php';

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
        $MEMBER = new MEMBER(array('postcode' => $THEUSER->postcode(), 'house' => HOUSE_TYPE_COMMONS));
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

/////////////////////////////////////////////////////////
// CANONICAL PERSON ID
if (is_numeric($pid))
{

    // Normal, plain, displaying an MP by person ID.
    $MEMBER = new MEMBER(array('person_id' => $pid));

    // If the member ID doesn't exist then the object won't have it set.
    if ($MEMBER->member_id)
    {
        // Ensure that we're actually at the current, correct and canonical URL for the person. If not, redirect.
        if (str_replace('/mp/', '/' . $this_page . '/', get_http_var('url')) !== urldecode($MEMBER->url(FALSE)))
        {
            member_redirect($MEMBER);
        }
    }
    else
    {
        $errors['pc'] = 'Sorry, that ID number wasn\'t recognised.';
    }
}

/////////////////////////////////////////////////////////
// MEMBER ID
elseif (is_numeric(get_http_var('m')))
{
    // Got a member id, redirect to the canonical MP page, with a person id.
    $MEMBER = new MEMBER(array('member_id' => get_http_var('m')));
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
            $errors['pc'] = "Sorry, we couldn't check your postcode right now, as our postcode lookup server is under quite a lot of load.";
        } elseif ($constituency == "") {
            $errors['pc'] = "Sorry, ".htmlentities($pc) ." isn't a known postcode";
            twfy_debug ('MP', "Can't display an MP, as submitted postcode didn't match a constituency");
        } else {
            // Redirect to the canonical MP page, with a person id.
            $MEMBER = new MEMBER(array('constituency' => $constituency, 'house' => HOUSE_TYPE_COMMONS));
            if ($MEMBER->person_id()) {
                // This will cookie the postcode.
                $THEUSER->set_postcode_cookie($pc);
            }
            member_redirect($MEMBER, 302);
        }
    } else {
        $errors['pc'] = "Sorry, ".htmlentities($pc) ." isn't a valid postcode";
        twfy_debug ('MP', "Can't display an MP because the submitted postcode wasn't of a valid form.");
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
        $NEWPAGE->error_message('Your set postcode is not in Scotland.');
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
        $NEWPAGE->error_message('Your set postcode is not in Northern Ireland.');
    }
}

/////////////////////////////////////////////////////////
// DOES THE USER HAVE A POSTCODE ALREADY SET (WESTMINISTER)?
// (Either in their logged-in details or in a cookie from a previous search.)
elseif ($THEUSER->postcode_is_set() && $name == '' && $constituency == '')
{
    $MEMBER = new MEMBER(array('postcode' => $THEUSER->postcode(), 'house' => HOUSE_TYPE_COMMONS));
    member_redirect($MEMBER, 302);
}

/////////////////////////////////////////////////////////
// NAME AND CONSTITUENCY
elseif ($name && $constituency)
{
    $MEMBER = new MEMBER(array('name'=>$name, 'constituency'=>$constituency));

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
    $MEMBER = new MEMBER(array('name' => $name));

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
    $MEMBER = new MEMBER(array('constituency' => $constituency, 'house' => HOUSE_TYPE_COMMONS));
    member_redirect($MEMBER);
}

/////////////////////////////////////////////////////////
// UNABLE TO IDENTIFY MP
else
{
    // No postcode, member_id or person_id to use.
    twfy_debug ('MP', "We don't have any way of telling what MP to display");
}

/////////////////////////////////////////////////////////
// DISPLAY A LIST OF REPRESENTATIVES

header('Cache-Control: max-age=900');

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

    // Set page metadata
    $DATA->set_page_metadata($this_page, 'title', $title);
    $DATA->set_page_metadata($this_page, 'meta_description', $desc);

    // Prepare data for the template
    $data['full_name'] = $MEMBER->full_name();
    $data['person_id'] = $MEMBER->person_id();
    $data['constituency'] = $MEMBER->constituency();
    $data['party'] = $MEMBER->party_text();
    $data['image'] = person_image($MEMBER);
    $data['member_summary'] = person_summary_description($MEMBER);
    $data['rebellion_rate'] = person_rebellion_rate($MEMBER);
    $data['key_votes'] = person_voting_record($MEMBER, $MEMBER->extra_info);
    $data['useful_links'] = person_useful_links($MEMBER);

    /*

    $data['member_id'] = $MEMBER->member_id();


    $data['other_parties'] = $MEMBER->other_parties;
    $data['other_constituencies'] = $MEMBER->other_constituencies;
    $data['houses'] = $MEMBER->houses();
    $data['entered_house'] = $MEMBER->entered_house();
    $data['left_house'] = $MEMBER->left_house();
    $data['current_member'] = $MEMBER->current_member();
    $data['the_users_mp'] = $MEMBER->the_users_mp();
    $data['current_member_anywhere'] = $MEMBER->current_member_anywhere();
    $data['house_disp'] = $MEMBER->house_disp;

    */

    // Send the output for rendering
    MySociety\TheyWorkForYou\Renderer::output('mp/profile', $data);

}

/////////////////////////////////////////////////////////
// SUPPORTING FUNCTIONS

/**
 * Member Redirect
 *
 * Redirect to the canonical page for a member.
 */

function member_redirect (&$MEMBER, $code = 301) {
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
        header('Location: ' . $url, true, $code );
        exit;
    }
}

/**
 * Person Image
 *
 * Return the URL to an image of this person
 */

function person_image ($MEMBER) {
    $is_lord = in_array(HOUSE_TYPE_LORDS, $MEMBER->houses());
    if ($is_lord) {
        list($image,$sz) = find_rep_image($MEMBER->person_id(), false, 'lord');
    } else {
        list($image,$sz) = find_rep_image($MEMBER->person_id(), false, true);
    }
    return $image;
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
            $desc .= htmlentities($party);
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
 * Is Person Dead
 *
 * Determine if the given member has died or not.
 *
 * @param MEMBER $member The member to check for death.
 *
 * @return boolean If the member is dead or not.
 */

function is_member_dead($member) {

    $left_house = $member->left_house();

    if (
        $left_house && (
            ( in_array(HOUSE_TYPE_COMMONS, $left_house) && $left_house[HOUSE_TYPE_COMMONS]['reason'] && $left_house[HOUSE_TYPE_COMMONS]['reason'] == 'Died' ) ||
            ( in_array(HOUSE_TYPE_LORDS, $left_house) && $left_house[HOUSE_TYPE_LORDS ]['reason'] && $left_house[HOUSE_TYPE_LORDS]['reason'] == 'Died' ) ||
            ( in_array(HOUSE_TYPE_SCOTLAND, $left_house) && $left_house[HOUSE_TYPE_SCOTLAND ]['reason'] && $left_house[HOUSE_TYPE_SCOTLAND]['reason'] == 'Died' ) ||
            ( in_array(HOUSE_TYPE_NI, $left_house) && $left_house[HOUSE_TYPE_NI ]['reason'] && $left_house[HOUSE_TYPE_NI]['reason'] == 'Died' )
        )
    ) {
        return TRUE;
    } else {
        return FALSE;
    }

}

/**
 * Person Rebellion Rate
 *
 * How often has this person rebelled against their party?
 *
 * @param MEMBER $member The member to calculate rebellion rate for.
 *
 * @return string A HTML summary of this person's rebellion rate.
 */

function person_rebellion_rate ($member) {

    // Rebellion string may be empty.
    $rebellion_string = '';

    if (isset($member->extra_info['public_whip_rebellions']) && $member->extra_info['public_whip_rebellions'] != 'n/a') {
        $displayed_stuff = 1;
        $rebels_term = 'rebels';
        if (is_member_dead($member)) {
            $rebels_term = 'rebelled';
        }

        $rebellion_string = '<a href="http://www.publicwhip.org.uk/mp.php?id=uk.org.publicwhip/member/' . $member->member_id() . '#divisions" title="See more details at Public Whip"><strong>' . htmlentities(ucfirst($member->extra_info['public_whip_rebel_description'])) . ' ' . $rebels_term . '</strong></a> against their party';

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

function display_dream_comparison($extra_info, $member, $dreamid, $desc, $inverse=false) {
    $out = '';
    if (isset($extra_info["public_whip_dreammp${dreamid}_distance"])) {
        if ($extra_info["public_whip_dreammp${dreamid}_both_voted"] == 0) {
            $dmpdesc = 'Has <strong>never voted</strong> on';
        } else {
            $dmpscore = floatval($extra_info["public_whip_dreammp${dreamid}_distance"]);
            $out .= "<!-- distance $dreamid: $dmpscore -->";
            if ($inverse)
                $dmpscore = 1.0 - $dmpscore;
            $english = score_to_strongly($dmpscore);
            # XXX Note special casing of 2nd tuition fee policy here
            if ($extra_info["public_whip_dreammp${dreamid}_both_voted"] == 1 || $dreamid == 1132) {
                $english = preg_replace('#(very )?(strongly|moderately) #', '', $english);
            }
            $dmpdesc = 'Voted <strong>' . $english . '</strong>';

            // How many votes Dream MP and MP both voted (and didn't abstain) in
            // $extra_info["public_whip_dreammp${dreamid}_both_voted"];
        }
        $out .= $dmpdesc . ' ' . $desc;
    }
    return $out;
}

/**
 * Person Voting Record
 *
 * Return an array containing this person's votes plus string metadata.
 *
 * @param MEMBER $member     The member to generate a record for.
 * @param array  $extra_info Extra info for the member.
 */

function person_voting_record ($member, $extra_info) {

    $out = array();

    $displayed_stuff = 0;

    $policies_object = new MySociety\TheyWorkForYou\Policies;

    $policies = $policies_object->shuffle()->policies;
    $joined = $policies_object->joined;

    $member_houses = $member->houses();
    $entered_house = $member->entered_house();
    $current_member = $member->current_member();

    $member_has_died = is_member_dead($member);

    $key_votes = array();
    foreach ($policies as $policy) {
        if (isset($policy[2]) && $policy[2] && !in_array(HOUSE_TYPE_COMMONS, $member_houses))
            continue;
        $dream = display_dream_comparison($extra_info, $member, $policy[0], $policy[1]);
        if ($dream !== '') {
            $key_votes[] = $dream;
        }
        if (isset($joined[$policy[0]])) {
            $policy = $joined[$policy[0]];
            $dream = display_dream_comparison($extra_info, $member, $policy[0], $policy[1]);
            if ($dream !== '') {
                $key_votes[] = $dream;
            }
        }
    }

    if (count($key_votes) > 0) {
        $displayed_stuff = 1;
        if (in_array(HOUSE_TYPE_COMMONS, $member_houses) && $entered_house[HOUSE_TYPE_COMMONS]['date'] > '2001-06-07') {
            $since = '';
        } elseif (!in_array(HOUSE_TYPE_COMMONS, $member_houses) && in_array(HOUSE_TYPE_LORDS, $member_houses) && $entered_house[HOUSE_TYPE_LORDS]['date'] > '2001-06-07') {
            $since = '';
        } elseif ($member_has_died) {
            $since = '';
        } else {
            $since = ' since 2001';
        }
        # If not current MP/Lord, but current MLA/MSP, need to say voting record is when MP
        if (!$current_member[HOUSE_TYPE_COMMONS] && !$current_member[HOUSE_TYPE_LORDS] && ($current_member[HOUSE_TYPE_SCOTLAND] || $current_member[HOUSE_TYPE_NI])) {
            $since .= ' whilst an MP';
        }
        $out['since_string'] = $since;
    }

    $out['key_votes'] = $key_votes;

    // Links to full record at Guardian and Public Whip
    $record = array();
    if (isset($extra_info['guardian_howtheyvoted'])) {
        $record[] = '<a href="' . $extra_info['guardian_howtheyvoted'] . '" title="At The Guardian">well-known issues</a> <small>(from the Guardian)</small>';
    }
    if ((isset($extra_info['public_whip_division_attendance']) && $extra_info['public_whip_division_attendance'] != 'n/a')
      || (isset($extra_info['Lpublic_whip_division_attendance']) && $extra_info['Lpublic_whip_division_attendance'] != 'n/a')) {
        $record[] = '<a href="http://www.publicwhip.org.uk/mp.php?id=uk.org.publicwhip/member/' . $member->member_id() . '&amp;showall=yes#divisions" title="At Public Whip">their full record</a>';
    }

    if (count($record) > 0) {
        $displayed_stuff = 1;
        $out['more_link'] = 'More on ' . implode(' &amp; ', $record);
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

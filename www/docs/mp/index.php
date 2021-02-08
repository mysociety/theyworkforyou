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
include_once INCLUDESPATH . 'easyparliament/member.php';
include_once INCLUDESPATH . '../../commonlib/phplib/random.php';
include_once INCLUDESPATH . '../../commonlib/phplib/auth.php';
include_once '../api/api_getGeometry.php';
include_once '../api/api_getConstituencies.php';

// Ensure that page type is set
if (get_http_var('pagetype')) {
    $pagetype = get_http_var('pagetype');
} else {
    $pagetype = 'profile';
}
if ($pagetype == 'profile') {
    $pagetype = '';
}

// list of years for which we have WTT response stats in
// reverse chronological order. Add new years here as we
// get them.
// NB: also need to update ./mpinfoin.pl to import the stats
$wtt_stats_years = array(2015, 2014, 2013, 2008, 2007, 2006, 2005);

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
        $URL = new \MySociety\TheyWorkForYou\Url('search');
        $URL->insert( array('pid'=>$pid, 'pop'=>1) );
        header('Location: ' . $URL->generate('none'));
        exit;
    }
}

/////////////////////////////////////////////////////////
// DETERMINE TYPE OF REPRESENTITIVE

switch (get_http_var('representative_type')) {
    case 'peer':
        $this_page = 'peer';
        break;
    case 'royal':
        $this_page = 'royal';
        break;
    case 'mla':
        $this_page = 'mla';
        break;
    case 'msp':
        $this_page = 'msp';
        break;
    case 'london-assembly-member':
        $this_page = 'london-assembly-member';
        break;
    default:
        $this_page = 'mp';
        break;
}

try {
    if (is_numeric($pid)) {
        $MEMBER = get_person_by_id($pid);
    } elseif (is_numeric(get_http_var('m'))) {
        get_person_by_member_id(get_http_var('m'));
    } elseif (get_http_var('pc')) {
        get_person_by_postcode(get_http_var('pc'));
    } elseif ($name) {
        $MEMBER = get_person_by_name($name, $constituency);
    } elseif ($constituency) {
        get_mp_by_constituency($constituency);
    } elseif (($this_page == 'msp' || $this_page == 'mla') && $THEUSER->postcode_is_set()) {
        get_regional_by_user_postcode($THEUSER->postcode(), $this_page);
        exit;
    } elseif ($THEUSER->postcode_is_set()) {
        get_mp_by_user_postcode($THEUSER->postcode());
    } else {
        twfy_debug ('MP', "We don't have any way of telling what MP to display");
        throw new MySociety\TheyWorkForYou\MemberException('Sorry, but we can&rsquo;t tell which representative to display.');
    }
    if (!isset($MEMBER) || !$MEMBER->valid) {
        throw new MySociety\TheyWorkForYou\MemberException('You haven&rsquo;t provided a way of identifying which representative you want');
    }
} catch (MySociety\TheyWorkForYou\MemberMultipleException $e) {
    person_list_page($e->ids);
    exit;
} catch (MySociety\TheyWorkForYou\MemberException $e) {
    person_error_page($e->getMessage());
    exit;
}

# We have successfully looked up one person to show now.

if (!DEVSITE) {
    header('Cache-Control: max-age=900');
}

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

$known_for = '';
$current_offices_ignoring_committees = $MEMBER->offices('current', TRUE);
if (count($current_offices_ignoring_committees) > 0) {
    $known_for = $current_offices_ignoring_committees[0];
}

// Finally, if this is a Votes page, replace the page description with
// something more descriptive of the actual data on the page.
if ($pagetype == 'votes') {
  $title = "Voting record - " . $title;
  $desc = 'See how ' . $member_name . ' voted on topics like Employment, Social Issues, Foreign Policy, and more.';
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

$data['known_for'] = $known_for;
$data['latest_membership'] = $MEMBER->getMostRecentMembership();

$data['constituency'] = $MEMBER->constituency();
$data['party'] = $MEMBER->party_text();
$data['current_member_anywhere'] = $MEMBER->current_member_anywhere();
$data['current_member'] = $MEMBER->current_member();
$data['the_users_mp'] = $MEMBER->the_users_mp();
$data['user_postcode'] = $THEUSER->postcode;
$data['houses'] = $MEMBER->houses();
$data['member_url'] = $MEMBER->url();
$data['abs_member_url'] = $MEMBER->url(true);
// If there's photo attribution information, copy it into data
foreach (['photo_attribution_text', 'photo_attribution_link'] as $key) {
    if (isset($MEMBER->extra_info[$key])) {
        $data[$key] = $MEMBER->extra_info[$key];
    }
}
$data['profile_message'] = isset($MEMBER->extra_info['profile_message']) ? $MEMBER->extra_info['profile_message'] : '';
$data['image'] = $MEMBER->image();
$data['member_summary'] = person_summary_description($MEMBER);
$data['enter_leave'] = $MEMBER->getEnterLeaveStrings();
$data['entry_date'] = $MEMBER->getEntryDate();
$data['leave_date'] = $MEMBER->getLeftDate();
$data['is_new_mp'] = $MEMBER->isNew();
$data['other_parties'] = $MEMBER->getOtherPartiesString();
$data['other_constituencies'] = $MEMBER->getOtherConstituenciesString();
$data['rebellion_rate'] = person_rebellion_rate($MEMBER);
$data['recent_appearances'] = person_recent_appearances($MEMBER);
$data['useful_links'] = person_useful_links($MEMBER);
$data['social_links'] = person_social_links($MEMBER);
$data['topics_of_interest'] = person_topics($MEMBER);
$data['current_offices'] = $MEMBER->offices('current');
$data['previous_offices'] = $MEMBER->offices('previous');
$data['register_interests'] = person_register_interests($MEMBER, $MEMBER->extra_info);
$data['eu_stance'] = $MEMBER->getEUStance();

# People who are or were MPs and Lords potentially have voting records, except Sinn Fein MPs
$data['has_voting_record'] = ( ($MEMBER->house(HOUSE_TYPE_COMMONS) && $MEMBER->party() != 'Sinn Féin') || $MEMBER->house(HOUSE_TYPE_LORDS) );
# Everyone who is currently somewhere has email alert signup, apart from current Sinn Fein MPs who are not MLAs
$data['has_email_alerts'] = ($MEMBER->current_member_anywhere() && !($MEMBER->current_member(HOUSE_TYPE_COMMONS) && $MEMBER->party() == 'Sinn Féin' && !$MEMBER->current_member(HOUSE_TYPE_NI)));
$data['has_expenses'] = $data['leave_date'] > '2004-01-01';

$data['pre_2010_expenses'] = False;
$data['post_2010_expenses'] = $data['leave_date'] > '2010-05-05';

if ($data['entry_date'] < '2010-05-05') {
    $data['pre_2010_expenses'] = True;
    // Set the expenses URL if we know it
    if (isset($MEMBER->extra_info['expenses_url'])) {
        $data['expenses_url_2004'] = $MEMBER->extra_info['expenses_url'];
    } else {
        $data['expenses_url_2004'] = 'https://mpsallowances.parliament.uk/mpslordsandoffices/hocallowances/allowances%2Dby%2Dmp/';
    }
}

$data['constituency_previous_mps'] = constituency_previous_mps($MEMBER);
$data['constituency_future_mps'] = constituency_future_mps($MEMBER);
$data['public_bill_committees'] = person_pbc_membership($MEMBER);

$data['this_page'] = $this_page;
$country = MySociety\TheyWorkForYou\Utility\House::getCountryDetails($data['latest_membership']['house']);
$data['current_assembly'] = $country[2];

$data['policy_last_update'] = MySociety\TheyWorkForYou\Divisions::getMostRecentDivisionDate();

// Do any necessary extra work based on the page type, and send for rendering.
switch ($pagetype) {

    case 'votes':
        $policy_set = get_http_var('policy');

        $policiesList = new MySociety\TheyWorkForYou\Policies;
        $divisions = new MySociety\TheyWorkForYou\Divisions($MEMBER);
        $policySummaries = $divisions->getMemberDivisionDetails();

        $policyOptions = array( 'summaries' => $policySummaries);

        // Generate voting segments
        $set_descriptions = $policiesList->getSetDescriptions();
        if ( $policy_set && array_key_exists($policy_set, $set_descriptions) ) {
            $sets = array($policy_set);
            $data['og_image'] = $MEMBER->url(true) . "/policy_set_png?policy_set=" . $policy_set;
            $data['page_title'] = $set_descriptions[$policy_set] . ' ' . $title . ' - TheyWorkForYou';
            $data['meta_description'] = 'See how ' . $data['full_name'] . ' voted on ' . $set_descriptions[$policy_set];
            $data['single_policy_page'] = true;
        } else {
            $data['single_policy_page'] = false;
            $sets = array(
                'social', 'foreignpolicy', 'welfare', 'taxation', 'business',
                'health', 'education', 'reform', 'home', 'environment',
                'transport', 'housing', 'misc'
            );
            shuffle($sets);
        }

        $data['key_votes_segments'] = array();
        foreach ($sets as $key) {
            $data['key_votes_segments'][] = array(
                'key'   => $key,
                'title' => $set_descriptions[$key],
                'votes' => new MySociety\TheyWorkForYou\PolicyPositions(
                    $policiesList->limitToSet($key), $MEMBER, $policyOptions
                )
            );
        }

        // Send the output for rendering
        MySociety\TheyWorkForYou\Renderer::output('mp/votes', $data);

        break;

    case 'recent':
        $divisions = new MySociety\TheyWorkForYou\Divisions($MEMBER);
        $data['divisions'] = $divisions->getRecentMemberDivisions();
        MySociety\TheyWorkForYou\Renderer::output('mp/recent', $data);
        break;

    case 'divisions':
        $policyID = get_http_var('policy');
        if ( $policyID ) {
            $policiesList = new MySociety\TheyWorkForYou\Policies( $policyID );
        } else {
            $policiesList = new MySociety\TheyWorkForYou\Policies;
        }
        $positions = new MySociety\TheyWorkForYou\PolicyPositions( $policiesList, $MEMBER );
        $divisions = new MySociety\TheyWorkForYou\Divisions($MEMBER, $positions, $policiesList);

        if ( $policyID ) {
            $data['policydivisions'] = $divisions->getMemberDivisionsForPolicy($policyID);
        } else {
            $data['policydivisions'] = $divisions->getAllMemberDivisionsByPolicy();
        }

        // Send the output for rendering
        MySociety\TheyWorkForYou\Renderer::output('mp/divisions', $data);

        break;

    case 'policy_set_svg':
        policy_image($data, $MEMBER, 'svg');
        break;

    case 'policy_set_png':
        policy_image($data, $MEMBER, 'png');
        break;

    case '':
    default:

        $policiesList = new MySociety\TheyWorkForYou\Policies;
        $policies = $policiesList->limitToSet('summary');
        $divisions = new MySociety\TheyWorkForYou\Divisions($MEMBER);
        $policySummaries = $divisions->getMemberDivisionDetails();

        $policyOptions = array('limit' => 6, 'summaries' => $policySummaries);

        // Generate limited voting record list
        $data['policyPositions'] = new MySociety\TheyWorkForYou\PolicyPositions($policies, $MEMBER, $policyOptions);

        // generate party policy diffs
        $party = new MySociety\TheyWorkForYou\Party($MEMBER->party());
        $positions = new MySociety\TheyWorkForYou\PolicyPositions( $policiesList, $MEMBER );
        $party_positions = $party->getAllPolicyPositions($policiesList);
        $policy_diffs = $MEMBER->getPartyPolicyDiffs($party, $policiesList, $positions, true);

        $data['sorted_diffs'] = $policy_diffs;
        # house hard coded as this is only used for the party position
        # comparison which is Commons only
        $data['party_member_count'] = $party->getCurrentMemberCount(HOUSE_TYPE_COMMONS);
        $data['party_positions'] = $party_positions;
        $data['positions'] = $positions->positionsById;
        $data['policies'] = $policiesList->getPolicies();

        // Send the output for rendering
        MySociety\TheyWorkForYou\Renderer::output('mp/profile', $data);

        break;

}


/////////////////////////////////////////////////////////
// SUPPORTING FUNCTIONS

/* Person lookup functions */

function get_person_by_id($pid) {
    global $pagetype, $this_page;
    $MEMBER = new MySociety\TheyWorkForYou\Member(array('person_id' => $pid));
    if (!$MEMBER->valid) {
        throw new MySociety\TheyWorkForYou\MemberException('Sorry, that ID number wasn&rsquo;t recognised.');
    }
    // Ensure that we're actually at the current, correct and canonical URL for the person. If not, redirect.
    // No need to worry about other URL syntax forms for vote pages, they shouldn't happen.
    $at = str_replace('/mp/', "/$this_page/", get_http_var('url'));
    $shouldbe = urldecode($MEMBER->url());
    if ($pagetype) {
        $shouldbe .= "/$pagetype";
    }
    if ($at !== $shouldbe) {
        member_redirect($MEMBER, 301, $pagetype);
    }
    return $MEMBER;
}

function get_person_by_member_id($member_id) {
    // Got a member id, redirect to the canonical MP page, with a person id.
    $MEMBER = new MySociety\TheyWorkForYou\Member(array('member_id' => $member_id));
    member_redirect($MEMBER);
}

function get_person_by_postcode($pc) {
    global $THEUSER;
    $pc = preg_replace('#[^a-z0-9]#i', '', $pc);
    if (!validate_postcode($pc)) {
        twfy_debug ('MP', "Can't display an MP because the submitted postcode wasn't of a valid form.");
        throw new MySociety\TheyWorkForYou\MemberException('Sorry, '._htmlentities($pc) .' isn&rsquo;t a valid postcode');
    }
    twfy_debug ('MP', "MP lookup by postcode");
    $constituency = strtolower(MySociety\TheyWorkForYou\Utility\Postcode::postcodeToConstituency($pc));
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
}

function get_person_by_name($name, $const='') {
    $MEMBER = new MySociety\TheyWorkForYou\Member(array('name' => $name, 'constituency' => $const));
    // Edge case, only attempt further detection if this isn't the Queen.
    if ($name !== 'elizabeth the second' || $const) {
        twfy_debug ('MP', 'Redirecting for MP found by name/constituency');
        member_redirect($MEMBER);
    }
    return $MEMBER;
}

function get_mp_by_constituency($constituency) {
    $MEMBER = new MySociety\TheyWorkForYou\Member(array('constituency' => $constituency, 'house' => HOUSE_TYPE_COMMONS));
    member_redirect($MEMBER);
}

function get_regional_by_user_postcode($pc, $page) {
    global $this_page;
    $this_page = "your$page";
    if ($page == 'msp' && \MySociety\TheyWorkForYou\Utility\Postcode::postcodeIsScottish($pc)) {
        regional_list($pc, 'SPC', $page);
    } elseif ($page == 'mla' && \MySociety\TheyWorkForYou\Utility\Postcode::postcodeIsNi($pc)) {
        regional_list($pc, 'NIE', $page);
    } else {
        throw new MySociety\TheyWorkForYou\MemberException('Your set postcode is not in the right region.');
    }
}

function get_mp_by_user_postcode($pc) {
    $MEMBER = new MySociety\TheyWorkForYou\Member(array('postcode' => $pc, 'house' => HOUSE_TYPE_COMMONS));
    member_redirect($MEMBER, 302);
}

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
        if ($pagetype) {
            $pagetype = '/' . $pagetype;
        } else {
            $pagetype = '';
        }
        header('Location: ' . $url . $pagetype, true, $code );
        exit;
    }
}

/* Error list page */

function person_list_page($ids) {
    global $name;
    if (!DEVSITE) {
        header('Cache-Control: max-age=900');
    }
    $data = array('mps' => array());
    foreach ($ids as $id => $constituency) {
        $data['mps'][] = array(
            'url'  => WEBPATH . 'mp/?pid=' . $id,
            'name' => ucwords(strtolower($name)) . ', ' . $constituency,
        );
    }
    $MPSURL = new \MySociety\TheyWorkForYou\Url('mps');
    $data['all_mps_url'] = $MPSURL->generate();
    MySociety\TheyWorkForYou\Renderer::output('mp/list', $data);
}

/* Error page */

function person_error_page($message) {
    global $this_page;
    switch($this_page) {
    case 'mla':
        $rep = 'MLA';
        $SEARCHURL = '/postcode/';
        $MPSURL = new \MySociety\TheyWorkForYou\Url('mlas');
        break;
    case 'msp':
        $rep = 'MSP';
        $SEARCHURL = '/postcode/';
        $MPSURL = new \MySociety\TheyWorkForYou\Url('msps');
        break;
    case 'peer':
        $rep = 'Lord';
        $SEARCHURL = '';
        $MPSURL = new \MySociety\TheyWorkForYou\Url('peers');
        break;
    default:
        $rep = 'MP';
        $SEARCHURL = new \MySociety\TheyWorkForYou\Url('mp');
        $SEARCHURL = $SEARCHURL->generate();
        $MPSURL = new \MySociety\TheyWorkForYou\Url('mps');
    }

    $data = array(
        'error' => $message,
        'rep_name' => $rep,
        'all_mps_url' => $MPSURL->generate(),
        'rep_search_url' => $SEARCHURL,
    );
    MySociety\TheyWorkForYou\Renderer::output('mp/error', $data);
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
 * @param MEMBER $member The member to calculate rebellion rate for.
 *
 * @return string A HTML summary of this person's rebellion rate.
 */

function person_rebellion_rate ($member) {

    // Rebellion string may be empty.
    $rebellion_string = '';

    if (isset($member->extra_info['public_whip_rebellions']) && $member->extra_info['public_whip_rebellions'] != 'n/a') {
        $rebels_term = 'rebelled';

        $rebellion_string = 'has <a href="https://www.publicwhip.org.uk/mp.php?id=uk.org.publicwhip/member/' . $member->member_id() . '#divisions" title="See more details at Public Whip"><strong>' . _htmlentities($member->extra_info['public_whip_rebel_description']) . ' ' . $rebels_term . '</strong></a> against their party';

        if (isset($member->extra_info['public_whip_rebelrank'])) {
            if ($member->extra_info['public_whip_data_date'] == 'complete') {
                $rebellion_string .= ' in their last parliament.';
            } else {
                $rebellion_string .= ' in the current parliament.';
            }
        }

        $rebellion_string .= ' <small><a title="What do the rebellion figures mean exactly?" href="https://www.publicwhip.org.uk/faq.php#clarify">Find out more</a>.</small>';
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

    $memcache = new MySociety\TheyWorkForYou\Memcache;
    $recent = $memcache->get('recent_appear:' . $person_id);

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
        $memcache->set('recent_appear:' . $person_id, $recent);
    }
    $out['appearances'] = unserialize($recent);
    twfy_debug_timestamp();

    $MOREURL = new \MySociety\TheyWorkForYou\Url('search');
    $MOREURL->insert( array('pid'=>$person_id, 'pop'=>1) );

    $out['more_href'] = $MOREURL->generate() . '#n4';
    $out['more_text'] = 'More of ' . ucfirst($member->full_name()) . '&rsquo;s recent appearances';

    if ($rssurl = $DATA->page_metadata($this_page, 'rss')) {
        // If we set an RSS feed for this page.
        $HELPURL = new \MySociety\TheyWorkForYou\Url('help');
        $out['additional_links'] = '<a href="' . WEBPATH . $rssurl . '" title="XML version of this person&rsquo;s recent appearances">RSS feed</a> (<a href="' . $HELPURL->generate() . '#rss" title="An explanation of what RSS feeds are for">?</a>)';
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

    if (isset($links['sp_url'])) {
        $out[] = array(
                'href' => $links['sp_url'],
                'text' => 'Page on the Scottish Parliament website'
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

    return $out;
}

function person_social_links($member) {

    $links = $member->extra_info();

    $out = array();

    if (isset($links['twitter_username'])) {
        $out[] = array(
                'href' => 'https://twitter.com/' . _htmlentities($links['twitter_username']),
                'text' => '@' . _htmlentities($links['twitter_username']),
                'type' => 'twitter'
        );
    }

    if (isset($links['facebook_page'])) {
        $out[] = array(
                'href' => _htmlentities($links['facebook_page']),
                'text' => _htmlentities($links['facebook_page']),
                'type' => 'facebook'
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
        return $member->previous_mps();
    } else {
        return array();
    }
}

function constituency_future_mps($member) {
    if ($member->house(HOUSE_TYPE_COMMONS)) {
        return $member->future_mps();
    } else {
        return array();
    }
}

function person_pbc_membership($member) {

    $extra_info = $member->extra_info();
    $out = array('info'=>'', 'data'=>array());

    # Public Bill Committees
    if (count($extra_info['pbc'])) {
        if ($member->party() == 'Scottish National Party') {
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
    $constituencies = MySociety\TheyWorkForYou\Utility\Postcode::postcodeToConstituencies($pc);
    if ($constituencies == 'CONNECTION_TIMED_OUT') {
        throw new MySociety\TheyWorkForYou\MemberException('Sorry, we couldn&rsquo;t check your postcode right now, as our postcode lookup server is under quite a lot of load.');
    } elseif (!$constituencies) {
        throw new MySociety\TheyWorkForYou\MemberException('Sorry, ' . htmlentities($pc) . ' isn&rsquo;t a known postcode');
    } elseif (!isset($constituencies[$area_type])) {
        throw new MySociety\TheyWorkForYou\MemberException(htmlentities($pc) . ' does not appear to be a valid postcode');
    }
    global $PAGE;
    $a = array_values($constituencies);
    $db = new ParlDB;
    $query_base = "SELECT member.person_id, given_name, family_name, constituency, house
        FROM member, person_names pn
        WHERE constituency IN ('" . join("','", $a) . "')
            AND member.person_id = pn.person_id AND pn.type = 'name'
            AND pn.end_date = (SELECT MAX(end_date) FROM person_names WHERE person_names.person_id = member.person_id)";
    $q = $db->query($query_base . " AND left_reason = 'still_in_office' AND house in (" . HOUSE_TYPE_NI . "," . HOUSE_TYPE_SCOTLAND . ")");
    $current = true;
    if (!$q->rows() && ($dissolution = MySociety\TheyWorkForYou\Dissolution::db())) {
        $current = false;
        $q = $db->query($query_base . " AND $dissolution[query]",
            $dissolution['params']);
    }
    $mcon = array(); $mreg = array();
    foreach ($q as $row) {
        $house = $row['house'];
        $cons = $row['constituency'];
        if ($house == HOUSE_TYPE_COMMONS) {
            continue;
        } elseif ($house == HOUSE_TYPE_NI) {
            $mreg[] = $row;
        } elseif ($house == HOUSE_TYPE_SCOTLAND) {
            if ($cons == $constituencies['SPC']) {
                $mcon = $row;
            } elseif ($cons == $constituencies['SPE']) {
                $mreg[] = $row;
            }
        } else {
            throw new MySociety\TheyWorkForYou\MemberException('Odd result returned!' . $house);
        }
    }
    if ($rep_type == 'msp') {
        if ($current) {
            $data['members_statement'] = '<p>You have one constituency MSP (Member of the Scottish Parliament) and multiple region MSPs.</p>';
            $data['members_statement'] .= '<p>Your <strong>constituency MSP</strong> is <a href="/msp/?p=' . $mcon['person_id'] . '">';
            $data['members_statement'] .= $mcon['given_name'] . ' ' . $mcon['family_name'] . '</a>, MSP for ' . $mcon['constituency'];
            $data['members_statement'] .= '.</p> <p>Your <strong>' . $constituencies['SPE'] . ' region MSPs</strong> are:</p>';
        } else {
            $data['members_statement'] = '<p>You had one constituency MSP (Member of the Scottish Parliament) and multiple region MSPs.</p>';
            $data['members_statement'] .= '<p>Your <strong>constituency MSP</strong> was <a href="/msp/?p=' . $mcon['person_id'] . '">';
            $data['members_statement'] .= $mcon['given_name'] . ' ' . $mcon['family_name'] . '</a>, MSP for ' . $mcon['constituency'];
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
            'name' => $reg['given_name'] . ' ' . $reg['family_name']
        );

    }

    // Send the output for rendering
    MySociety\TheyWorkForYou\Renderer::output('mp/regional_list', $data);

}

function policy_image($data, $MEMBER, $format) {
    $policiesList = new MySociety\TheyWorkForYou\Policies;
    $set_descriptions = $policiesList->getSetDescriptions();
    $policy_set = get_http_var('policy_set');

    if (!array_key_exists($policy_set, $set_descriptions)) {
        header('HTTP/1.0 404 Not Found');
        exit();
    }

    // Generate voting segments
    $data['segment'] = array(
      'key'   => $policy_set,
      'title' => $policiesList->getSetDescriptions()[$policy_set],
      'votes' => new MySociety\TheyWorkForYou\PolicyPositions(
          $policiesList->limitToSet($policy_set), $MEMBER
      )
    );

    if ($format === 'png') {
        ob_start();
    }
    MySociety\TheyWorkForYou\Renderer::output('mp/votes_svg', $data, true);
    if ($format === 'svg') {
        return;
    }

    $svg = ob_get_clean();

    $im = new Imagick();
    $im->setOption('-antialias', true);
    $im->readImageBlob($svg);
    $im->setImageFormat("png24");

    $filename = strtolower(str_replace(' ', '_', $MEMBER->full_name() . "_" . $policiesList->getSetDescriptions()[$policy_set] . ".png"));
    header("Content-type: image/png");
    header('Content-Disposition: filename="' . $filename . '"');
    print $im->getImageBlob();

    $im->clear();
    $im->destroy();
}

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

use MySociety\TheyWorkForYou\PolicyDistributionCollection;
use MySociety\TheyWorkForYou\PolicyComparisonPeriod;

$new_style_template = true;

// Include all the things this page needs.
include_once '../../includes/easyparliament/init.php';
include_once INCLUDESPATH . 'easyparliament/member.php';
include_once INCLUDESPATH . '../../commonlib/phplib/random.php';
include_once INCLUDESPATH . '../../commonlib/phplib/auth.php';
include_once '../api/api_getGeometry.php';
include_once '../api/api_getConstituencies.php';

// Ensure that page type is set
$allowed_page_types = ['divisions', 'votes', 'policy_set_svg', 'policy_set_png', 'recent', 'register', 'election_register', 'memberships'];

if (get_http_var('pagetype')) {
    $pagetype = get_http_var('pagetype');
} else {
    $pagetype = 'profile';
}
if (!in_array($pagetype, $allowed_page_types)) {
    $pagetype = 'profile';
}
if ($pagetype == 'profile') {
    $pagetype = '';
}

// list of years for which we have WTT response stats in
// reverse chronological order. Add new years here as we
// get them.
// NB: also need to update ./mpinfoin.pl to import the stats
$wtt_stats_years = [2015, 2014, 2013, 2008, 2007, 2006, 2005];

// Set the PID, name and constituency.
$pid = get_http_var('pid') != '' ? get_http_var('pid') : get_http_var('p');
$name = strtolower(str_replace('_', ' ', get_http_var('n')));
$constituency = strtolower(str_replace('_', ' ', get_http_var('c')));

// Fix for names with non-ASCII characters
if ($name == 'sion simon') {
    $name = 'si\xf4n simon';
}
if ($name == 'sian james') {
    $name = 'si\xe2n james';
}
if ($name == 'lembit opik') {
    $name = 'lembit \xf6pik';
}
if ($name == 'bairbre de brun') {
    $name = 'bairbre de br\xfan';
}
if ($name == 'daithi mckay') {
    $name = 'daith\xed mckay';
}
if ($name == 'caral ni chuilin') {
    $name = 'car\xe1l n\xed chuil\xedn';
}
if ($name == 'caledon du pre') {
    $name = 'caledon du pr\xe9';
}
if ($name == 'sean etchingham') {
    $name = 'se\xe1n etchingham';
}
if ($name == 'john tinne') {
    $name = 'john tinn\xe9';
}
if ($name == 'renee short') {
    $name = 'ren\xe9e short';
}

// Fix for common misspellings, name changes etc
$name_fix = [
    'a j beith' => 'alan beith',
    'micky brady' => 'mickey brady',
    'daniel rogerson' => 'dan rogerson',
    'andrew slaughter' => 'andy slaughter',
    'robert wilson' => ['rob wilson', 'reading east'],
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
];

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
if ($constituency == 'ynys mon') {
    $constituency = "ynys m\xf4n";
}
if (preg_match("#^ynys m\xc3\xb4n#i", $constituency)) {
    $constituency = "ynys m\xf4n";
}

// If this is a request for recent appearances, redirect to search results
if (get_http_var('recent')) {
    if ($THEUSER->postcode_is_set() && !$pid) {
        $MEMBER = new MySociety\TheyWorkForYou\Member(['postcode' => $THEUSER->postcode(), 'house' => HOUSE_TYPE_COMMONS]);
        if ($MEMBER->person_id()) {
            $pid = $MEMBER->person_id();
        }
    }
    if ($pid) {
        $URL = new \MySociety\TheyWorkForYou\Url('search');
        $URL->insert(['pid' => $pid, 'pop' => 1]);
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
    case 'ms':
        $this_page = 'ms';
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
    } elseif (($this_page == 'msp' || $this_page == 'mla' || $this_page == 'ms') && $THEUSER->postcode_is_set()) {
        get_regional_by_user_postcode($THEUSER->postcode(), $this_page);
        exit;
    } elseif ($THEUSER->postcode_is_set()) {
        get_mp_by_user_postcode($THEUSER->postcode());
    } else {
        twfy_debug('MP', "We don't have any way of telling what MP to display");
        throw new MySociety\TheyWorkForYou\MemberException(gettext('Sorry, but we can’t tell which representative to display.'));
    }
    if (!isset($MEMBER) || !$MEMBER->valid) {
        throw new MySociety\TheyWorkForYou\MemberException(gettext('You haven’t provided a way of identifying which representative you want'));
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
if ($MEMBER->current_member_anywhere()) {
    $desc .= ', investigate their voting record, and get email alerts on their activity';
}

// Enhance title if this is a member of the Commons
if ($MEMBER->house(HOUSE_TYPE_COMMONS)) {
    if (!$MEMBER->current_member(1)) {
        $title .= ', former';
    }
    $title .= ' MP';
    if ($MEMBER->constituency()) {
        $title .= ', ' . $MEMBER->constituency();
    }
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
    if ($MEMBER->constituency()) {
        $title .= ', ' . $MEMBER->constituency();
    }
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
    $title .= ' MSP, ' . $MEMBER->constituency();
}

// Enhance title if this is a member of Welsh Parliament
if ($MEMBER->house(HOUSE_TYPE_WALES)) {
    if ($MEMBER->house(HOUSE_TYPE_COMMONS) || $MEMBER->house(HOUSE_TYPE_LORDS)) {
        $desc = str_replace('Parliament', 'the UK and Welsh Parliaments', $desc);
    } else {
        $desc = str_replace('Parliament', 'the Senedd', $desc);
    }
    $desc = str_replace(', and get email alerts on their activity', '', $desc);
    if (!$MEMBER->current_member(HOUSE_TYPE_WALES)) {
        $title .= ', former';
    }
    $title .= ' MS, ' . $MEMBER->constituency();
}

$known_for = '';
$current_offices_ignoring_committees = $MEMBER->offices('current', true);
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
if (file_exists(BASEDIR . '/' . $feedurl)) {
    $DATA->set_page_metadata($this_page, 'rss', $feedurl);
}

// Prepare data for the template
$data["pagetype"] = $pagetype;
$data['full_name'] = $MEMBER->full_name();
$data['person_id'] = $MEMBER->person_id();
$data['member_id'] = $MEMBER->member_id();

$data['known_for'] = $known_for;
$data['latest_membership'] = $MEMBER->getMostRecentMembership();

$data['constituency'] = $MEMBER->constituency();
$data['party'] = $MEMBER->party_text();
$data['current_party_comparison'] = $MEMBER->currentPartyComparison();
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
$data['profile_message'] = $MEMBER->extra_info['profile_message'] ?? '';
$data['image'] = $MEMBER->image();
$data['member_summary'] = person_summary_description($MEMBER);
$data['enter_leave'] = $MEMBER->getEnterLeaveStrings();
$data['entry_date'] = $MEMBER->getEntryDate(HOUSE_TYPE_COMMONS);
$data['leave_date'] = $MEMBER->getLeftDate(HOUSE_TYPE_COMMONS);
$data['is_new_mp'] = $MEMBER->isNew();
$data['other_parties'] = $MEMBER->getOtherPartiesString();
$data['other_constituencies'] = $MEMBER->getOtherConstituenciesString();
$data['rebellion_rate'] = person_rebellion_rate($MEMBER);
$data['recent_appearances'] = person_recent_appearances($MEMBER);
$data['useful_links'] = person_useful_links($MEMBER);
$data['social_links'] = person_social_links($MEMBER);
$data['current_offices'] = $MEMBER->offices('current', true);
$data['previous_offices'] = $MEMBER->offices('previous', true);
$data['register_interests'] = person_register_interests($MEMBER, $MEMBER->extra_info);
$data['register_2024_enriched'] = person_register_interests_from_key('person_regmem_enriched2024_en', $MEMBER->extra_info);
$data['standing_down_2024'] = $MEMBER->extra_info['standing_down_2024'] ?? '';
$data['memberships'] = memberships($MEMBER);

# People who are or were MPs and Lords potentially have voting records, except Sinn Fein MPs
$data['has_voting_record'] = (($MEMBER->house(HOUSE_TYPE_COMMONS) && $MEMBER->party() != 'Sinn Féin') || $MEMBER->house(HOUSE_TYPE_LORDS));
# Everyone who is currently somewhere has email alert signup, apart from current Sinn Fein MPs who are not MLAs
$data['has_email_alerts'] = ($MEMBER->current_member_anywhere() && !($MEMBER->current_member(HOUSE_TYPE_COMMONS) && $MEMBER->party() == 'Sinn Féin' && !$MEMBER->current_member(HOUSE_TYPE_NI)));
$data['has_expenses'] = $data['leave_date'] > '2004-01-01';

$data['pre_2010_expenses'] = false;
$data['post_2010_expenses'] = $data['leave_date'] > '2010-05-05' ? ($MEMBER->extra_info['datadotparl_id'] ?? '') : '';

if ($data['entry_date'] < '2010-05-05') {
    $data['pre_2010_expenses'] = true;
    // Set the expenses URL if we know it
    $data['expenses_url_2004'] = $MEMBER->extra_info['expenses_url'] ?? 'https://mpsallowances.parliament.uk/mpslordsandoffices/hocallowances/allowances%2Dby%2Dmp/';
}

$data['constituency_previous_mps'] = constituency_previous_mps($MEMBER);
$data['constituency_future_mps'] = constituency_future_mps($MEMBER);
$data['public_bill_committees'] = person_pbc_membership($MEMBER);

$data['this_page'] = $this_page;
$country = MySociety\TheyWorkForYou\Utility\House::getCountryDetails($data['latest_membership']['house']);
$data['current_assembly'] = $country[2];

$data['policy_last_update'] = MySociety\TheyWorkForYou\Divisions::getMostRecentDivisionDate();

$data['comparison_party'] = $MEMBER->cohortParty();
$data['unslugified_comparison_party'] = ucwords(str_replace('-', ' ', $data['comparison_party']));

// is the party we're comparing this MP to different from the party they're currently in?
$data['party_switcher'] = (slugify($data['current_party_comparison']) != slugify($data["comparison_party"]));

// Update the social image URL generation logic
switch ($pagetype) {
    case 'votes':
        $data['og_image'] = \MySociety\TheyWorkForYou\Url::generateSocialImageUrl($member_name, 'Voting Summaries', $data['current_assembly']);
        $policy_set = get_http_var('policy');

        $policiesList = new MySociety\TheyWorkForYou\Policies();
        $divisions = new MySociety\TheyWorkForYou\Divisions($MEMBER);
        // Generate voting segments
        $set_descriptions = $policiesList->getSetDescriptions();
        if ($policy_set && array_key_exists($policy_set, $set_descriptions)) {
            $sets = [$policy_set];
            $data['page_title'] = $set_descriptions[$policy_set] . ' ' . $title . ' - TheyWorkForYou';
            $data['meta_description'] = 'See how ' . $data['full_name'] . ' voted on ' . $set_descriptions[$policy_set];
            $data['single_policy_page'] = true;
        } else {
            $data['single_policy_page'] = false;
            $sets = [
                'social', 'foreignpolicy', 'welfare', 'taxation', 'business',
                'health', 'education', 'reform', 'home', 'environment',
                'transport', 'housing', 'misc',
            ];
            $sets = array_filter($sets, function ($v) use ($set_descriptions) {
                return array_key_exists($v, $set_descriptions);
            });
            shuffle($sets);
        }
        $house = HOUSE_TYPE_COMMONS;
        $party = new MySociety\TheyWorkForYou\Party($MEMBER->party());
        $voting_comparison_period_slug = get_http_var('comparison_period') ?: 'all_time';
        $voting_comparison_period = new PolicyComparisonPeriod($voting_comparison_period_slug, $house);
        $cohort_party = $MEMBER->cohortParty();

        // this comes up if the votes page is being accessed for an old MP/Lord without party information.
        // by definition, not covered by our voting comparisons so just return an empty array.
        if ($cohort_party == null) {
            $data['key_votes_segments'] = [];
        } else {
            $data['key_votes_segments'] = PolicyDistributionCollection::getPersonDistributions($sets, $MEMBER->person_id(), $cohort_party, $voting_comparison_period->slug, $house);
        }

        $data["comparison_period"] = $voting_comparison_period;
        $data['available_periods'] = PolicyComparisonPeriod::getComparisonPeriodsForPerson($MEMBER->person_id(), $house);
        // shuffle the key_votes_segments for a random order
        shuffle($data['key_votes_segments']);
        $data["sig_diff_policy"] = PolicyDistributionCollection::getSignificantDistributions($data['key_votes_segments']);
        $data['party_member_count'] = $party->getCurrentMemberCount($house);

        // Send the output for rendering
        MySociety\TheyWorkForYou\Renderer::output('mp/votes', $data);

        break;

    case 'recent':
        $data['og_image'] = \MySociety\TheyWorkForYou\Url::generateSocialImageUrl($member_name, 'Recent Votes', $data['current_assembly']);
        $divisions = new MySociety\TheyWorkForYou\Divisions($MEMBER);
        $data['divisions'] = $divisions->getRecentMemberDivisions();
        MySociety\TheyWorkForYou\Renderer::output('mp/recent', $data);
        break;

    case 'memberships':
        $data['og_image'] = \MySociety\TheyWorkForYou\Url::generateSocialImageUrl($member_name, 'Committees, Memberships and Signatures', $data['current_assembly']);
        MySociety\TheyWorkForYou\Renderer::output('mp/memberships', $data);
        break;

    case 'election_register':
        // Send the output for rendering

        $memcache = new \MySociety\TheyWorkForYou\Memcache();
        $mem_key = "highlighted_interests" . $MEMBER->person_id();

        $highlighted_for_this_mp = $memcache->get($mem_key);

        if (!$highlighted_for_this_mp) {
            $highlighted_register = MySociety\TheyWorkForYou\DataClass\Regmem\Register::getMisc("highlighted_interests.json");
            $str_id = "uk.org.publicwhip/person/" . $MEMBER->person_id();
            $highlighted_for_this_mp = $highlighted_register->getPersonFromId($str_id);
            $memcache->set($mem_key, $highlighted_for_this_mp, 60 * 60 * 24);
        }

        $data['og_image'] = \MySociety\TheyWorkForYou\Url::generateSocialImageUrl($member_name, 'Election Register', $data['current_assembly']);
        $data['mp_has_highlighted_interests'] = (bool) $highlighted_for_this_mp;
        $overlapping_interests = [];

        MySociety\TheyWorkForYou\Renderer::output('mp/election_register', $data);

        // no break
    case 'register':
        $data['og_image'] = \MySociety\TheyWorkForYou\Url::generateSocialImageUrl($member_name, 'Register of Interests', $data['current_assembly']);
        // Send the output for rendering
        MySociety\TheyWorkForYou\Renderer::output('mp/register', $data);

        // no break
    case '':
    default:
        $data['og_image'] = \MySociety\TheyWorkForYou\Url::generateSocialImageUrl($member_name, 'Profile', $data['current_assembly']);
        // if extra detail needed for overview page in future

        // Send the output for rendering
        MySociety\TheyWorkForYou\Renderer::output('mp/profile', $data);

        break;

}


/////////////////////////////////////////////////////////
// SUPPORTING FUNCTIONS

/* Person lookup functions */

function get_person_by_id($pid) {
    global $pagetype, $this_page;
    $MEMBER = new MySociety\TheyWorkForYou\Member(['person_id' => $pid]);
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
    $MEMBER = new MySociety\TheyWorkForYou\Member(['member_id' => $member_id]);
    member_redirect($MEMBER);
}

function get_person_by_postcode($pc) {
    global $THEUSER;
    $pc = preg_replace('#[^a-z0-9]#i', '', $pc);
    if (!validate_postcode($pc)) {
        twfy_debug('MP', "Can't display an MP because the submitted postcode wasn't of a valid form.");
        throw new MySociety\TheyWorkForYou\MemberException(sprintf(gettext('Sorry, %s isn’t a valid postcode'), _htmlentities($pc)));
    }
    twfy_debug('MP', "MP lookup by postcode");
    $constituency = strtolower(MySociety\TheyWorkForYou\Utility\Postcode::postcodeToConstituency($pc));
    if ($constituency == "connection_timed_out") {
        throw new MySociety\TheyWorkForYou\MemberException(gettext('Sorry, we couldn’t check your postcode right now, as our postcode lookup server is under quite a lot of load.'));
    } elseif ($constituency == "") {
        twfy_debug('MP', "Can't display an MP, as submitted postcode didn't match a constituency");
        throw new MySociety\TheyWorkForYou\MemberException(sprintf(gettext('Sorry, %s isn’t a known postcode'), _htmlentities($pc)));
    } else {
        // Redirect to the canonical MP page, with a person id.
        $MEMBER = new MySociety\TheyWorkForYou\Member(['constituency' => $constituency, 'house' => HOUSE_TYPE_COMMONS]);
        if ($MEMBER->person_id()) {
            // This will cookie the postcode.
            $THEUSER->set_postcode_cookie($pc);
        }
        member_redirect($MEMBER, 302);
    }
}

function get_person_by_name($name, $const = '') {
    $MEMBER = new MySociety\TheyWorkForYou\Member(['name' => $name, 'constituency' => $const]);
    // Edge case, only attempt further detection if this isn't the Queen.
    if (($name !== 'elizabeth the second' && $name !== 'prince charles') || $const) {
        twfy_debug('MP', 'Redirecting for MP found by name/constituency');
        member_redirect($MEMBER);
    }
    return $MEMBER;
}

function get_mp_by_constituency($constituency) {
    $MEMBER = new MySociety\TheyWorkForYou\Member(['constituency' => $constituency, 'house' => HOUSE_TYPE_COMMONS]);
    member_redirect($MEMBER);
}

function get_regional_by_user_postcode($pc, $page) {
    global $this_page;
    $this_page = "your$page";
    $areas = \MySociety\TheyWorkForYou\Utility\Postcode::postcodeToConstituencies($pc);
    if ($page == 'msp' && isset($areas['SPC'])) {
        regional_list($pc, 'SPC', $page);
    } elseif ($page == 'ms' && isset($areas['WAC'])) {
        regional_list($pc, 'WAC', $page);
    } elseif ($page == 'mla' && isset($areas['NIE'])) {
        regional_list($pc, 'NIE', $page);
    } else {
        throw new MySociety\TheyWorkForYou\MemberException('Your set postcode is not in the right region.');
    }
}

function get_mp_by_user_postcode($pc) {
    $MEMBER = new MySociety\TheyWorkForYou\Member(['postcode' => $pc, 'house' => HOUSE_TYPE_COMMONS]);
    member_redirect($MEMBER, 302);
}

/**
 * Member Redirect
 *
 * Redirect to the canonical page for a member.
 */

function member_redirect(&$MEMBER, $code = 301, $pagetype = null) {
    // We come here after creating a MEMBER object by various methods.
    // Now we redirect to the canonical MP page, with a person_id.
    if ($MEMBER->person_id()) {
        $url = $MEMBER->url();
        $params = [];
        foreach ($_GET as $key => $value) {
            if (substr($key, 0, 4) == 'utm_' || $key == 'gclid') {
                $params[] = urlencode($key) . "=" . urlencode($value);
            }
        }
        if ($pagetype) {
            $url .= '/' . $pagetype;
        }
        if (count($params)) {
            $url .= '?' . join('&', $params);
        }
        header('Location: ' . $url, true, $code);
        exit;
    }
}

/* Error list page */

function person_list_page($ids) {
    global $name;
    if (!DEVSITE) {
        header('Cache-Control: max-age=900');
    }
    $data = ['mps' => []];
    foreach ($ids as $id => $constituency) {
        $data['mps'][] = [
            'url'  => WEBPATH . 'mp/?pid=' . $id,
            'name' => ucwords(strtolower($name)) . ', ' . $constituency,
        ];
    }
    $MPSURL = new \MySociety\TheyWorkForYou\Url('mps');
    $data['all_mps_url'] = $MPSURL->generate();
    MySociety\TheyWorkForYou\Renderer::output('mp/list', $data);
}

/* Error page */

function person_error_page($message) {
    global $this_page;
    $SEARCHURL = '';
    switch($this_page) {
        case 'peer':
            $people = new MySociety\TheyWorkForYou\People\Peers();
            $MPSURL = new \MySociety\TheyWorkForYou\Url('peers');
            break;
        case 'mla':
            $people = new MySociety\TheyWorkForYou\People\MLAs();
            $SEARCHURL = '/postcode/';
            $MPSURL = new \MySociety\TheyWorkForYou\Url('mlas');
            break;
        case 'msp':
            $people = new MySociety\TheyWorkForYou\People\MSPs();
            $SEARCHURL = '/postcode/';
            $MPSURL = new \MySociety\TheyWorkForYou\Url('msps');
            break;
        case 'ms':
            $people = new MySociety\TheyWorkForYou\People\MSs();
            $SEARCHURL = '/postcode/';
            $MPSURL = new \MySociety\TheyWorkForYou\Url('mss');
            break;
        case 'london-assembly-member':
            $people = new MySociety\TheyWorkForYou\People\LondonAssemblyMembers();
            $MPSURL = new \MySociety\TheyWorkForYou\Url('london-assembly-members');
            break;
        default:
            $people = new MySociety\TheyWorkForYou\People\MPs();
            $SEARCHURL = new \MySociety\TheyWorkForYou\Url('mp');
            $SEARCHURL = $SEARCHURL->generate();
            $MPSURL = new \MySociety\TheyWorkForYou\Url('mps');
    }

    $data = [
        'error' => $message,
        'rep_name' => $people->rep_name,
        'rep_name_plural' => $people->rep_plural,
        'all_mps_url' => $MPSURL->generate(),
        'rep_search_url' => $SEARCHURL,
    ];
    MySociety\TheyWorkForYou\Renderer::output('mp/error', $data);
}

/**
 * Person Positions Summary
 *
 * Generate the summary of this person's held positions.
 */

function person_summary_description($MEMBER) {
    $entered_house = $MEMBER->entered_house();
    $current_member = $MEMBER->current_member();
    $left_house = $MEMBER->left_house();

    if (in_array(HOUSE_TYPE_ROYAL, $MEMBER->houses())) {
        # Royal short-circuit
        if (substr($entered_house[HOUSE_TYPE_ROYAL]['date'], 0, 4) == 1952) {
            return '<strong>Acceded on ' . $entered_house[HOUSE_TYPE_ROYAL]['date_pretty']
                . '<br>Coronated on 2 June 1953</strong></li>';
        } else {
            return '';
        }
    }
    $desc = '';
    foreach ($MEMBER->houses() as $house) {
        if ($house == HOUSE_TYPE_COMMONS && isset($entered_house[HOUSE_TYPE_LORDS])) {
            # Same info is printed further down
            continue;
        }

        $party = $left_house[$house]['party'];
        $party_br = '';
        if (preg_match('#^(.*?)\s*\((.*?)\)$#', $party, $m)) {
            $party_br = " ($m[2])";
            $party = $m[1];
        }
        $pparty = $party != 'unknown' ? _htmlentities($party) : '';

        if ($house != HOUSE_TYPE_LORDS) {
            if ($house == HOUSE_TYPE_COMMONS) {
                $type = gettext('<abbr title="Member of Parliament">MP</abbr>');
            } elseif ($house == HOUSE_TYPE_NI) {
                $type = gettext('<abbr title="Member of the Legislative Assembly">MLA</abbr>');
            } elseif ($house == HOUSE_TYPE_SCOTLAND) {
                $type = gettext('<abbr title="Member of the Scottish Parliament">MSP</abbr>');
            } elseif ($house == HOUSE_TYPE_WALES) {
                $type = gettext('<abbr title="Member of the Senedd">MS</abbr>');
            } elseif ($house == HOUSE_TYPE_LONDON_ASSEMBLY) {
                $type = gettext('Member of the London Assembly');
            }

            if ($party == 'Speaker' || $party == 'Deputy Speaker') {
                # XXX: Might go horribly wrong if something odd happens
                if ($party == 'Deputy Speaker') {
                    $last = end($MEMBER->other_parties);
                    $oparty = $last['from'];
                } else {
                    $oparty = '';
                }
                if ($current_member[$house]) {
                    $line = sprintf(gettext('%s, and %s %s for %s'), $pparty, $oparty, $type, $left_house[$house]['constituency']);
                } else {
                    $line = sprintf(gettext('Former %s, and %s %s for %s'), $pparty, $oparty, $type, $left_house[$house]['constituency']);
                }
            } elseif ($current_member[$house]) {
                $line = sprintf(gettext('%s %s %s for %s'), $pparty, $type, $party_br, $left_house[$house]['constituency']);
            } else {
                $line = sprintf(gettext('Former %s %s %s for %s'), $pparty, $type, $party_br, $left_house[$house]['constituency']);
            }
        } elseif ($house == HOUSE_TYPE_LORDS && $party != 'Bishop') {
            if ($current_member[$house]) {
                $line = sprintf(gettext('%s Peer'), $pparty);
            } else {
                $line = sprintf(gettext('Former %s Peer'), $pparty);
            }
        } else {
            if ($current_member[$house]) {
                $line = $pparty;
            } else {
                $line = sprintf(gettext('Former %s'), $pparty);
            }
        }
        $desc .= $line . ', ';
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

function person_rebellion_rate($member) {

    // Rebellion string may be empty.
    $rebellion_string = '';

    if (isset($member->extra_info['party_vote_alignment_last_year'])) {

        // unserialise the data from json
        $data = json_decode($member->extra_info['party_vote_alignment_last_year'], true);
        $total_votes = $data['total_votes'];
        $avg_diff_from_party = $data['avg_diff_from_party'];

        // as int %
        $avg_diff_str = number_format((1 - $avg_diff_from_party) * 100, 0) . '%';

        if ($total_votes == 0) {
            return '';
        }
        $votes_help_url = TWFY_VOTES_URL . "/help/about#voting-breakdowns-and-party-alignment";

        $rebellion_string .= 'In the last year, ' . $member->full_name() . ' has an alignment score of ' . $avg_diff_str . ' with other MPs of their party (over ' . $total_votes . ' votes).';
        $rebellion_string .= ' <small><a title="More about party alignment" href="' . $votes_help_url . '">Find out more</a>.</small>';
    }
    return $rebellion_string;
}

function person_recent_appearances($member) {
    global $DATA, $SEARCHENGINE, $this_page;

    $out = [];
    $out['appearances'] = [];

    //$this->block_start(array('id'=>'hansard', 'title'=>$title));
    // This is really far from ideal - I don't really want $PAGE to know
    // anything about HANSARDLIST / DEBATELIST / WRANSLIST.
    // But doing this any other way is going to be a lot more work for little
    // benefit unfortunately.
    twfy_debug_timestamp();

    $person_id = $member->person_id();

    $memcache = new MySociety\TheyWorkForYou\Memcache();
    $recent = $memcache->get("recent_appear:$person_id:" . LANGUAGE);

    if (!$recent) {
        // Initialise the search engine
        $searchstring = "speaker:$person_id";
        $SEARCHENGINE = new \SEARCHENGINE($searchstring);

        $hansard = new MySociety\TheyWorkForYou\Hansard();
        $args =  [
            's' => $searchstring,
            'p' => 1,
            'num' => 3,
            'pop' => 1,
            'o' => 'd',
        ];
        $results = $hansard->search($searchstring, $args);
        $recent = serialize($results['rows']);
        $memcache->set('recent_appear:' . $person_id, $recent);
    }
    $out['appearances'] = unserialize($recent);
    twfy_debug_timestamp();

    $MOREURL = new \MySociety\TheyWorkForYou\Url('search');
    $MOREURL->insert(['pid' => $person_id, 'pop' => 1]);

    $out['more_href'] = $MOREURL->generate() . '#n4';
    $out['more_text'] = sprintf(gettext('More of %s’s recent appearances'), ucfirst($member->full_name()));

    if ($rssurl = $DATA->page_metadata($this_page, 'rss')) {
        // If we set an RSS feed for this page.
        $HELPURL = new \MySociety\TheyWorkForYou\Url('help');
        $out['additional_links'] = '<a href="' . WEBPATH . $rssurl . '" title="XML version of this person&rsquo;s recent appearances">RSS feed</a> (<a href="' . $HELPURL->generate() . '#rss" title="An explanation of what RSS feeds are for">?</a>)';
    }

    return $out;

}

function person_useful_links($member) {

    $links = $member->extra_info();

    $out = [];

    if (isset($links['maiden_speech'])) {
        $maiden_speech = fix_gid_from_db($links['maiden_speech']);
        $out[] = [
            'href' => WEBPATH . 'debate/?id=' . $maiden_speech,
            'text' => 'Maiden speech',
        ];
    }

    // BIOGRAPHY.
    global $THEUSER;
    if (isset($links['mp_website'])) {
        $out[] = [
            'href' => $links['mp_website'],
            'text' => 'Personal website',
        ];
    }

    if (isset($links['sp_url'])) {
        $out[] = [
            'href' => $links['sp_url'],
            'text' => 'Page on the Scottish Parliament website',
        ];
    }

    if (isset($links['wikipedia_url'])) {
        $out[] = [
            'href' => $links['wikipedia_url'],
            'text' => 'Wikipedia page',
        ];
    }

    if (isset($links['bbc_profile_url'])) {
        $out[] = [
            'href' => $links['bbc_profile_url'],
            'text' => 'BBC News profile',
        ];
    }

    if (isset($links['diocese_url'])) {
        $out[] = [
            'href' => $links['diocese_url'],
            'text' => 'Diocese website',
        ];
    }

    return $out;
}

function person_social_links($member) {

    $links = $member->extra_info();

    $out = [];


    if (isset($links['bluesky_handle'])) {
        $out[] = [
            'href' => 'https://bsky.app/profile/' . _htmlentities($links['bluesky_handle']),
            'text' => '@' . _htmlentities($links['bluesky_handle']),
            'type' => 'bluesky',
        ];
    }

    if (isset($links['twitter_username'])) {
        $out[] = [
            'href' => 'https://twitter.com/' . _htmlentities($links['twitter_username']),
            'text' => '@' . _htmlentities($links['twitter_username']),
            'type' => 'twitter',
        ];
    }

    if (isset($links['facebook_page'])) {
        $out[] = [
            'href' => _htmlentities($links['facebook_page']),
            'text' => _htmlentities("Facebook"),
            'type' => 'facebook',
        ];
    }

    $official_keys = [
        'profile_url_uk_parl' => 'UK Parliament Profile',
        'profile_url_scot_parl' => 'Scottish Parliament Profile',
        'profile_url_ni_assembly' => 'Northern Ireland Assembly Profile',
    ];

    if (LANGUAGE == 'cy') {
        $official_keys['profile_url_senedd_cy'] = 'Proffil Senedd';
    } else {
        $official_keys['profile_url_senedd_en'] = 'Senedd Profile';
    }

    foreach ($official_keys as $key => $text) {
        if (isset($links[$key])) {
            $out[] = [
                'href' => $links[$key],
                'text' => $text,
                'type' => 'official',
            ];
        }
    }

    return $out;
}

function person_topics($member) {
    $out = [];

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

function person_appg_memberships($member) {
    $out = [];

    $extra_info = $member->extra_info();
    if (isset($extra_info['appg_membership'])) {
        $out = MySociety\TheyWorkForYou\DataClass\APPGs\APPGMembershipAssignment::fromJson($extra_info['appg_membership']);
    }

    return $out;
}

function person_statements($member) {
    $out = [
        "edms" => null,
        "letter" => null,
    ];

    $extra_info = $member->extra_info();
    if (isset($extra_info['edms_signed'])) {
        $out["edms"] = MySociety\TheyWorkForYou\DataClass\Statements\SignatureList::fromJson($extra_info['edms_signed']);
    }
    if (isset($extra_info['letters_signed'])) {
        $out["letters"] = MySociety\TheyWorkForYou\DataClass\Statements\SignatureList::fromJson($extra_info['letters_signed']);
    }

    return $out;
}

function memberships($member) {
    $out = [];

    $committee_lookup = MySociety\TheyWorkForYou\DataClass\Groups\MiniGroupList::uk_committees();

    $topics = person_topics($member);
    if ($topics) {
        $out['topics'] = $topics;
    }

    $posts = $member->offices('current', false, true);
    if ($posts) {
        // for each post we want to add the description and external_url from the committee lookup if possible
        foreach ($posts as $post) {
            $committee = $committee_lookup->findByName($post->dept);
            if ($committee) {
                $post->desc = $committee->description;
                $post->external_url = $committee->external_url;
            }
        }
        $out['posts'] = $posts;
    }

    $posts = $member->offices('previous', false, true);
    if ($posts) {
        $out['previous_posts'] = $posts;
    }

    $eu_stance = $member->getEUStance();
    if ($eu_stance) {
        $out['eu_stance'] = $eu_stance;
    }

    $topics_of_interest = person_topics($member);
    if ($topics_of_interest) {
        $out['topics_of_interest'] = $topics_of_interest;
    }

    $appg_membership = person_appg_memberships($member);
    if ($appg_membership) {
        $out['appg_membership'] = $appg_membership;
    }

    $statments_signed = person_statements($member);
    if ($statments_signed) {
        if (isset($statments_signed["edms"]) && $statments_signed["edms"]->count() > 0) {
            $out['edms_signed'] = $statments_signed["edms"];
        }
        if (isset($statments_signed["letters"]) && $statments_signed["letters"]->count() > 0) {
            $out['letters_signed'] = $statments_signed["letters"];
        }
    }

    return $out;
}

function constituency_previous_mps($member) {
    if ($member->house(HOUSE_TYPE_COMMONS)) {
        return $member->previous_mps();
    } else {
        return [];
    }
}

function constituency_future_mps($member) {
    if ($member->house(HOUSE_TYPE_COMMONS)) {
        return $member->future_mps();
    } else {
        return [];
    }
}

function person_pbc_membership($member) {

    $extra_info = $member->extra_info();
    $out = ['info' => '', 'data' => []];

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
            $out['data'][] = [
                'href'      => '/pbc/' . $arr['session'] . '/' . urlencode($arr['title']),
                'text'      => $text,
                'attending' => $arr['attending'] . ' out of ' . $arr['outof'],
            ];
        }
    }

    return $out;
}

function person_register_interests_from_key($key, $extra_info): ?MySociety\TheyWorkForYou\DataClass\Regmem\Person {
    $lang = LANGUAGE;
    $reg = null;
    if (isset($extra_info[$key])) {
        $reg = MySociety\TheyWorkForYou\DataClass\Regmem\Person::fromJson($extra_info[$key]);
    }
    return $reg;
}

function person_register_interests($member, $extra_info) {

    $valid_chambers = ['house-of-commons', 'scottish-parliament', 'northern-ireland-assembly', 'senedd'];

    $lang = LANGUAGE;

    $reg = ['chamber_registers' => [] ];

    foreach ($valid_chambers as $chamber) {
        $key = 'person_regmem_' . $chamber . '_' . $lang;
        $chamber_register = person_register_interests_from_key($key, $extra_info);
        if ($chamber_register) {
            $reg['chamber_registers'][$chamber] = $chamber_register;
        }
    }
    // if chamber_registers is empty, we don't have any data
    if (empty($reg['chamber_registers'])) {
        return;
    }

    // sort chamber registers by published_date
    uasort($reg['chamber_registers'], function ($a, $b) {
        return $a->published_date <=> $b->published_date;
    });

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
    $db = new ParlDB();
    $query_base = "SELECT member.person_id, given_name, family_name, constituency, house
        FROM member, person_names pn
        WHERE constituency IN ('" . join("','", $a) . "')
            AND member.person_id = pn.person_id AND pn.type = 'name'
            AND pn.end_date = (SELECT MAX(end_date) FROM person_names WHERE person_names.person_id = member.person_id)";
    $q = $db->query($query_base . " AND left_reason = 'still_in_office' AND house in (" . HOUSE_TYPE_NI . "," . HOUSE_TYPE_SCOTLAND . "," . HOUSE_TYPE_WALES . ")");
    $current = true;
    if (!$q->rows() && ($dissolution = MySociety\TheyWorkForYou\Dissolution::db())) {
        $current = false;
        $q = $db->query(
            $query_base . " AND $dissolution[query]",
            $dissolution['params']
        );
    }
    $mcon = [];
    $mreg = [];
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
        } elseif ($house == HOUSE_TYPE_WALES) {
            if ($cons == $constituencies['WAC']) {
                $mcon = $row;
            } elseif ($cons == $constituencies['WAE']) {
                $mreg[] = $row;
            }
        } else {
            throw new MySociety\TheyWorkForYou\MemberException('Odd result returned!' . $house);
        }
    }
    if ($rep_type == 'msp') {
        $name = $mcon['given_name'] . ' ' . $mcon['family_name'];
        $cons = $mcon['constituency'];
        $reg = $constituencies['SPE'];
        $url = '/msp/?p=' . $mcon['person_id'];
        if ($current) {
            $data['members_statement'] = '<p>You have one constituency MSP (Member of the Scottish Parliament) and multiple region MSPs.</p>';
            $data['members_statement'] .= '<p>' . sprintf('Your <strong>constituency MSP</strong> is <a href="%s">%s</a>, MSP for %s.', $url, $name, $cons) . '</p>';
            $data['members_statement'] .= '<p>' . sprintf('Your <strong>%s region MSPs</strong> are:', $reg) . '</p>';
        } else {
            $data['members_statement'] = '<p>' . 'You had one constituency MSP (Member of the Scottish Parliament) and multiple region MSPs.' . '</p>';
            $data['members_statement'] .= '<p>' . sprintf('Your <strong>constituency MSP</strong> was <a href="%s">%s</a>, MSP for %s.', $url, $name, $cons) . '</p>';
            $data['members_statement'] .= '<p>' . sprintf('Your <strong>%s region MSPs</strong> were:', $reg) . '</p>';
        }
    } elseif ($rep_type == 'ms') {
        $name = $mcon['given_name'] . ' ' . $mcon['family_name'];
        $cons = gettext($mcon['constituency']);
        $reg = gettext($constituencies['WAE']);
        $url = '/ms/?p=' . $mcon['person_id'];
        if ($current) {
            $data['members_statement'] = '<p>' . gettext('You have one constituency MS (Member of the Senedd) and multiple region MSs.') . '</p>';
            $data['members_statement'] .= '<p>' . sprintf(gettext('Your <strong>constituency MS</strong> is <a href="%s">%s</a>, MS for %s.'), $url, $name, $cons) . '</p>';
            $data['members_statement'] .= '<p>' . sprintf(gettext('Your <strong>%s region MSs</strong> are:'), $reg) . '</p>';
        } else {
            $data['members_statement'] = '<p>' . gettext('You had one constituency MS (Member of the Senedd) and multiple region MSs.') . '</p>';
            $data['members_statement'] .= '<p>' . sprintf(gettext('Your <strong>constituency MS</strong> was <a href="%s">%s</a>, MS for %s.'), $url, $name, $cons) . '</p>';
            $data['members_statement'] .= '<p>' . sprintf(gettext('Your <strong>%s region MSs</strong> were:'), $reg) . '</p>';
        }
    } else {
        if ($current) {
            $data['members_statement'] = '<p>You have multiple MLAs (Members of the Legislative Assembly) who represent you in ' . $constituencies['NIE'] . '. They are:</p>';
        } else {
            $data['members_statement'] = '<p>You had multiple MLAs (Members of the Legislative Assembly) who represented you in ' . $constituencies['NIE'] . '. They were:</p>';
        }
    }

    foreach($mreg as $reg) {
        $data['members'][] =  [
            'url' => '/' . $rep_type . '/?p=' . $reg['person_id'],
            'name' => $reg['given_name'] . ' ' . $reg['family_name'],
        ];

    }

    // Send the output for rendering
    MySociety\TheyWorkForYou\Renderer::output('mp/regional_list', $data);

}

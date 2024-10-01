<?php

include_once '../../includes/easyparliament/init.php';
include_once INCLUDESPATH . 'easyparliament/member.php';

$this_page = 'divisions_vote';

$vote = get_http_var('vote');

if (!$vote) {
    $PAGE->error_message("No vote specified", true);
    exit();
}

$divisions = new MySociety\TheyWorkForYou\Divisions();
$division_votes = $divisions->getDivisionResults($vote);
[$country, $location, $assembly, $cons_type, $assembly_name] = MySociety\TheyWorkForYou\Utility\House::getCountryDetails($division_votes['house_number']);

$main_vote_mp = false;
if ($mp = get_http_var('p')) {
    $MEMBER = new MySociety\TheyWorkForYou\Member(['person_id' => $mp, 'house' => $division_votes['house_number']]);
    $main_vote_mp = true;
} elseif ($THEUSER->postcode_is_set() && $cons_type == 'WMC') {
    $MEMBER = new MySociety\TheyWorkForYou\Member(['postcode' => $THEUSER->postcode(), 'house' => HOUSE_TYPE_COMMONS]);
}

$data = ['division' => $division_votes];

if (!$data['division']) {
    $PAGE->error_message("Vote not found", true);
    exit();
}

$template = 'divisions/vote';
$DATA->set_page_metadata($this_page, 'title', $data['division']['division_title']);

$data['main_vote_mp'] = $main_vote_mp;

# We don't want a "Your MP" box on PBC votes
if (isset($MEMBER) && $division_votes['house'] != 'pbc') {
    $mp_vote = $divisions->getDivisionResultsForMember($vote, $MEMBER->person_id());
    if ($mp_vote) {
        $data['mp_vote'] = $mp_vote;
        $data['mp_vote']['with_majority'] = false;
        if ($data['mp_vote']['vote'] == $data['division']['vote']) {
            $data['mp_vote']['with_majority'] = true;
        }
    } else {
        if ($data['division']['date'] < $MEMBER->entered_house($division_votes['house_number'])['date']) {
            $data['before_mp'] = true;
        } elseif ($data['division']['date'] > $MEMBER->left_house($division_votes['house_number'])['date']) {
            $data['after_mp'] = true;
        }
    }

    $mp_data = [
        'name' => $MEMBER->full_name(),
        'party' => $MEMBER->party(),
        'constituency' => $MEMBER->constituency(),
        'former' => '',
        'mp_url' => $MEMBER->url(),
        'image' => $MEMBER->image()['url'],
    ];
    $left_house = $MEMBER->left_house();
    if ($left_house[$division_votes['house_number']]['date'] != '9999-12-31') {
        $mp_data['former'] = 'former';
    }
    $data['mp_data'] = $mp_data;

    if ($THEUSER->postcode_is_set() && $cons_type !== '') {
        $user = new MySociety\TheyWorkForYou\User();
        $data['mp_data']['change_url'] = $user->getPostCodeChangeURL();
        $data['mp_data']['postcode'] = $THEUSER->postcode();
    }
}

if ($data['division']['house'] == 'pbc') {
    $location = '&ndash; in a Public Bill Committee';
}
$data['debate_time_human'] = false;
$data['debate_day_human'] = format_date($data['division']['date'], LONGDATEFORMAT);
$data['location'] = $location;
$data['current_assembly'] = $assembly;
$data['assembly_name'] = $assembly_name;
$data['nextprev'] = ['up' => ['url' => '/divisions/', 'body' => 'Recent Votes']];

MySociety\TheyWorkForYou\Renderer::output($template, $data);

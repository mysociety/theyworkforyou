<?php

include_once '../../includes/easyparliament/init.php';
include_once INCLUDESPATH . 'easyparliament/member.php';


$MEMBER = null;
if ($THEUSER->postcode_is_set()) {
    $MEMBER = new MySociety\TheyWorkForYou\Member(array('postcode' => $THEUSER->postcode(), 'house' => HOUSE_TYPE_COMMONS));
}

$houses = get_http_var('house', '', true);

// Set to a special version of the page name to get right parliament menus
if ($houses == 'scotland') {
    $this_page = 'divisions_recent_sp';
} elseif ($houses == 'senedd') {
    $this_page = "divisions_recent_wales";
} elseif ($houses == 'commons') {
    $this_page = 'divisions_recent_commons';
} elseif ($houses == 'lords') {
    $this_page = 'divisions_recent_lords';
} else {
    $this_page = 'divisions_recent';
}

$divisions = new MySociety\TheyWorkForYou\Divisions($MEMBER);
$data = $divisions->getRecentDivisions(30, $houses);

if (isset($MEMBER)) {
    $data['mp_name'] = ucfirst($MEMBER->full_name());
}

$data['last_updated'] = MySociety\TheyWorkForYou\Divisions::getMostRecentDivisionDate()['latest'];
$data["houses"] = $houses;

$template = 'divisions/index';
MySociety\TheyWorkForYou\Renderer::output($template, $data);

<?php

include_once '../../includes/easyparliament/init.php';
include_once INCLUDESPATH . 'easyparliament/member.php';

$this_page = 'divisions_recent';

if ($THEUSER->postcode_is_set()) {
    $MEMBER = new MySociety\TheyWorkForYou\Member(array('postcode' => $THEUSER->postcode(), 'house' => HOUSE_TYPE_COMMONS));
}

if (isset($MEMBER)) {
  $divisions = new MySociety\TheyWorkForYou\Divisions($MEMBER);
  $data = array('divisions' => $divisions->getRecentMemberDivisions(30, 'Parliament'));
  $data['mp_name'] = ucfirst($MEMBER->full_name());
} else {
  $divisions = new MySociety\TheyWorkForYou\Divisions();
  $data = $divisions->getRecentDivisions(30);
}

$data['last_updated'] = MySociety\TheyWorkForYou\Divisions::getMostRecentDivisionDate()['latest'];

$template = 'divisions/index';

if ($data) {
    MySociety\TheyWorkForYou\Renderer::output($template, $data);
}

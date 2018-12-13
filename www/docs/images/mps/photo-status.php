<?php

include_once '../../../includes/easyparliament/init.php';
include_once INCLUDESPATH . "easyparliament/member.php";
$DATA->set_page_metadata($this_page, 'heading', 'MPs photo status on TheyWorkForYou');
$PAGE->page_start();
$PAGE->stripe_start();
$db = new ParlDB;
$query = 'SELECT person_id, constituency, party
    FROM member
    WHERE house=1 AND left_house = (SELECT MAX(left_house) FROM member) ';
$q = $db->query($query . "ORDER BY person_id");
$out = array('both'=>'', 'small'=>'', 'none'=>array());
foreach ($q as $row) {
    $p_id = $row['person_id'];
    list($dummy, $sz) = MySociety\TheyWorkForYou\Utility\Member::findMemberImage($p_id);
    if ($sz == 'L') {
        $out['both'] .= $row['person_id'] . ', ';
    } elseif ($sz == 'S') {
        $out['small'] .= $row['person_id'] . ', ';
    } else {
        array_push($out['none'], '<li>' . $row['person_id'] . ' (' . $row['party'] . ')' . ', ' . $row['constituency']);
    }
}
print '<h3>Missing completely ('.count($out['none']).')</h3> <ul>';
print join($out['none'], "\n");
print '</ul>';
print '<h3>Large and small</h3> <p>';
print $out['both'];
print '<h3>Only small photos</h3> <p>';
print $out['small'];
$PAGE->stripe_end();
$PAGE->page_end();

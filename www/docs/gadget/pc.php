<?php

# Given a postcode, return a person ID

include_once 'min-init.php';
include_once INCLUDESPATH . 'easyparliament/member.php';

$pc = $_GET['pc'];
$pc = preg_replace('#[^a-z0-9 ]#i', '', $pc);
if (\MySociety\TheyWorkForYou\Utility\Validation::validatePostcode($pc)) {
    $constituency = MySociety\TheyWorkForYou\Utility\Postcode::postcodeToConstituency($pc);
    if ($constituency == 'CONNECTION_TIMED_OUT') {
        error('Connection timed out');
    } elseif ($constituency) {
        $pid = get_person_id($constituency);
        echo 'pid,', $pid;
    } else {
        error('Unknown postcode');
    }
} else {
    error('Invalid postcode');
}

function error($s) {
    echo 'error,', $s;
}

function get_person_id($c) {
    $db = new ParlDB;
    if ($c == '') return false;
    if ($c == 'Orkney ') $c = 'Orkney &amp; Shetland';
    $n = MySociety\TheyWorkForYou\Utility\Constituencies::normaliseConstituencyName($c); if ($n) $c = $n;
    $q = $db->query("SELECT person_id FROM member
        WHERE constituency = :constituency
        AND left_reason = 'still_in_office' AND house=1", array(
            ':constituency' => $c
            ));
    if ($q->rows > 0)
        return $q->field(0, 'person_id');
    return false;
}

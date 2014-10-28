<?php

# Run from command line; to mark a few speeches as missing video at once

include_once "../www/includes/easyparliament/init.php";

$ARGV = $_SERVER['argv'];
$db = new \MySociety\TheyWorkForYou\ParlDb;

$from = isset($ARGV[1]) ? $ARGV[1] : '';
$to = isset($ARGV[2]) ? $ARGV[2] : '';

if (!$from) {
    print "Need a from!\n"; exit;
}

$date = substr($from, 0, 10);
$from = 'uk.org.publicwhip/debate/' . $from;
if ($to) $to = 'uk.org.publicwhip/debate/' . $to;

$q = $db->query('select hpos from hansard where gid="' . $from . '"');
$hpos_from = $q->field(0, 'hpos');
if (!$hpos_from) {
    print "No hpos for from gid!\n"; exit;
}

if ($to) {
    $q = $db->query('select hpos from hansard where gid="' . $to . '"');
    $hpos_to = $q->field(0, 'hpos');
    if (!$hpos_to) {
        print "No hpos for to gid!\n"; exit;
    }
}

$query = "update hansard set video_status = video_status | 8
    where major=1 and hdate='$date' and hpos>=$hpos_from";
if ($to)
    $query .= " and hpos<=$hpos_to";

print "Executing $query...\n";
$q = $db->query($query);
print '  ' . $q->affected_rows() . " rows have been marked as missing\n";

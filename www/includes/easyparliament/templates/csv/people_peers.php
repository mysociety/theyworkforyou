<?php

twfy_debug("TEMPLATE", "people_peers.php");

header('Content-Disposition: attachment; filename=Peers.csv');
header('Content-Type: text/csv');
print "Person ID,Name,Party,URI";
print "\r\n";

foreach ($data['data'] as $n => $peer) {
    render_peers_row($peer);
}

function render_peers_row($peer) {
    global $parties;
    $name = member_full_name(2, $peer['title'], $peer['first_name'], $peer['last_name'], $peer['constituency']);
    if (strstr($name, ',')) $name = "\"$name\"";
    print $peer['person_id'] . ',' . ucfirst($name) . ',';
    if (array_key_exists($peer['party'], $parties))
        print $parties[$peer['party']];
    else
        print $peer['party'];
    print ',' .  'http://www.theyworkforyou.com/peer/' .
        make_member_url($name, NULL, 2, $peer['person_id']);
    print "\r\n";
}

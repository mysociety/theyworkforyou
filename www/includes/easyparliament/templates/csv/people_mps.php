<?php

twfy_debug ("TEMPLATE", "people_mps.php");

$order = $data['info']['order'];

header('Content-Type: text/csv');
print "Person ID,First name,Last name,Party,Constituency,URI";
if ($order == 'debates') print ',Debates spoken in the last year';
print "\r\n";

foreach ($data['data'] as $n => $mp) {
    render_mps_row($mp, $order);
}

function render_mps_row($mp, $order) {
    global $parties;
    $con = $mp['constituency'];
    if (strstr($con, ',')) $con = "\"$con\"";
    print $mp['person_id'] . ',';
    print $mp['given_name'] . ',' . $mp['family_name'] . ',';
    if (array_key_exists($mp['party'], $parties))
        print $parties[$mp['party']];
    else
        print $mp['party'];
    print ',' . $con . ',' .  'http://www.theyworkforyou.com/mp/' . $mp['url'];
    if ($order == 'debates') print ',' . $mp['data_value'];
    print "\r\n";
}

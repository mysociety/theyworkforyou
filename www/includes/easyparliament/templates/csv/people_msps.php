<?php

twfy_debug("TEMPLATE", "people_msps.php");

header('Content-Type: text/csv');
print "Person ID,Name,Party,Constituency,URI";
print "\r\n";

foreach ($data['data'] as $n => $msp) {
    render_msps_row($msp);
}

function render_msps_row($msp) {
    global $parties;
    $con = $msp['constituency'];
    if (strstr($con, ',')) $con = "\"$con\"";
    $name = $msp['name'];
    if (strstr($name, ',')) $name = "\"$name\"";
    print $msp['person_id'] . ',' . ucfirst($name) . ',';
    if (array_key_exists($msp['party'], $parties))
        print $parties[$msp['party']];
    else
        print $msp['party'];
    print ',' . $con . ',' .  'http://www.theyworkforyou.com/msp/' . $msp['url'];
    print "\r\n";
}

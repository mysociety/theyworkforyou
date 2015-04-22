<?php

twfy_debug("TEMPLATE", "people_mlas.php");

header('Content-Type: text/csv');
print "Person ID,Name,Party,Constituency,URI";
print "\r\n";

foreach ($data['data'] as $n => $mla) {
    render_mlas_row($mla);
}

function render_mlas_row($mla) {
    global $parties;
    $con = $mla['constituency'];
    if (strstr($con, ',')) $con = "\"$con\"";
    $name = $mla['name'];
    if (strstr($name, ',')) $name = "\"$name\"";
    print $mla['person_id'] . ',' . ucfirst($name) . ',';
    if (array_key_exists($mla['party'], $parties))
        print $parties[$mla['party']];
    else
        print $mla['party'];
    print ',' . $con . ',' .  'http://www.theyworkforyou.com/mla/' . $mla['url'];
    print "\r\n";
}

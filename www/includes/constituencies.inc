<?php
# $Id: constituencies.inc,v 1.2 2006-08-31 13:20:48 twfy-live Exp $

function normalise_constituency_name($name) {
    $db = new ParlDB;

    // Use &amp; everywhere, as in DB
    $name = str_replace("&amp;", "&", $name);
    $name = str_replace("&", "&amp;", $name);

    $query = "select cons_id from constituency where name like '" . mysql_escape_string($name) . "' and from_date <= date(now()) and date(now()) <= to_date";
    $q1 = $db->query($query);
    if ($q1->rows <= 0)
        return false;

    $query = "select name from constituency where main_name and cons_id = '".$q1->field(0,'cons_id')."'";
    $q2 = $db->query($query);
    if ($q2->rows <= 0)
        return false;

    return $q2->field(0, "name");
}

# As I don't want to do 646*2 DB queries!
function normalise_constituency_names($names) {
	$db = new ParlDB;
	$q = $db->query('select constituency.name as name,c_main.name as canonical_name
		from constituency, constituency as c_main
		where constituency.cons_id = c_main.cons_id
		and c_main.main_name and constituency.name in ("' . join('","', array_values($names)) .
		'") and constituency.from_date <= date(now())
		and date(now()) <= constituency.to_date');
	$lookup = array();
	for ($i=0; $i<$q->rows(); $i++) {
		$name = html_entity_decode($q->field($i, 'name'));
		$canonical = html_entity_decode($q->field($i, 'canonical_name'));
		$lookup[$name] = $canonical;
	}
	$output = array();
	foreach ($names as $area_id => $name) {
		$output[$area_id] = isset($lookup[$name]) ? $lookup[$name] : $name;
	}
	return $output;
}

?>

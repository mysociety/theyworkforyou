<?

/* Shared API functions for get<Members> */

function _api_getMembers_output($sql) {
	global $parties;
	$db = new ParlDB;
	$q = $db->query($sql);
	$output = array();
	$last_mod = 0;
	for ($i=0; $i<$q->rows(); $i++) {
		$row = array(
			'member_id' => $q->field($i, 'member_id'),
			'person_id' => $q->field($i, 'person_id'),
			'name' => html_entity_decode(member_full_name($q->field($i, 'house'), $q->field($i, 'title'),
				$q->field($i, 'first_name'), $q->field($i, 'last_name'),
				$q->field($i, 'constituency') )),
			'party' => isset($parties[$q->field($i, 'party')]) ? $parties[$q->field($i, 'party')] : $q->field($i, 'party'),
		);
		if ($q->field($i, 'house') == 1)
			$row['constituency'] = html_entity_decode($q->field($i, 'constituency'));
		$output[] = $row;
		$time = strtotime($q->field($i, 'lastupdate'));
		if ($time > $last_mod)
			$last_mod = $time;
	}
	api_output($output, $last_mod);
}

function api_getMembers_party($house, $s) {
	global $parties;
	$canon_to_short = array_flip($parties);
	if (isset($canon_to_short[ucwords($s)]))
	    $s = $canon_to_short[ucwords($s)];
	_api_getMembers_output('select * from member
		where house = ' . mysql_escape_string($house) . '
		and party like "%' . mysql_escape_string($s) .
		'%" and entered_house <= date(now()) and date(now()) <= left_house');
}

function api_getMembers_search($house, $s) {
	$sq = mysql_escape_string($s);
	_api_getMembers_output('select * from member
		where house = ' . mysql_escape_string($house) . "
		and (first_name like '%$sq%'
		or last_name like '%$sq%'
		or concat(first_name,' ',last_name) like '%$sq%'
		or constituency like '%$sq%'
		) and entered_house <= date(now()) and date(now()) <= left_house");
}

function api_getMembers_date($house, $date) {
	if ($date = parse_date($date)) {
		api_getMembers($house, '"' . $date['iso'] . '"');
	} else {
		api_error('Invalid date format');
	}
}

function api_getMembers($house, $date = 'now()') {
	_api_getMembers_output('select * from member where house=' . mysql_escape_string($house) .
		' AND entered_house <= date('.$date.') and date('.$date.') <= left_house');
}

?>

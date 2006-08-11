<?

/* Shared API functions for get<Members> */

function _api_getMembers_output($sql) {
	global $parties;
	$db = new ParlDB;
	$q = $db->query($sql);
	$output['twfy']['matches'] = array();
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
		$output['twfy']['matches'][] = $row;
	}
	api_output($output);
}

function api_getMembers_party($house, $s) {
	_api_getMembers_output('select * from member
		where house = ' . mysql_escape_string($house) . '
		and party like "%' . mysql_escape_string($s) .
		'%" and entered_house <= date(now()) and date(now()) <= left_house');
}

function api_getMembers_search($house, $s) {
	_api_getMembers_output('select * from member
		where house = ' . mysql_escape_string($house) . '
		and (first_name like "%' . mysql_escape_string($s) .
		'%" or last_name like "%' . mysql_escape_string($s) . 
		'%" or constituency like "%' . mysql_escape_string($s) .
		'%") and entered_house <= date(now()) and date(now()) <= left_house');
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

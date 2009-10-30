<?

include_once INCLUDESPATH . 'easyparliament/member.php';
include_once 'api_getMP.php';
include_once 'api_getLord.php';
include_once 'api_getMSP.php';
include_once 'api_getMLA.php';
function api_getPerson_front() {
?>
<p><big>Fetch a particular person.</big></p>

<h4>Arguments</h4>
<dl>

<dt>id</dt>
<dd>If you know the person ID for the member you want (returned from getMPs or elsewhere), this will return data for that person.
This will return all database entries for this person, so will include previous elections, party changes, etc.</dd>
</dl>

<?	
}

function _api_getPerson_row($row, $has_party=FALSE){
  global $parties;
	$row['full_name'] = member_full_name($row['house'], $row['title'], $row['first_name'],
		$row['last_name'], $row['constituency']);
	if ($has_party && isset($parties[$row['party']]))
		$row['party'] = $parties[$row['party']];
	list($image,$sz) = find_rep_image($row['person_id']);
	if ($image) $row['image'] = $image;
	foreach ($row as $k => $r) {
		if (is_string($r)) $row[$k] = html_entity_decode($r);
	}
	return $row;
}

function _api_getRoyal_row($row) {
  return _api_getPerson_row($row, $has_party=FALSE);
}

function api_getPerson_id($id) {
	$db = new ParlDB;
	$q = $db->query("select * from member
		where person_id = '" . mysql_escape_string($id) . "'
		order by left_house desc");
	if ($q->rows()) {
		$output = array();
		$last_mod = 0;
		for ($i=0; $i<$q->rows(); $i++) {
		  $house = $q->field($i, 'house');
		  if ($house == 0)
			  $out = _api_getRoyal_row($q->row($i));
		  else if ($house == 1)
			  $out = _api_getMP_row($q->row($i));
			else if ($house == 2)
			  $out = _api_getLord_row($q->row($i));
			else if ($house == 3)
			  $out = _api_getMLA_row($q->row($i));
			else if ($house == 4)
			  $out = _api_getMSP_row($q->row($i));
			$output[] = $out;
			$time = strtotime($q->field($i, 'lastupdate'));
			if ($time > $last_mod)
				$last_mod = $time;
		}
		api_output($output, $last_mod);
	} else {
		api_error('Unknown person ID');
	}
}

?>

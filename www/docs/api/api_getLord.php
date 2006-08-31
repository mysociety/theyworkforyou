<?

function api_getLord_front() {
?>
<p><big>Fetch a particular Lord.</big></p>

<h4>Arguments</h4>
<dl>
<dt>id (optional)</dt>
<dd>If you know the person ID for the Lord you want, this will return data for that person.</dd>
</dl>

<?	
}

function _api_getLord_row($row) {
	global $parties;
	$row['full_name'] = member_full_name($row['house'], $row['title'], $row['first_name'],
		$row['last_name'], $row['constituency']);
	if (isset($parties[$row['party']]))
		$row['party'] = $parties[$row['party']];
	$row = array_map('html_entity_decode', $row);
	return $row;
}

function api_getLord_id($id) {
	$db = new ParlDB;
	$q = $db->query("select * from member
		where house=2 and person_id = '" . mysql_escape_string($id) . "'
		order by left_house desc");
	if ($q->rows()) {
		$output = array();
		for ($i=0; $i<$q->rows(); $i++) {
			$out = _api_getLord_row($q->row($i));
			$output[] = $out;
		}
		api_output($output);
	} else {
		api_error('Unknown person ID');
	}
}

?>

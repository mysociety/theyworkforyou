<?

function api_getConstituencies_front() {
?>
<p><big>Fetch a list of constituencies.</big></p>

<h4>Arguments</h4>
<dl>
<dt>date (optional)</dt>
<dd>Fetch the list of constituencies as it was on this date.</dd>
<dt>search (optional)</dt>
<dd>Fetch the list of constituencies that match this search string.</dd>
</dl>

<h4>Example Response</h4>
<pre>{ twfy : {
	matches : [
		{ id : "1", name : "Aberavon" },
		{ id : "6", name : "Aldershot" },
		{ id : "7", name : "Aldridge-Brownhills" },
		...
	]
} }</pre>

<h4>Error Codes</h4>
<p></P>

<?	
}

function api_getconstituencies_search($s) {
	$db = new ParlDB;
	$q = $db->query('select * from constituency
		where main_name and name like "%' . mysql_escape_string($s) .
		'%" and from_date <= date(now()) and date(now()) <= to_date');
	$output['twfy']['matches'] = array();
	for ($i=0; $i<$q->rows(); $i++) {
		$output['twfy']['matches'][] = array(
			'id' => $q->field($i, 'cons_id'),
			'name' => html_entity_decode($q->field($i, 'name'))
		);
	}
	api_output($output);
}

function api_getconstituencies_date($date) {
	if ($date = parse_date($date)) {
		api_getconstituencies('"' . $date['iso'] . '"');
	} else {
		api_error('Invalid date format');
	}
}

function api_getconstituencies($date = 'now()') {
	$db = new ParlDB;
	$q = $db->query('select cons_id, name from constituency
		where main_name and from_date <= date('.$date.') and date('.$date.') <= to_date');
	$output['twfy']['matches'] = array();
	for ($i=0; $i<$q->rows(); $i++) {
		$output['twfy']['matches'][] = array(
			'id' => $q->field($i, 'cons_id'),
			'name' => html_entity_decode($q->field($i, 'name'))
		);
	}
	api_output($output);
}

?>

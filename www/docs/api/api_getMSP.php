<?

include_once INCLUDESPATH . 'easyparliament/member.php';

function api_getMSP_front() {
?>
<p><big>Fetch a particular MSP.</big></p>

<h4>Arguments</h4>
<dl>
<dt>postcode (optional)</dt>
<dd>Fetch the MSPs for a particular postcode.</dd>
<dt>constituency (optional)</dt>
<dd>The name of a constituency.</dd>
<dt>id (optional)</dt>
<dd>If you know the person ID for the member you want (returned from getMSPs or elsewhere), this will return data for that person.</dd>
</dl>

<h4>Example Response</h4>
<pre>&lt;twfy&gt;
  &lt;/twfy&gt;
</pre>

<?	
}

function _api_getMSP_row($row) {
	global $parties;
	$row['full_name'] = member_full_name($row['house'], $row['title'], $row['first_name'],
		$row['last_name'], $row['constituency']);
	if (isset($parties[$row['party']]))
		$row['party'] = $parties[$row['party']];
	list($image,$sz) = find_rep_image($row['person_id']);
	if ($image) $row['image'] = $image;
	$row = array_map('html_entity_decode', $row);
	return $row;
}

function api_getMSP_id($id) {
	$db = new ParlDB;
	$q = $db->query("select * from member
		where house=4 and person_id = '" . mysql_escape_string($id) . "'
		order by left_house desc");
	if ($q->rows()) {
		_api_getMSP_output($q);
	} else {
		api_error('Unknown person ID');
	}
}

function api_getMSP_postcode($pc) {
	$pc = preg_replace('#[^a-z0-9 ]#i', '', $pc);
	if (validate_postcode($pc)) {
		$constituencies = postcode_to_constituencies($pc);
		if ($constituencies == 'CONNECTION_TIMED_OUT') {
			api_error('Connection timed out');
		} elseif (isset($constituencies['SPC'])) {
			_api_getMSP_constituency($constituencies);
		} elseif (isset($constituencies['WMC'])) {
			api_error('Non-Scottish postcode');
		} else {
			api_error('Unknown postcode');
		}
	} else {
		api_error('Invalid postcode');
	}
}

function api_getMSP_constituency($constituency) {
	$output = _api_getMSP_constituency(array($constituency));
	if (!$output)
		api_error('Unknown constituency, or no MSP for that constituency');
}

# Very similary to MEMBER's constituency_to_person_id
# Should all be abstracted properly :-/
function _api_getMSP_constituency($constituencies) {
	$db = new ParlDB;

	$cons = array();
	foreach ($constituencies as $constituency) {
		if ($constituency == '') continue;
		if ($constituency == 'Orkney ')
			$constituency = 'Orkney &amp; Shetland';
		$cons[] = mysql_escape_string($constituency);
	}


	$q = $db->query("SELECT * FROM member
		WHERE constituency in ('" . join("','", $cons) . "')
		AND left_reason = 'still_in_office' AND house=4");
	if ($q->rows > 0) {
		_api_getMSP_output($q);
		return true;
	}

	return false;
}

function _api_getMSP_output($q) {
	$output = array();
	$last_mod = 0;
	for ($i=0; $i<$q->rows(); $i++) {
		$out = _api_getMSP_row($q->row($i));
		$output[] = $out;
		$time = strtotime($q->field($i, 'lastupdate'));
		if ($time > $last_mod)
			$last_mod = $time;
	}
	api_output($output, $last_mod);
}

<?

function api_getCommittee_front() {
?>
<p><big>Fetch the members of a Select Committee.</big></p>

<h4>Arguments</h4>
<dl>
<dt>name</dt>
<dd>Fetch the members of the committee that match this name - if more than one committee matches, return their names.</dd>
<dt>date (optional)</dt>
<dd>Return the members of the committee as they were on this date.</dd>
</dl>

<h4>Example Response</h4>
<pre>&lt;twfy&gt;
&lt;/twfy&gt;</pre>

<?	
}

function api_getCommittee_name($name) {
	$db = new ParlDB;

	$name = htmlspecialchars($name); # Names in the database have & as &amp;...
	$name = preg_replace('#\s+Committee#', '', $name);

	$date = parse_date(get_http_var('date'));
	if ($date) $date = '"' . $date['iso'] . '"';
	else $date = 'date(now())';
	$q = $db->query("select distinct(dept) from moffice
		where dept like '%" . mysql_escape_string($name) . "%Committee'
		and from_date <= " . $date . ' and '
		. $date . ' <= to_date');
	if ($q->rows() > 1) {
		# More than one committee matches
		for ($i=0; $i<$q->rows(); $i++) {
			$output['twfy']['committees'][] = array(
				'name' => html_entity_decode($q->field($i, 'dept'))
			);
		}
		api_output($output);
	} elseif ($q->rows()) {
		# One committee
		$q = $db->query("select * from moffice,member
			where moffice.person = member.person_id
			and dept like '%" . mysql_escape_string($name) . "%Committee'
			and from_date <= " . $date . ' and ' . $date . " <= to_date
			and entered_house <= " . $date . ' and ' . $date . ' <= left_house');
		if ($q->rows()) {
			$output = array();
			$output['twfy']['committee'] = html_entity_decode($q->field(0, 'dept'));
			for ($i=0; $i<$q->rows(); $i++) {
				$member = array(
					'person_id' => $q->field($i, 'person'),
					'name' => $q->field($i, 'first_name') . ' ' . $q->field($i, 'last_name'),
				);
				if ($q->field($i, 'position') == 'Chairman') {
					$member['position'] = $q->field($i, 'position');
				}
				$output['twfy']['members'][] = $member;
			}
			api_output($output);
		} else {
			api_error('That committee has no members...?');
		}
	} else {
		api_error('That name was not recognised');
	}
}


function api_getCommittee_date($date) {
	api_error('You need to give a name!');
}

?>

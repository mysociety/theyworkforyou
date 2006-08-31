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

<h4>Example responses</h4>

<pre>{ "committees" : [
	{ "name" : "Scottish Affairs Committee" },
	{ "name" : "Northern Ireland Affairs Committee" },
	{ "name" : "Home Affairs Committee" },
	{ "name" : "Constitutional Affairs Committee" },
	{ "name" : "Environment, Food and Rural Affairs Committee" },
	{ "name" : "Foreign Affairs Committee" },
	{ "name" : "Welsh Affairs Committee" }
] }</pre>

<pre>{
    "committee" : "Health Committee",
    "members" : [
	{ "person_id" : "10009", "name" : "David Amess" },
	{ "person_id" : "10018", "name" : "Charlotte Atkins" },
	{ "person_id" : "10176", "name" : "Jim Dowd" },
	{ "person_id" : "11603", "name" : "Anne Milton" },
	{ "person_id" : "10455", "name" : "Doug Naysmith" },
	{ "person_id" : "11626", "name" : "Michael Penning" },
	{ "person_id" : "10571", "name" : "Howard Stoate" },
	{ "person_id" : "11275", "name" : "Richard Taylor" },
	{ "person_id" : "10027", "name" : "Kevin Barron", "position" : "Chairman" },
	{ "person_id" : "10089", "name" : "Ronnie Campbell" },
	{ "person_id" : "10677", "name" : "Sandra Gidley" }
  ]
}
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
			$output['committees'][] = array(
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
			$output['committee'] = html_entity_decode($q->field(0, 'dept'));
			for ($i=0; $i<$q->rows(); $i++) {
				$member = array(
'person_id' => $q->field($i, 'person'),
'name' => $q->field($i, 'first_name') . ' ' . $q->field($i, 'last_name'),
				);
				if ($q->field($i, 'position') == 'Chairman') {
					$member['position'] = $q->field($i, 'position');
				}
				$output['members'][] = $member;
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

<?

include_once INCLUDESPATH . 'easyparliament/member.php';
include_once 'api_getPerson.php';

function api_getMP_front() {
?>
<p><big>Fetch a particular MP.</big></p>

<h4>Arguments</h4>
<dl>

<dt>postcode (optional)</dt>
<dd>Fetch the MP for a particular postcode (either the current one, or the most recent one, depending upon the setting of the always_return variable.
<em>This will only return their current/ most recent entry in the database, look up by ID to get full history of a person.</em>
</dd>

<dt>constituency (optional)</dt>
<dd>The name of a constituency; we will try and work it out from whatever you give us. :)
<em>This will only return their current/ most recent entry in the database, look up by ID to get full history of a person.</em>
</dd>

<dt>id (optional)</dt>
<dd>If you know the person ID for the member you want (returned from getMPs or elsewhere), this will return data for that person.
This will return all database entries for this person, so will include previous elections, party changes, etc.
<!-- <em>Also returns select committee membership and ministerial positions, past and present.</em> --></dd>

<dt>always_return (optional)</dt>
<dd>For the postcode and constituency options, sets whether to always try and return an MP, even if the seat is currently vacant.</dd>
<!-- 
<dt>extra (optional)</dt>
<dd>Returns extra data in one or more categories, separated by commas.</dd>
-->
</dl>

<h4>Example Response</h4>
<pre>&lt;twfy&gt;
  &lt;/twfy&gt;
</pre>

<?	
}

function api_getMP_id($id) {
	$db = new ParlDB;
	$q = $db->query("select * from member
		where house=1 and person_id = '" . mysql_real_escape_string($id) . "'
		order by left_house desc");
	if ($q->rows()) {
        _api_getPerson_output($q);
	} else {
		api_error('Unknown person ID');
	}
}

function api_getMP_postcode($pc) {
	$pc = preg_replace('#[^a-z0-9 ]#i', '', $pc);
	if (validate_postcode($pc)) {
		$constituency = postcode_to_constituency($pc);
		if ($constituency == 'CONNECTION_TIMED_OUT') {
			api_error('Connection timed out');
		} elseif ($constituency) {
			$person = _api_getMP_constituency($constituency);
			$output = $person;
			api_output($output, strtotime($output['lastupdate']));
		} else {
			api_error('Unknown postcode');
		}
	} else {
		api_error('Invalid postcode');
	}
}

function api_getMP_constituency($constituency) {
	$person = _api_getMP_constituency($constituency);
	if ($person) {
		$output = $person;
		api_output($output, strtotime($output['lastupdate']));
	} else {
		api_error('Unknown constituency, or no MP for that constituency');
	}
}

# Very similary to MEMBER's constituency_to_person_id
# Should all be abstracted properly :-/
function _api_getMP_constituency($constituency) {
	$db = new ParlDB;

	if ($constituency == '')
		return false;

	if ($constituency == 'Orkney ')
		$constituency = 'Orkney &amp; Shetland';

	$normalised = normalise_constituency_name($constituency);
	if ($normalised) $constituency = $normalised;

	$q = $db->query("SELECT * FROM member
		WHERE constituency = '" . mysql_real_escape_string($constituency) . "'
		AND left_reason = 'still_in_office' AND house=1");
	if ($q->rows > 0)
		return _api_getPerson_row($q->row(0), true);

	if (get_http_var('always_return')) {
		$q = $db->query("SELECT * FROM member
			WHERE house=1 AND constituency = '".mysql_real_escape_string($constituency)."'
			ORDER BY left_house DESC LIMIT 1");
		if ($q->rows > 0)
			return _api_getPerson_row($q->row(0), true);
	}
	
	return false;
}


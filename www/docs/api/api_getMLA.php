<?

include_once INCLUDESPATH . 'easyparliament/member.php';
include_once dirname(__FILE__) . '/api_getPerson.php';

function api_getMLA_front() {
?>
<p><big>Fetch a particular MLA.</big></p>

<h4>Arguments</h4>
<dl>
<dt>postcode (optional)</dt>
<dd>Fetch the MLAs for a particular postcode.</dd>
<dt>constituency (optional)</dt>
<dd>The name of a constituency.</dd>
<dt>id (optional)</dt>
<dd>If you know the person ID for the member you want (returned from getMLAs or elsewhere), this will return data for that person.</dd>
</dl>

<h4>Example Response</h4>
<pre>&lt;twfy&gt;
  &lt;/twfy&gt;
</pre>

<?	
}

function api_getMLA_id($id) {
	$db = new ParlDB;
	$q = $db->query("select * from member
		where house=3 and person_id = '" . mysql_real_escape_string($id) . "'
		order by left_house desc");
	if ($q->rows()) {
		_api_getPerson_output($q);
	} else {
		api_error('Unknown person ID');
	}
}

function api_getMLA_postcode($pc) {
	$pc = preg_replace('#[^a-z0-9 ]#i', '', $pc);
	if (validate_postcode($pc)) {
		$constituencies = postcode_to_constituencies($pc, true);
		if ($constituencies == 'CONNECTION_TIMED_OUT') {
			api_error('Connection timed out');
		} elseif (isset($constituencies['NIE'])) {
			_api_getMLA_constituency($constituencies);
		} elseif (isset($constituencies['WMC'])) {
			api_error('Non-N. Irish postcode');
		} else {
			api_error('Unknown postcode');
		}
	} else {
		api_error('Invalid postcode');
	}
}

function api_getMLA_constituency($constituency) {
	$output = _api_getMLA_constituency(array($constituency));
	if (!$output)
		api_error('Unknown constituency, or no MLA for that constituency');
}

# Very similary to MEMBER's constituency_to_person_id
# Should all be abstracted properly :-/
function _api_getMLA_constituency($constituencies) {
	$db = new ParlDB;

	$cons = array();
	foreach ($constituencies as $constituency) {
		if ($constituency == '') continue;
		$cons[] = mysql_real_escape_string($constituency);
	}

	$q = $db->query("SELECT * FROM member
		WHERE constituency in ('" . join("','", $cons) . "')
		AND left_reason = 'still_in_office' AND house=3");
	if ($q->rows > 0) {
		_api_getPerson_output($q);
		return true;
	}

	return false;
}

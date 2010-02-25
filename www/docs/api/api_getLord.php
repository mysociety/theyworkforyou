<?

include_once 'api_getPerson.php';

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

function api_getLord_id($id) {
	$db = new ParlDB;
	$q = $db->query("select * from member
		where house=2 and person_id = '" . mysql_real_escape_string($id) . "'
		order by left_house desc");
	if ($q->rows()) {
        _api_getPerson_output($q);
	} else {
		api_error('Unknown person ID');
	}
}


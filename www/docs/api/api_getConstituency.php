<?

function api_getConstituency_front() {
?>
<p><big>Fetch a constituency.</big></p>

<h4>Arguments</h4>
<dl>
<dt>postcode</dt>
<dd>Fetch the constituency for a given postcode.</dd>
</dl>

<h4>Example Response</h4>
<pre>{ twfy : { name : "Manchester, Gorton" } }</pre>

<h4>Error Codes</h4>
<p></p>

<?	
}

function api_getconstituency_postcode($pc) {
	$pc = preg_replace('#[^a-z0-9 ]#i', '', $pc);
	if (is_postcode($pc)) {
		$constituency = postcode_to_constituency($pc);
		if ($constituency == 'CONNECTION_TIMED_OUT') {
			api_error('Connection timed out');
		} elseif ($constituency) {
			$output['twfy']['name'] = html_entity_decode($constituency);
			api_output($output);
		} else {
			api_error('Unknown postcode');
		}
	} else {
		api_error('Invalid postcode');
	}
}

?>

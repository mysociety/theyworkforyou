<?

include_once '../../../../phplib/mapit.php';

function api_getConstituency_front() {
?>
<p><big>Fetch a constituency.</big></p>

<h4>Arguments</h4>
<dl>
<dt>postcode</dt>
<dd>Fetch the constituency for a given postcode.</dd>
<dt>future</dt>
<dd>If set to anything, return the name of the constituency this postcode will be in
at the next election (<a href="/boundaries/new-constituencies.tsv">list as TSV file</a>).
This is a temporary feature before the 2010 general election.</dd>
</dl>

<h4>Example Response</h4>
<pre>{ "name" : "Manchester, Gorton" }</pre>

<h4>Example of future variable</h4>
<p>Without future=1, NN12 8NF returns: <samp>{ "name" : "Daventry" }</samp>
<p>With future=1, NN12 8NF returns: <samp>{ "name" : "South Northamptonshire" }</samp>

<?	
}

function api_getconstituency_postcode($pc) {
	$pc = preg_replace('#[^a-z0-9 ]#i', '', $pc);

  if (get_http_var('future')) {

    $new_areas = mapit_get_voting_areas($pc, 13); # Magic number 13
    if (!isset($new_areas['WMC'])) {
        api_error('Unknown postcode, or problem with lookup');
    }
    $new_info = mapit_get_voting_area_info($new_areas['WMC']);
    $output['name'] = html_entity_decode($new_info['name']);
    api_output($output);

  } else {

	if (validate_postcode($pc)) {
		$constituency = postcode_to_constituency($pc);
		if ($constituency == 'CONNECTION_TIMED_OUT') {
			api_error('Connection timed out');
		} elseif ($constituency) {
			$output['name'] = html_entity_decode($constituency);
			api_output($output);
		} else {
			api_error('Unknown postcode');
		}
	} else {
		api_error('Invalid postcode');
	}

  }
}

?>

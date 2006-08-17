<?

function api_getDebates_front() {
?>
<p><big>Fetch Debates.</big></p>
<p>This includes Oral Questions.</p>
<h4>Arguments</h4>
<p>Note you can only supply <strong>one</strong> of the following search terms at present.</p>
<dl>
<dt>type (required)</dt>
<dd>One of "commons", "westminsterhall", or "lords".
<dt>date</dt>
<dd>Fetch the debates for this date.</dd>
<dt>search</dt>
<dd>Fetch the debates that contain this term.</dd>
<dt>person</dt>
<dd>Fetch the debates by a particular person ID.</dd>
</dl>

<h4>Example Response</h4>

<?	
}

function api_getDebates_type($t) {
	if ($t == 'commons') {
		$LIST = new DEBATELIST;
	} elseif ($t == 'lords') {
		$LIST = new LORDSDEBATELIST;
	} elseif ($t == 'westminsterhall') {
		$LIST = new WHALLLIST;
	} else {
		api_error('Unknown type');
		return;
	}
	if ($d = get_http_var('date')) {
		$args = array ('date' => $d);
		$LIST->display('date', $args, 'api');
	} else {
		api_error('That search is not supported yet!');
	}
}

?>

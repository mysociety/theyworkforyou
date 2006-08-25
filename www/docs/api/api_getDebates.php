<?

include_once 'api_getHansard.php';

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
<dt>gid</dt>
<dd>Fetch the speech or debate that matches this GID.</dd>
</dl>

<h4>Example Response</h4>

<?	
}

function api_getDebates_type($t) {
	if ($t == 'commons') {
		$list = 'DEBATE';
	} elseif ($t == 'lords') {
		$list = 'LORDSDEBATE';
	} elseif ($t == 'westminsterhall') {
		$list = 'WHALL';
	} else {
		api_error('Unknown type');
		return;
	}
	if ($d = get_http_var('date')) {
		_api_getHansard_date($list, $d);
	} elseif ($s = get_http_var('search')) {
		_api_getHansard_search($list, $s);
	} elseif ($pid = get_http_var('person')) {
		_api_getHansard_person($list, $pid);
	} elseif ($gid = get_http_var('gid')) {
		_api_getHansard_gid($list, $gid);
	} elseif ($y = get_http_var('year')) {
		_api_getHansard_year($list, $y);
	} else {
		api_error('That is not a valid search.');
	}
}

function api_getDebates_date($d) {
	api_error('You must supply a type');
}
function api_getDebates_search($s) {
	api_error('You must supply a type');
}
function api_getDebates_person($p) {
	api_error('You must supply a type');
}
function api_getDebates_gid($p) {
	api_error('You must supply a type');
}
?>

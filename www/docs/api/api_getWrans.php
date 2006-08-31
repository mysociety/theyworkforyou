<?

include_once 'api_getHansard.php';

function api_getWrans_front() {
?>
<p><big>Fetch Written Questions/Answers.</big></p>

<h4>Arguments</h4>
<p>Note you can only supply <strong>one</strong> of the following at present.</p>
<dl>
<dt>date</dt>
<dd>Fetch the written answers for this date.</dd>
<dt>search</dt>
<dd>Fetch the written answers that contain this term.</dd>
<dt><s>department</s></dt>
<dd><s>Fetch the written answers by a particular department.</s></dd>
<dt>person</dt>
<dd>Fetch the written answers by a particular person ID.</dd>
<dt>gid</dt>
<dd>Fetch the written question/answer that matches this GID.</dd>
<dt>order (optional, when using search or person)</dt>
<dd><kbd>d</kbd> for date ordering, <kbd>r</kbd> for relevance ordering.</dd>
<dt>page (optional, when using search or person)</dt>
<dd>Page of results to return.</dd>
<dt>num (optional, when using search or person)</dt>
<dd>Number of results to return.</dd>
</dl>

<h4>Example Response</h4>
<pre>{
	"url" : "/wrans/",
	"dates" : [
		"2006-01-09",
		"2006-01-10",
		"2006-01-11",
		"2006-01-12",
		...
		"2006-07-19",
		"2006-07-20",
		"2006-07-24",
		"2006-07-25"
	]
}</pre>

<?	
}

function api_getWrans_date($d) {
	_api_getHansard_date('WRANS', $d);
}
function api_getWrans_year($y) {
	_api_getHansard_year('WRANS', $y);
}
function api_getWrans_search($s) {
	_api_getHansard_search( array(
		's' => $s,
		'pid' => get_http_var('person'),
		'type' => 'wrans',
	) );
}
function api_getWrans_person($pid) {
	_api_getHansard_search(array(
		'pid' => $pid,
		'type' => 'wrans',
	));
}
function api_getWrans_gid($gid) {
	_api_getHansard_gid('WRANS', $gid);
}
function api_getWrans_department($dept) {
	_api_getHansard_department('WRANS', $dept);
}

?>

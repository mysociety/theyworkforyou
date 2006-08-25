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
<dt>department</dt>
<dd>Fetch the written answers by a particular department.</dd>
<dt>person</dt>
<dd>Fetch the written answers by a particular person ID.</dd>
<dt>gid</dt>
<dd>Fetch the written question/answer that matches this GID.</dd>
</dl>

<h4>Example Response</h4>

<?	
}

function api_getWrans_date($d) {
	_api_getHansard_date('WRANS', $d);
}
function api_getWrans_year($y) {
	_api_getHansard_year('WRANS', $y);
}
function api_getWrans_search($s) {
	_api_getHansard_search('WRANS', $s);
}
function api_getWrans_person($pid) {
	_api_getHansard_person('WRANS', $pid);
}
function api_getWrans_gid($gid) {
	_api_getHansard_gid('WRANS', $gid);
}
function api_getWrans_department($dept) {
}

?>

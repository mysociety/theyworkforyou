<?

include_once 'api_getHansard.php';

function api_getWMS_front() {
?>
<p><big>Fetch Written Ministerial Statements.</big></p>

<h4>Arguments</h4>
<p>Note you can only supply <strong>one</strong> of the following at present.</p>
<dl>
<dt>date</dt>
<dd>Fetch the written ministerial statements for this date.</dd>
<dt>search</dt>
<dd>Fetch the written ministerial statements that contain this term.</dd>
<dt>department</dt>
<dd>Fetch the written ministerial statements by a particular department.</dd>
<dt>person</dt>
<dd>Fetch the written ministerial statements by a particular person ID.</dd>
<dt>gid</dt>
<dd>Fetch the written ministerial statement(s) that matches this GID.</dd>
</dl>

<h4>Example Response</h4>

<?
}

function api_getWMS_date($d) {
	_api_getHansard_date('WMS', $d);
}
function api_getWMS_year($y) {
	_api_getHansard_year('WMS', $y);
}
function api_getWMS_search($s) {
	_api_getHansard_search('WMS', $s);
}
function api_getWMS_person($pid) {
	_api_getHansard_person('WMS', $pid);
}
function api_getWMS_gid($gid) {
	_api_getHansard_gid('WMS', $gid);
}
function api_getWMS_department($dept) {
}

?>

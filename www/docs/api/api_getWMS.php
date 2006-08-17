<?

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
</dl>

<h4>Example Response</h4>

<?	
}

function api_getWMS_date($d) {
	$args = array ('date' => $d);
	$LIST = new WMSLIST;
	$LIST->display('date', $args, 'api');
}
function api_getWMS_search($s) {
}
function api_getWMS_person($pid) {
}
function api_getWMS_department($dept) {
}

?>

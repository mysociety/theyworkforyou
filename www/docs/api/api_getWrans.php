<?

function api_getWrans_front() {
?>
<p><big>Fetch Written Ministerial Statements.</big></p>

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
</dl>

<h4>Example Response</h4>

<?	
}

function api_getWrans_date($d) {
	$args = array ('date' => $d);
	$LIST = new WransLIST;
	$LIST->display('date', $args, 'api');
}
function api_getWrans_search($s) {
}
function api_getWrans_person($pid) {
}
function api_getWrans_department($dept) {
}

?>

<?

include_once 'api_getMembers.php';

function api_getLords_front() {
?>
<p><big>Fetch a list of Lords.</big></p>

<h4>Arguments</h4>
<dl>
<dt>date (optional)</dt>
<dd>Fetch the list of Lords as it was on this date. <strong>Note our from date is when the Lord is introduced in Parliament.</strong></dd>
<dt>search (optional)</dt>
<dd>Fetch the list of Lords that match this search string in their name.</dd>
</dl>

<h4>Example Response</h4>

<?	
}

/* See api_getMembers.php for these shared functions */
function api_getLords_party($s) {
	api_getMembers_party(2, $s);
}
function api_getLords_search($s) {
	api_getMembers_search(2, $s);
}
function api_getLords_date($date) {
	api_getMembers_date(2, $date);
}
function api_getLords($date = 'now()') {
	api_getMembers(2, $date);
}

?>

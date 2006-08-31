<?

include_once 'api_getMembers.php';

function api_getMPs_front() {
?>
<p><big>Fetch a list of MPs.</big></p>

<h4>Arguments</h4>
<dl>
<dt>date (optional)</dt>
<dd>Fetch the list of MPs as it was on this date.</dd>
<dt>search (optional)</dt>
<dd>Fetch the list of MPs that match this search string in their name.</dd>
</dl>

<h4>Example Response</h4>
<pre>a:646:{
	i:0; a:5:{
		s:9:"member_id"; s:4:"1368";
		s:9:"person_id"; s:5:"10900";
		s:4:"name"; s:13:"Hywel Francis";
		s:5:"party"; s:6:"Labour";
		s:12:"constituency"; s:8:"Aberavon";
	}
	i:1; ...
</pre>

<?	
}

/* See api_getMembers.php for these shared functions */
function api_getMPs_party($s) {
	api_getMembers_party(1, $s);
}
function api_getMPs_search($s) {
	api_getMembers_search(1, $s);
}
function api_getMPs_date($date) {
	api_getMembers_date(1, $date);
}
function api_getMPs($date = 'now()') {
	api_getMembers(1, $date);
}

?>

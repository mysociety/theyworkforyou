<?

include_once INCLUDESPATH."easyparliament/member.php";

function api_getHansard_front() {
?>
<p><big>Fetch all Hansard.</big></p>

<h4>Arguments</h4>
<p>Note you can only supply <strong>one</strong> of the following at present.</p>
<dl>
<dt>search</dt>
<dd>Fetch the data that contain this term.</dd>
<dt>person</dt>
<dd>Fetch the data by a particular person ID.</dd>
<dt>order (optional, when using search or person)</dt>
<dd><kbd>d</kbd> for date ordering, <kbd>r</kbd> for relevance ordering.</dd>
<dt>page (optional, when using search or person)</dt>
<dd>Page of results to return.</dd>
<dt>num (optional, when using search or person)</dt>
<dd>Number of results to return.</dd>
</dl>

<?	
}

function api_getHansard_search($s) {
	_api_getHansard_search( array(
		's' => $s,
		'pid' => get_http_var('person')
	) );
}
function api_getHansard_person($pid) {
	_api_getHansard_search(array(
		'pid' => $pid
	));
}

function _api_getHansard_date($type, $d) {
	$args = array ('date' => $d);
	$LIST = _api_getListObject($type);
	$LIST->display('date', $args, 'api');
}
function _api_getHansard_year($type, $y) {
	$args = array('year' => $y);
	$LIST = _api_getListObject($type);
	$LIST->display('calendar', $args, 'api');
}
function _api_getHansard_search($array) {
	$search = isset($array['s']) ? trim($array['s']) : '';
	$pid = trim($array['pid']);
	$type = isset($array['type']) ? $array['type'] : '';
	$search = filter_user_input($search, 'strict');
	if ($pid) {
		$search .= ($search?' ':'') . 'speaker:' . $pid;
	}
	if ($type) {
		$search .= " section:" . $type;
	}

	global $SEARCHENGINE;
        $SEARCHENGINE = new SEARCHENGINE($search); 
	#        $query_desc_short = $SEARCHENGINE->query_description_short();
    	$pagenum = get_http_var('page');
        $o = get_http_var('order');
    	$args = array (
    		's' => $search,
    		'p' => $pagenum,
    		'num' => get_http_var('num'),
		'pop' => get_http_var('pop'),
		'o' => ($o=='d' || $o=='r') ? $o : 'd',
    	);
    	$LIST = new HANSARDLIST();
        $LIST->display('search', $args, 'api');
}

function _api_getHansard_gid($type, $gid) {
	$args = array('gid' => $gid);
	$LIST = _api_getListObject($type);
	$LIST->display('gid', $args, 'api');
}

function _api_getHansard_department($type, $dept) {
	$args = array('department' => $dept);
	$LIST = _api_getListObject($type);
	$LIST->display('department', $args, 'api');
}

function _api_getListObject($type) {
	eval('$list = new ' . strtoupper($type) . 'LIST;');
	return $list;
}


?>

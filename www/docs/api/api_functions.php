<?

include_once '../../../../phplib/rabx.php';

# The METHODS

$methods = array(
	'convertURL' => array(
		'parameters' => array('url'),
		'required' => true,
		'help' => 'Converts a parliament.uk Hansard URL into a TheyWorkForYou one, if possible',
	),
	'getConstituency' => array(
		'new' => true,
		'parameters' => array('postcode'),
		'required' => true,
		'help' => 'Searches for a constituency',
	),
	'getConstituencies' => array(
		'parameters' => array('date', 'search', 'latitude', 'longitude', 'distance'),
		'required' => false,
		'help' => 'Returns list of constituencies',
	),
	'getMP' => array(
		'new' => true,
		'parameters' => array('id', 'constituency', 'postcode', 'always_return', 'extra'),
		'required' => true,
		'help' => 'Returns main details for an MP'
	),
	'getMPInfo' => array(
		'parameters' => array('id', 'fields'),
		'required' => true,
		'help' => 'Returns extra information for a person'
	),
	'getMPs' => array(
		'parameters' => array('party', 'date', 'search'),
		'required' => false,
		'help' => 'Returns list of MPs',
	),
	'getLord' => array(
		'parameters' => array('id'),
		'required' => true,
		'help' => 'Returns details for a Lord'
	),
	'getLords' => array(
		'parameters' => array('date', 'party', 'search'),
		'required' => false,
		'help' => 'Returns list of Lords',
	),
	'getMLAs' => array(
		'parameters' => array('date', 'party', 'search'),
		'required' => false,
		'help' => 'Returns list of MLAs',
	),
	'getMSP' => array(
		'parameters' => array('id', 'constituency', 'postcode'),
		'required' => true,
		'help' => 'Returns details for an MSP'
	),
	'getMSPs' => array(
		'parameters' => array('date', 'party', 'search'),
		'required' => false,
		'help' => 'Returns list of MSPs',
	),
	'getGeometry' => array(
		'new' => true,
		'parameters' => array('name'),
		'required' => false,
		'help' => 'Returns centre, bounding box of constituencies'
	),
/*	'getBoundary' => array(
		'parameters' => array('name'),
		'required' => true,
		'help' => 'Returns boundary polygon of constituency'
	),
*/
	'getCommittee' => array(
		'new' => true,
		'parameters' => array('name', 'date'),
		'required' => true,
		'help' => 'Returns members of Select Committee',
	),
	'getDebates' => array(
		'new' => true,
		'parameters' => array('type', 'date', 'search', 'person', 'gid', 'year', 'order', 'page', 'num'),
		'required' => true,
		'help' => 'Returns Debates (either Commons, Westminhall Hall, or Lords)',
	),
	'getWrans' => array(
		'parameters' => array('date', 'search', 'person', 'gid', 'year', 'order', 'page', 'num'),
		'required' => true,
		'help' => 'Returns Written Answers',
	),
	'getWMS' => array(
		'parameters' => array('date', 'search', 'person', 'gid', 'year', 'order', 'page', 'num'),
		'required' => true,
		'help' => 'Returns Written Ministerial Statements',
	),
	'getHansard' => array(
		'parameters' => array('search', 'person', 'order', 'page', 'num'),
		'required' => true,
		'help' => 'Returns any of the above',
	),
	'getComments' => array(
		'new' => true,
		'parameters' => array('search', 'page', 'num', 'pid'),
		'required' => false,
		'help' => 'Returns comments'
	),
	'postComment' => array(
		'parameters' => array('user_id', 'gid?'),
		'working' => false,
		'required' => true,
		'help' => 'Posts a comment - needs authentication!'
	),
);

# Key-related functions

function api_log_call($key) {
	if ($key=='DOCS') return;
	$ip = $_SERVER['REMOTE_ADDR'];
	$query = $_SERVER['REQUEST_URI'];
	$query = preg_replace('#key=[A-Za-z0-9]+&?#', '', $query);
	$db = new ParlDB;
	$db->query("INSERT INTO api_stats (api_key, ip_address, query_time, query)
		VALUES ('$key', '$ip', NOW(), '" . mysql_escape_string($query) . "')");
}

function api_check_key($key) {
	$db = new ParlDB;
	$q = $db->query('SELECT user_id FROM api_key WHERE api_key="' . mysql_escape_string($key) . '"');
	if (!$q->rows())
		return false;
	return true;
}

function api_key_current_message() { ?>
<p id="video_already" style="text-align:left"><em>Current API users</em>: We
realise the inconvenience of adding a key to an API that previously did not
require one. However, we feel it is now necessary in order to monitor the
service for abuse, help with support and maintenance, locate large volume/
commercial users to ask them to contribute to our costs, and provide you with
usage statistics.<br>
The API will allow key-less calls for a short time, during
which you can get a key and update your code.</p>
<?
}

# Front-end sidebar of all methods

function api_sidebar() {
	global $methods;
	$sidebar = '<div class="block"><h4>API Functions</h4> <div class="blockbody"><ul>';
	foreach ($methods as $method => $data){
		$sidebar .= '<li';
		if (isset($data['new']))
			$sidebar .= ' style="border-top: solid 1px #999999;"';
		$sidebar .= '>';
		if (!isset($data['working']) || $data['working'])
			$sidebar .= '<a href="' . WEBPATH . 'api/docs/' . $method . '">';
		$sidebar .= $method;
		if (!isset($data['working']) || $data['working'])
			$sidebar .= '</a>';
		else
			$sidebar .= ' - <em>not written yet</em>';
		#		if ($data['required'])
		#			$sidebar .= ' (parameter required)';
		#		else
		#			$sidebar .= ' (parameter optional)';
		$sidebar .= '<br>' . $data['help'];
		#		$sidebar .= '<ul>';
		#		foreach ($data['parameters'] as $parameter) {
		#			$sidebar .= '<li>' . $parameter . '</li>';
		#		}
		#		$sidebar .= '</ul>';
		$sidebar .= '</li>';
	}
	$sidebar .= '</ul></div></div>';
	$sidebar = array(
		'type' => 'html',
		'content' => $sidebar
	);
	return $sidebar;
}

# Output functions

function api_output($arr, $last_mod=null) {
	$output = get_http_var('output');
	if (!get_http_var('docs')) {
		$cond = api_header($output, $last_mod);
		if ($cond) return;
	}
	if ($output == 'xml') {
		$out = '<?xml version="1.0" encoding="iso-8859-1"?>'."\n";
		$out .= '<twfy>' . api_output_xml($arr) . '</twfy>';
	} elseif ($output == 'php') {
		$out = api_output_php($arr);
	} elseif ($output == 'rabx') {
		$out = api_output_rabx($arr);
	} else { # JS
		$out = api_output_js($arr);
		$callback = get_http_var('callback');
		if (preg_match('#^[A-Za-z0-9._[\]]+$#', $callback)) {
			$out = "$callback($out)";
		}
	}
	print $out;
}

function api_header($o, $last_mod=null) {
	if ($last_mod && array_key_exists('HTTP_IF_MODIFIED_SINCE', $_SERVER)) {
		$t = cond_parse_http_date($_SERVER['HTTP_IF_MODIFIED_SINCE']);
		if (isset($t) && $t >= $last_mod) {
			header('HTTP/1.0 304 Not Modified');
			header('Last-Modified: ' . date('r', $last_mod));
			return true;
		} 
	}
	if ($o == 'xml') {
		$type = 'text/xml';
	} elseif ($o == 'php') {
		$type = 'text/php';
	} elseif ($o == 'rabx') {
		$type = 'application/octet-stream';
	} else {
		$type = 'text/javascript';
	}
	#$type = 'text/plain';
	header("Content-Type: $type; charset=iso-8859-1");
	if ($last_mod>0)
		header('Last-Modified: ' . date('r', $last_mod));
	return false;
}

function api_error($e) {
	api_output(array('error' => $e));
}

function api_output_php($arr) {
	$out = serialize($arr);
	if (get_http_var('verbose')) $out = str_replace(';', ";\n", $out);
	return $out;
}

function api_output_rabx($arr) {
	$out = '';
	rabx_wire_wr($arr, $out);
	if (get_http_var('verbose')) $out = str_replace(',', ",\n", $out);
	return $out;
}

$api_xml_arr = 0;
function api_output_xml($v, $k=null) {
	global $api_xml_arr;
	$verbose = get_http_var('verbose') ? "\n" : '';
	if (is_array($v)) {
		if (count($v) && array_keys($v) === range(0, count($v)-1)) {
			$elt = 'match';
			$api_xml_arr++;
			$out = "<$elt>";
			$out .= join("</$elt>$verbose<$elt>", array_map('api_output_xml', $v));
			$out .= "</$elt>$verbose";
			return $out;
		}
		$out = '';
		foreach ($v as $k => $vv) {
			$out .= "<$k>";
			$out .= api_output_xml($vv, $k);
		        $out .= "</$k>$verbose";
		}
		return $out;
	} else {
		return htmlspecialchars($v);
	}
}

function api_output_js($v, $level=0) {
	$verbose = get_http_var('verbose') ? "\n" : '';
	if (is_array($v)) {
		# PHP arrays are both JS arrays and objects
		if (count($v) && array_keys($v) === range(0, count($v)-1))
			return '[' . join(",$verbose" , array_map('api_output_js', $v)) . ']';
		$out = '{' . $verbose;
		$b = false;
		foreach ($v as $k => $vv) {
			if ($b) $out .= ",$verbose";
			if ($verbose) {
				$out .= str_repeat(' ', ($level+1)*2);
				$out .= '"' . $k . '" : ';
			} else {
				$out .= '"' . $k . '":';
			}
			$out .= api_output_js($vv, $level+1);
			$b = true;
		}
		if ($verbose) $out .= "\n" . str_repeat(' ', $level*2);
		$out .= '}';
		return $out;
	} elseif (is_null($v)) {
		return "null";
	} elseif (is_string($v)) {
		return '"' . str_replace(
			array("\\",'"',"\n","\t","\r"),
			array("\\\\",'\"','\n','\t','\r'), $v) . '"';
	} elseif (is_bool($v)) {
		return $v ? 'true' : 'false';
	} elseif (is_int($v) || is_float($v)) {
		return $v;
	}
}

# Call an API function

function api_call_user_func_or_error($function, $params, $error, $type) {
	if (function_exists($function))
		call_user_func_array($function, $params);
	elseif ($type == 'api')
		api_error($error);
	else
		print "<p style='color:#cc0000'>$error</p>";
}

# Used for testing for conditional responses

$cond_wkday_re = '(Sun|Mon|Tue|Wed|Thu|Fri|Sat)';
$cond_weekday_re = '(Sunday|Monday|Tuesday|Wednesday|Thursday|Friday|Saturday)';
$cond_month_re = '(Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec)';
$cond_month_map = array(
	'Jan' =>  1, 'Feb' =>  2, 'Mar' =>  3, 'Apr' =>  4,
	'May' =>  5, 'Jun' =>  6, 'Jul' =>  7, 'Aug' =>  8,
	'Sep' =>  9, 'Oct' => 10, 'Nov' => 11, 'Dec' => 12
);

$cond_date1_re = '(\d\d) ' . $cond_month_re . ' (\d\d\d\d)';
$cond_date2_re = '(\d\d)-' . $cond_month_re . '-(\d\d)';
$cond_date3_re = $cond_month_re . ' (\d\d| \d)';

$cond_time_re = '([01][0-9]|2[0-3]):([0-5][0-9]):([0-5][0-9]|6[012])';

function cond_parse_http_date($date) {
	$H = $M = $S = 0;
	$Y = $m = $d = 0;

	$ma = array();
	global $cond_wkday_re, $cond_weekday_re, $cond_month_re, $cond_month_map,
	$cond_date1_re, $cond_date2_re, $cond_date3_re, $cond_time_re;
	if (preg_match("/^$cond_wkday_re, $cond_date1_re $cond_time_re GMT\$/", $date, $ma)) {
		/* RFC 1123 */
		$d = $ma[2];
		$m = $cond_month_map[$ma[3]];
		$Y = $ma[4];
		$H = $ma[5];
		$M = $ma[6];
		$S = $ma[7];
	} else if (preg_match("/^$cond_weekday_re, $cond_date2_re $cond_time_re GMT\$/", $date, $ma)) {
		/* RFC 850 */
		$d = $ma[2];
		$m = $cond_month_map[$ma[3]];
		$Y = $ma[4] + ($ma[4] < 50 ? 2000 : 1900); /* XXX */
		$H = $ma[5];
		$M = $ma[6];
		$S = $ma[7];
	} else if (preg_match("/^$cond_wkday_re $cond_date3_re $cond_time_re (\\d{4})\$/", $date, $ma)) {
		/* asctime(3) */
		$d = preg_replace('/ /', '', $ma[3]);
		$m = $cond_month_map[$ma[2]];
		$Y = $ma[7];
		$H = $ma[4];
		$M = $ma[5];
		$S = $ma[6];
	} else
		return null;

	return gmmktime($H, $M, $S, $m, $d, $Y);
}


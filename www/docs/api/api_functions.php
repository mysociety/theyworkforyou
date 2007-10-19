<?

include_once '../../../../phplib/rabx.php';

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

function api_call_user_func_or_error($function, $params, $error, $type) {
	if (function_exists($function))
		call_user_func_array($function, $params);
	elseif ($type == 'api')
		api_error($error);
	else
		print "<p style='color:#cc0000'>$error</p>";
}

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

?>

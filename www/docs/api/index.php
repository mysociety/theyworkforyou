<?

include_once '../../includes/easyparliament/init.php';
include_once '../../includes/postcode.inc';

# XXX: Need to override error handling! XXX

$methods = array(
	'urlconvert' => array(
		'parameters' => array('url'),
		'required' => true,
		'help' => 'Converts a parliament.uk Hansard URL into a TheyWorkForYou one, if possible',
		'working' => true,
	),
	'constituency' => array(
		'parameters' => array('postcode', 'search'),
		'required' => true,
		'help' => 'Searches for a constituency by postcode or text.',
		'working' => true,
	),
	'constituencies' => array(
		'parameters' => array('date'),
		'required' => false,
		'help' => 'Returns current list of constituencies; as of date if date is provided.',
		'working' => true,
	),
	'mp' => array(
		'parameters' => array('id', 'constituency?', 'postcode?', 'search'),
		'working' => false,
		'required' => true,
		'help' => 'Returns details for an MP'
	),
	'mps' => array(
		'parameters' => array('date', 'party', 'constituency?', 'postcode?', 'date', 'search'),
		'working' => false,
		'required' => false,
		'help' => 'Returns list of MPs',
	),
	'lord' => array(
		'parameters' => array('id', 'search'),
		'working' => false,
		'required' => true,
		'help' => 'Returns details for a Lord'
	),
	'lords' => array(
		'parameters' => array('date', 'party'),
		'working' => false,
		'required' => false,
		'help' => 'Returns list of Lords',
	),
	'debates' => array(
		'parameters' => array('type', 'date', 'search'),
		'working' => false,
		'required' => true,
		'help' => 'Returns Debates (either Commons, Westminhall Hall, or Lords)',
	),
	'wrans' => array(
		'parameters' => array('date', 'department?', 'search'),
		'working' => false,
		'required' => true,
		'help' => 'Returns Written Answers',
	),
	'wms' => array(
		'parameters' => array('date', 'department?', 'search'),
		'working' => false,
		'required' => true,
		'help' => 'Returns Written Ministerial Statements',
	),
	'hansard' => array(
		'parameters' => array('date', 'search', 'id'),
		'working' => false,
		'required' => true,
		'help' => 'Returns anything?'
	),
);

if ($q_method = get_http_var('method')) {
	$match = 0;
	foreach ($methods as $method => $data) {
		if ($q_method == $method) {
			$match++;
			foreach ($data['parameters'] as $parameter) {
				if ($q_param = get_http_var($parameter)) {
					$match++;
					if ($data['working'])
						call_user_func('api_' . $method . '_' . $parameter, $q_param);
					else
						api_front_page('API call not yet functional');
					break;
				}
			}
			if ($match == 1) {
				if ($data['required']) {
					api_front_page('No parameter provided to method "' .
					htmlspecialchars($q_method) .
						'". Possible choices are: ' .
						join(', ', $data['parameters']) );
				} else {
					call_user_func('api_' . $method);
					break;
				}
			}
			break;
		}
	}
	if (!$match) {
		api_front_page('Unknown method "' . htmlspecialchars($q_method) .
			'". Possible methods are: ' .
			join(', ', array_keys($methods)) );
	}
} else {
	api_front_page();
}

/* Very similar to function in hansardlist.php, but separated */
function get_listurl($q) {
	global $hansardmajors;
	$id_data = array(
		'gid' => fix_gid_from_db($q->field(0, 'gid')),
		'major' => $q->field(0, 'major'),
		'htype' => $q->field(0, 'htype'),
		'subsection_id' => $q->field(0, 'subsection_id'),
	);
	$db = new ParlDB;
	$LISTURL = new URL($hansardmajors[$id_data['major']]['page_all']);
	$fragment = '';
	if ($id_data['htype'] == '11' || $id_data['htype'] == '10') {
		$LISTURL->insert( array( 'id' => $id_data['gid'] ) );
	} else {
		$parent_epobject_id = $id_data['subsection_id'];
		$parent_gid = '';
		$r = $db->query("SELECT gid
				FROM 	hansard
				WHERE	epobject_id = '" . mysql_escape_string($parent_epobject_id) . "'
				");
		if ($r->rows() > 0) {
			$parent_gid = fix_gid_from_db( $r->field(0, 'gid') );
		}
		if ($parent_gid != '') {
			$LISTURL->insert( array( 'id' => $parent_gid ) );
			$fragment = '#g' . gid_to_anchor($id_data['gid']);
		}
	}
	return $LISTURL->generate('none') . $fragment;
}

function api_urlconvert_url_output($q) {
	$gid = $q->field(0, 'gid');
	$url = get_listurl($q);
	$output['twfy'] = array(
		'gid' => $gid,
		'url' => 'http://www.theyworkforyou.com' . $url
	);
	api_output($output);
}
function api_urlconvert_url($url) {
	$db = new ParlDB;
	$url_nohash = preg_replace('/#.*/', '', $url);
	$q = $db->query('select gid,major,htype,subsection_id from hansard where source_url = "' . mysql_escape_string($url) . '" order by gid limit 1');
	if ($q->rows())
		return api_urlconvert_url_output($q);

	$q = $db->query('select gid,major,htype,subsection_id from hansard where source_url like "' . mysql_escape_string($url_nohash) . '%" order by gid limit 1');
	if ($q->rows())
		return api_urlconvert_url_output($q);

	$url_bound = str_replace('cmhansrd/cm', 'cmhansrd/vo', $url_nohash);
	if ($url_bound != $url_nohash) {
		$q = $db->query('select gid,major,htype,subsection_id from hansard where source_url like "' . mysql_escape_string($url_bound) . '%" order by gid limit 1');
		if ($q->rows())
			return api_urlconvert_url_output($q);
	}
	api_error('Sorry, URL could not be converted');
}

function api_constituencies_date($date) {
	if (preg_match('#^\d\d\d\d-\d\d-\d\d$#', $date)) {
		api_constituencies('"' . $date . '"');
	} else {
		api_error('Invalid date format');
	}
}
function api_constituencies($date = 'now()') {
	$db = new ParlDB;
	$q = $db->query('select cons_id, name from constituency
		where main_name and from_date <= date('.$date.') and date('.$date.') <= to_date');
	$output['twfy']['matches'] = array();
	for ($i=0; $i<$q->rows(); $i++) {
		$output['twfy']['matches'][] = array(
			'id' => $q->field($i, 'cons_id'),
			'name' => $q->field($i, 'name')
		);
	}
	api_output($output);
}

function api_constituency_postcode($pc) {
	$pc = preg_replace('#[^a-z0-9 ]#i', '', $pc);
	if (is_postcode($pc)) {
		$constituency = postcode_to_constituency($pc);
		if ($constituency == 'CONNECTION_TIMED_OUT') {
			api_error('Connection timed out');
		} elseif ($constituency) {
			$output['twfy']['name'] = strip_tags($constituency);
			api_output($output);
		} else {
			api_error('Unknown postcode');
		}
	} else {
		api_error('Invalid postcode');
	}
}

function api_constituency_search($s) {
	$db = new ParlDB;
	$q = $db->query('select * from constituency
		where main_name and name like "%' . mysql_escape_string($s) .
		'%" and from_date <= date(now()) and date(now()) <= to_date');
	$output['twfy']['matches'] = array();
	for ($i=0; $i<$q->rows(); $i++) {
		$output['twfy']['matches'][] = array(
			'id' => $q->field($i, 'cons_id'),
			'name' => $q->field($i, 'name')
		);
	}
	api_output($output);
}

function api_front_page($error = '') {
	global $PAGE, $methods, $this_page;
	$this_page = 'api_front';
	$PAGE->page_start();
	$PAGE->stripe_start();
	if ($error) {
		print "<p style='color: #cc0000'>$error</p>";
	}
?>
<p>Welcome to TheyWorkForYou's API section, where you can learn how to query our database for stuff.</p>

<p align="center"><strong>http://www.theyworkforyou.com/api/?method=<em>method</em>&output=<em>output</em>&<em>other_variables</em></strong></p>

<h3>Outputs</h3>
<dl>
<dt>xml
<dd>XML. The root element is twfy.
<dt>php
<dd>Serialized PHP, that can be turned back into useful information with the unserialize() command.
<dt>js
<dd>A JavaScript object. You can provide a callback function with the <em>callback</em> variable, and then that
function will be called with the data as its argument.
</dl>
<h3>Examples</h3>

<p><a href="javascript:function foo(r){if(r.twfy.url)window.location=r.twfy.url;};(function(){var s=document.createElement('script');s.setAttribute('src','http://theyworkforyou.com/api/?method=urlconvert&callback=foo&url='+encodeURIComponent(window.location));s.setAttribute('type','text/javascript');document.getElementsByTagName('head')[0].appendChild(s);})()">Hansard prettifier</a> - drag this bookmarklet to your bookmarks bar, or bookmark it. Then if you ever find yourself on the official site, clicking this will try and take you to the equivalent page on TheyWorkForYou. (Tested in Firefox and Opera)</p>

<?
	print '<h3>Methods</h3> <ul>';
	foreach ($methods as $method => $data){
		print '<li>' . $method;
		if ($data['required'])
			print ' (parameter required)';
		else
			print ' (parameter optional)';
		if (!$data['working'])
			print ' &ndash; NOT YET WORKING';
		print ' &ndash; ' . $data['help'];
		print '<ul>';
		foreach ($data['parameters'] as $parameter) {
			print '<li>' . $parameter . '</li>';
		}
		print '</ul></li>';
	}
	print '</ul>';

	$PAGE->stripe_end();
	$PAGE->page_end();
}

# Actual API output functions

function api_output($arr) {
	$output = get_http_var('output');
	api_header($output);
	if ($output == 'xml') {
		$out = api_output_xml($arr);
	} elseif ($output == 'php') {
		$out = serialize($arr);
	} else { # JS
		$callback = get_http_var('callback');
		$out = api_output_js($arr);
		if (preg_match('#^[A-Za-z0-9._[\]]+$#', $callback)) {
			$out = "$callback($out)";
		}
	}
	print $out;
}

function api_header($o) {
	if ($o == 'xml') {
		$type = 'text/xml';
	} elseif ($o == 'php') {
		$type = 'text/php';
	} else {
		$type = 'text/javascript';
	}
	$type = 'text/plain';
	header("Content-Type: $type");
}

function api_error($e) {
	$output = get_http_var('output');
	api_header($output);
	if ($output == 'xml') {
		$out = "<twfy><error>$e</error></twfy>";
	} elseif ($output == 'php') {
		$out = serialize(array('twfy'=>array('error'=>$e)));
	} else { # JS
		$callback = get_http_var('callback');
		$out = '{twfy:{error:"'.$e.'"}}';
		if (preg_match('#^[A-Za-z0-9._[\]]+$#', $callback)) {
			$out = "$callback($out)";
		}
	}
	print $out;
}

function api_output_xml($arr) {
	$out = '';
	foreach ($arr as $k => $v) {
		if (is_array($v)) {
			# Actual array, or hash? is_numeric() not strictly correct, but will do
			$hash = false;
			foreach ($v as $kk => $vv) {
				if (!is_numeric($kk)) {
					$hash = true;
					break;
				}
			}
			if ($hash) {
				$out .= "<$k>" . api_output_xml($v) . "</$k>\n";
			} else {
				$out .= "<$k>" . join("</$k>\n<$k>", array_map('api_output_xml', $v)) . "</$k>\n";
			}
		} else {
			$out .= "<$k>" . htmlspecialchars($v) . "</$k>\n";
		}
	}
	return $out;
}
function api_output_js($arr) {
	$out = '{';
	$b = false;
	foreach ($arr as $k => $v) {
		if ($b)
			$out .= ',';
		$out .= "$k:";
		$out .= api_output_js_var($v);
		$b = true;
	}
	$out .= '}';
	return $out;
}

function api_output_js_var($v) {
	if (is_array($v)) {
		# Actual array, or hash? is_numeric() not strictly correct, but will do
		$hash = false;
		foreach ($v as $kk => $vv) {
			if (!is_numeric($kk)) {
				$hash = true;
				break;
			}
		}
		if ($hash) {
			return api_output_js($v);
		} else {
			return '[' . join(",\n" , array_map('api_output_js_var', $v)) . ']';
		}
	} elseif (is_null($v)) {
		return "null";
	} elseif (is_string($v)) {
		return "\"$v\"";
	} elseif (is_bool($v)) {
		return $v ? 'true' : 'false';
	} elseif (is_int($v) || is_float($v)) {
		return $v;
	}
}

?>

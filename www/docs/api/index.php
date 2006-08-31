<?

include_once '../../includes/easyparliament/init.php';
include_once '../../includes/postcode.inc';

include_once 'api_functions.php';

# XXX: Need to override error handling! XXX

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
		'parameters' => array('id', 'constituency', 'postcode', 'always_return'),
		'required' => true,
		'help' => 'Returns main details for an MP'
	),
	'getMPInfo' => array(
		'parameters' => array('id'),
		'required' => true,
		'help' => 'Returns extra information for an MP'
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
	'getGeometry' => array(
		'new' => true,
		'parameters' => array('name'),
		'required' => false,
		'help' => 'Returns geometry of constituencies'
	),
	'getBoundary' => array(
		'parameters' => array('id', 'constituency', 'postcode'),
		'working' => false,
		'required' => true,
		'help' => 'Returns boundary details for a constituency'
	),
	'getCommittee' => array(
		'new' => true,
		'parameters' => array('name', 'date'),
		'required' => true,
		'help' => 'Returns members of Select Committee',
	),
	'getDebates' => array(
		'new' => true,
		'parameters' => array('type', 'date', 'search', 'person', 'gid', 'year', 'order', 'page', 'num'),
		'working' => true,
		'required' => true,
		'help' => 'Returns Debates (either Commons, Westminhall Hall, or Lords)',
	),
	'getWrans' => array(
		'parameters' => array('date', 'search', 'person', 'gid', 'year', 'order', 'page', 'num'),
		'working' => true,
		'required' => true,
		'help' => 'Returns Written Answers',
	),
	'getWMS' => array(
		'parameters' => array('date', 'search', 'person', 'gid', 'year', 'order', 'page', 'num'),
		'working' => true,
		'required' => true,
		'help' => 'Returns Written Ministerial Statements',
	),
	'getComments' => array(
		'new' => true,
		'parameters' => array('user_id', 'page', 'number'),
		'working' => false,
		'required' => true,
		'help' => 'Returns comments'
	),
	'postComment' => array(
		'parameters' => array('user_id', 'gid?'),
		'working' => false,
		'required' => true,
		'help' => 'Posts a comment - needs authentication!'
	),
);

if ($q_method = get_http_var('method')) {
	$match = 0;
	foreach ($methods as $method => $data) {
		if (strtolower($q_method) == strtolower($method)) {
			$match++;
			if (get_http_var('docs')) {
				api_documentation_front($method);
				break;
			}
			foreach ($data['parameters'] as $parameter) {
				if ($q_param = trim(get_http_var($parameter))) {
					$match++;
					include_once 'api_' . $method . '.php';
					api_call_user_func_or_error('api_' . $method . '_' . $parameter, array($q_param), 'API call not yet functional', 'api');
					break;
				}
			}
			if ($match == 1) {
				if ($data['required']) {
					api_error('No parameter provided to function "' .
					htmlspecialchars($q_method) .
						'". Possible choices are: ' .
						join(', ', $data['parameters']) );
				} else {
					include_once 'api_' . $method . '.php';
					api_call_user_func_or_error('api_' . $method, null, 'API call not yet functional', 'api');
					break;
				}
			}
			break;
		}
	}
	if (!$match) {
		api_front_page('Unknown function "' . htmlspecialchars($q_method) .
			'". Possible functions are: ' .
			join(', ', array_keys($methods)) );
	}
} else {
	api_front_page();
}

function api_documentation_front($method) {
	global $PAGE, $this_page, $DATA, $methods;
	$this_page = 'api_doc_front';
	$DATA->set_page_metadata($this_page, 'title', "$method function");
	$PAGE->page_start();
	$PAGE->stripe_start();
	include_once 'api_' . $method . '.php';
	print '<p align="center"><strong>http://www.theyworkforyou.com/api/' . $method . '</strong></p>';
	api_call_user_func_or_error('api_' . $method . '_front', null, 'No documentation yet', 'html');
?>
<h4>Explorer</h4>
<p>Try out this function without writing any code!</p>
<form method="get" action="/api/<?=$method ?>" target="iframe">
<p>
<? foreach ($methods[$method]['parameters'] as $parameter) {
	print $parameter . ': <input type="text" name="'.$parameter.'" value="" size="30"><br>';
}
?>
Output:
<input id="output_js" type="radio" name="output" value="js" checked> <label for="output_js">JS</label>
<input id="output_xml" type="radio" name="output" value="xml"> <label for="output_xml">XML</label>
<input id="output_php" type="radio" name="output" value="php"> <label for="output_php">Serialised PHP</label>

<input type="hidden" name="verbose" value="1" />
<input type="submit" value="Go" />
</p>
</form>
<iframe name="iframe" style="width: 40em; height: 20em">Output here</iframe>
<?
	$sidebar = api_sidebar();
	$PAGE->stripe_end(array($sidebar));
	$PAGE->page_end();
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
<p>Welcome to TheyWorkForYou's API section, where you can learn how to query our database for information. Non-commercial use is free, please contact us for commercial use. <em>This API is currently being written!</em></p>

<h3>Overview</h3>

<p>All requests take a number of parameters. <em>output</em> is optional, and defaults to <kbd>js</kbd>.</p>

<p align="center"><strong>http://www.theyworkforyou.com/api/<em>function</em>?output=<em>output</em>&<em>other_variables</em></strong></p>

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

<p><a href="javascript:function foo(r){if(r.twfy.url)window.location=r.twfy.url;};(function(){var s=document.createElement('script');s.setAttribute('src','http://theyworkforyou.com/api/convertURL?callback=foo&url='+encodeURIComponent(window.location));s.setAttribute('type','text/javascript');document.getElementsByTagName('head')[0].appendChild(s);})()">Hansard prettifier</a> - drag this bookmarklet to your bookmarks bar, or bookmark it. Then if you ever find yourself on the official site, clicking this will try and take you to the equivalent page on TheyWorkForYou. (Tested in IE, Firefox, Opera.)</p>

<?
	$sidebar = api_sidebar();
	$PAGE->stripe_end(array($sidebar));
	$PAGE->page_end();
}

function api_sidebar() {
	global $methods;
	$sidebar = '<div class="block"><h4>API Functions</h4> <div class="blockbody"><ul>';
	foreach ($methods as $method => $data){
		$sidebar .= '<li';
		if (isset($data['new']))
			$sidebar .= ' style="border-top: solid 1px #999999;"';
		$sidebar .= '>';
		if (!isset($data['working']) || $data['working'])
			$sidebar .= '<a href="/api/docs/' . $method . '">';
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
?>

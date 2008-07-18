<?

include_once '../../includes/easyparliament/init.php';
include_once '../../includes/postcode.inc';

include_once 'api_functions.php';

# XXX: Need to override error handling! XXX

if ($q_method = get_http_var('method')) {
	if (get_http_var('docs')) {
		$key = 'DOCS';
	} else {
		if (!get_http_var('key')) {
			api_error('No API key provided. Please see http://www.theyworkforyou.com/api/key for more information.');
			exit;
		}
		$key = get_http_var('key');
		if (!api_check_key($key)) {
			api_error('Invalid API key.');
			exit;
		}
	}
	$match = 0;
	foreach ($methods as $method => $data) {
		if (strtolower($q_method) == strtolower($method)) {
			api_log_call($key);
			$match++;
			if (get_http_var('docs')) {
				$_GET['verbose'] = 1;
				ob_start();
			}
			foreach ($data['parameters'] as $parameter) {
				if ($q_param = trim(get_http_var($parameter))) {
					$match++;
					include_once 'api_' . $method . '.php';
					api_call_user_func_or_error('api_' . $method . '_' . $parameter, array($q_param), 'API call not yet functional', 'api');
					break;
				}
			}
			if ($match == 1 && (get_http_var('output') || !get_http_var('docs'))) {
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
		api_log_call($key);
		api_front_page('Unknown function "' . htmlspecialchars($q_method) .
			'". Possible functions are: ' .
			join(', ', array_keys($methods)) );
	} else {
		if (get_http_var('docs')) {
			$explorer = ob_get_clean();
			api_documentation_front($method, $explorer);
		}
	}
} else {
	api_front_page();
}

function api_documentation_front($method, $explorer) {
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
<form method="get" action="?#output">
<p>
<? foreach ($methods[$method]['parameters'] as $parameter) {
	print $parameter . ': <input type="text" name="'.$parameter.'" value="';
	if ($val = get_http_var($parameter))
		print htmlspecialchars($val);
	print '" size="30"><br>';
}
?>
Output:
<input id="output_js" type="radio" name="output" value="js"<? if (get_http_var('output')=='js' || !get_http_var('output')) print ' checked'?>>
<label for="output_js">JS</label>
<input id="output_xml" type="radio" name="output" value="xml"<? if (get_http_var('output')=='xml') print ' checked'?>>
<label for="output_xml">XML</label>
<input id="output_php" type="radio" name="output" value="php"<? if (get_http_var('output')=='php') print ' checked'?>>
<label for="output_php">Serialised PHP</label>
<input id="output_rabx" type="radio" name="output" value="rabx"<? if (get_http_var('output')=='rabx') print ' checked'?>>
<label for="output_rabx">RABX</label>

<input type="submit" value="Go">
</p>
</form>
<?
	if ($explorer) {
		$qs = array();
		foreach ($methods[$method]['parameters'] as $parameter) {
			if (get_http_var($parameter))
				$qs[] = htmlspecialchars(rawurlencode($parameter) . '=' . urlencode(get_http_var($parameter)));
		}
		print '<h4><a name="output"></a>Output</h4>';
		print '<p>URL for this: <strong>http://www.theyworkforyou.com/api/';
		print $method . '?' . join('&amp;', $qs) . '&amp;output='.get_http_var('output').'</strong></p>';
		print '<pre>' . htmlspecialchars($explorer) . '</pre>';
	}
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
<p>Welcome to TheyWorkForYou's API section, where you can learn how to query our database for information.</p>

<h3>Overview</h3>

<ol style="font-size:130%">
<li><a href="key">Get an API key</a>.
<li>All requests are made by GETting a particular URL with a number of parameters. <em>key</em> is required;
<em>output</em> is optional, and defaults to <kbd>js</kbd>.
</ol>

<p align="center"><strong>http://www.theyworkforyou.com/api/<em>function</em>?key=<em>key</em>&amp;output=<em>output</em>&amp;<em>other_variables</em></strong></p>

<? api_key_current_message(); ?>

<p>The current version of the API is <em>1.0.0</em>. If we make changes to the
API functions, we'll increase the version number and make it an argument so you
can still use the old version.</p>

<table>
<tr valign="top">
<td width="60%">

<h3>Outputs</h3>
<p>The <em>output</em> argument can take any of the following values:
<ul>
<li><strong>xml</strong>. XML. The root element is twfy.</li>
<li><strong>php</strong>. Serialized PHP, that can be turned back into useful information with the unserialize() command. Quite useful in Python as well, using <a href="http://hurring.com/code/python/serialize/">PHPUnserialize</a>.</li>
<li><strong>js</strong>. A JavaScript object. You can provide a callback
function with the <em>callback</em> variable, and then that function will be
called with the data as its argument.</li>
<li><strong>rabx</strong>. "RPC over Anything But XML".</li>
</ul>

</td><td>

<h3>Errors</h3>

<p>If there's an error, either in the arguments provided or in trying to perform the request,
this is returned as a top-level error string, ie. in XML it returns
<code>&lt;twfy&gt;&lt;error&gt;ERROR&lt;/error&gt;&lt;/twfy&gt;</code>;
in JS <code>{"error":"ERROR"}</code>;
and in PHP and RABX a serialised array containing one entry with key <code>error</code>.

</td></tr></table>

<h3>Licensing</h3>

<p>To use parliamentary material yourself (that's data returned from
getDebates, getWrans, and getWMS), you will need to get a
<a href="http://www.opsi.gov.uk/click-use/parliamentary-licence-information/index.htm">Parliamentary Licence</a> from the Office of Public Sector
Information.

<? /* All Ordnance Survey data (returned by getGeometry and getBoundary) is
covered by the <acronym title="Pan-Government Agreement">PGA</acronym>
under the <a href="http://www.dca.gov.uk/">Department for Constitutional
Affairs</a>; you will have to get your own licence to re-use them. */ ?>

Our own data - lists of MPs, Lords, constituencies and so on - is available
under the <a href="http://creativecommons.org/licenses/by-sa/2.5/">Creative
Commons Attribution-ShareAlike license version 2.5</a>.

<p>Low volume, non-commercial use of the API service itself is free. Please
<a href="/contact">contact us</a> for commercial use, or if you are about
to use the service on a large scale.

<h3>Bindings</h3>

<p>These help you interface with the API more easily in a particular language:</p>
<ul>
<li><a href="http://codefluency.com/2006/11/21/tfwy-1-0-0-released">Ruby</a> (thanks to Bruce Williams and Martin Owen)</li>
<li><a href="http://search.cpan.org/~sden/WebService-TWFY-API-0.01/lib/WebService/TWFY/API.pm">Perl</a> (thanks to Spiros Denaxas)</li>
<li><a href="http://tools.wackomenace.co.uk/twfyapi/">PHP</a> (thanks to Ruben Arakelyan)</li>
<li><a href="http://tools.wackomenace.co.uk/twfyapi/">ASP.net</a> (thanks to Ruben Arakelyan)</li>
<li><a href="https://sourceforge.net/projects/twfyjavaapi">Java</a> (thanks to Mitch Kent)</li>
</ul>

<p>If anyone wishes to write bindings for the API in any language, please
do so, let us know and we'll link to it here. You might want to
<a href="https://secure.mysociety.org/admin/lists/mailman/listinfo/developers-public">join our public developer mailing list</a>
to discuss things.</p>

<h3>Examples</h3>

<ul>
<li><a href="http://www.dracos.co.uk/work/theyworkforyou/api/postcode/">Postcode to constituency lookup, with no server side code</a> - use this to add constituency or MP lookup to a form on your website.
<li><a href="http://www.dracos.co.uk/work/theyworkforyou/api/map/">Map showing location of all 646 constituencies, with no server side code</a> - example code using JavaScript and Google Maps.
<li><a href="javascript:function foo(r){if(r.twfy.url)window.location=r.twfy.url;};(function(){var s=document.createElement('script');s.setAttribute('src','http://theyworkforyou.com/api/convertURL?callback=foo&url='+encodeURIComponent(window.location));s.setAttribute('type','text/javascript');document.getElementsByTagName('head')[0].appendChild(s);})()">Hansard prettifier</a> - drag this bookmarklet to your bookmarks bar, or bookmark it. Then if you ever find yourself on the official site, clicking this will try and take you to the equivalent page on TheyWorkForYou. (Tested in IE, Firefox, Opera.)</li>
<li><a href="http://www.dracos.co.uk/work/theyworkforyou/api/fabfarts/">Matthew's MP Fab Farts</a> - every technology has the capacity to be used for fun.
<li><a href="telnet://seagrass.goatchurch.org.uk:646/">Francis' MP Fight telnet text adventure</a> (<s>and <a href="http://caesious.beasts.org/~chris/scripts/mpfight">Chris' web version</a></s>) - battle your way to Sedgefield!
<li><a href="http://www.straw-dogs.co.uk/10/15/your-mp-google-desktop-gadget/">Your MP - Google Desktop Gadget</a> - with GPL source code
</ul>

<?
	$sidebar = api_sidebar();
	$PAGE->stripe_end(array($sidebar));
	$PAGE->page_end();
}


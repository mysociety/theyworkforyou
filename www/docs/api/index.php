<?php

include_once '../../includes/easyparliament/init.php';
include_once './api_functions.php';

# XXX: Need to override error handling! XXX

if ($q_method = get_http_var('method')) {
    if (get_http_var('docs')) {
        $key = 'DOCS';
    } else {
        $key = get_http_var('key');
        if (!$key) {
            api_error('No API key provided. Please see http://www.theyworkforyou.com/api/key for more information.');
            exit;
        }
        $check = api_check_key($key);
        if (!$check) {
            api_error('Invalid API key.');
            exit;
        } elseif ($check === 'disabled') {
            api_error('Your API key has been disabled.');
            exit;
        }
    }
    $match = 0;
    foreach ($methods as $method => $data) {
        if (strtolower($q_method) == strtolower($method)) {
      if (isset($data['superuser']) && $data['superuser']) {
        $super_check = api_is_superuser_key($key);
        if (!$super_check) {
                if (get_http_var('docs')) {
                  api_front_page();
                } else {
                  api_error('Invalid API key.');
                  exit;
              }
            }
      }

            api_log_call($key);
            $match++;
            if (get_http_var('docs')) {
                $_GET['verbose'] = 1;
                ob_start();
            }
            foreach ($data['parameters'] as $parameter) {
                if ($q_param = trim(get_http_var($parameter))) {
                    $match++;
                    include_once 'api_'. $method . '.php';
                    api_call_user_func_or_error('api_' . $method . '_' . $parameter, array($q_param), 'API call not yet functional', 'api');
                    break;
                }
            }
            if ($match == 1 && (get_http_var('output') || !get_http_var('docs'))) {
                if ($data['required']) {
                    api_error('No parameter provided to function "' .
                    _htmlspecialchars($q_method) .
                        '". Possible choices are: ' .
                        join(', ', $data['parameters']) );
                } else {
                    include_once 'api_'. $method . '.php';
                    api_call_user_func_or_error('api_' . $method, array(), 'API call not yet functional', 'api');
                    break;
                }
            }
            break;
        }
    }
    if (!$match) {
        api_log_call($key);
        api_front_page('Unknown function "' . _htmlspecialchars($q_method) .
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
    include_once 'api_'. $method . '.php';
    print '<p align="center"><strong>http://www.theyworkforyou.com/api/' . $method . '</strong></p>';
    api_call_user_func_or_error('api_' . $method . '_front', array(), 'No documentation yet', 'html');
?>
<h4>Explorer</h4>
<p>Try out this function without writing any code!</p>
<form method="get" action="?#output">
<p>
<?php foreach ($methods[$method]['parameters'] as $parameter) {
    print $parameter . ': <input type="text" name="'.$parameter.'" value="';
    if ($val = get_http_var($parameter))
        print _htmlspecialchars($val);
    print '" size="30"><br>';
}
?>
Output:
<input id="output_js" type="radio" name="output" value="js"<?php if (get_http_var('output')=='js' || !get_http_var('output')) print ' checked'?>>
<label for="output_js">JS</label>
<input id="output_xml" type="radio" name="output" value="xml"<?php if (get_http_var('output')=='xml') print ' checked'?>>
<label for="output_xml">XML</label>
<input id="output_php" type="radio" name="output" value="php"<?php if (get_http_var('output')=='php') print ' checked'?>>
<label for="output_php">Serialised PHP</label>
<input id="output_rabx" type="radio" name="output" value="rabx"<?php if (get_http_var('output')=='rabx') print ' checked'?>>
<label for="output_rabx">RABX</label>

<input type="submit" value="Go">
</p>
</form>
<?php
    if ($explorer) {
        $qs = array();
        foreach ($methods[$method]['parameters'] as $parameter) {
            if (get_http_var($parameter))
                $qs[] = _htmlspecialchars(rawurlencode($parameter) . '=' . urlencode(get_http_var($parameter)));
        }
        print '<h4><a name="output"></a>Output</h4>';
        print '<p>URL for this: <strong>http://www.theyworkforyou.com/api/';
        print $method . '?' . join('&amp;', $qs) . '&amp;output='._htmlspecialchars(get_http_var('output')).'</strong></p>';
        print '<pre>' . _htmlspecialchars($explorer) . '</pre>';
    }
    $sidebar = api_sidebar();
    $PAGE->stripe_end(array($sidebar));
    $PAGE->page_end();
}

function api_front_page($error = '') {
    global $PAGE, $methods, $this_page, $THEUSER;
    $this_page = 'api_front';
    $PAGE->page_start();
    $PAGE->stripe_start();
    if ($error) {
        print "<p style='color: #cc0000'>$error</p>";
    }

$em = join('&#64;', array('enquiries', 'mysociety.org'));

?>

<h3>Overview</h3>
<p>
    Welcome to TheyWorkForYou's API section. The API (Application Programming
    Interface) is a way of querying our database for information.
</p>
<p>
    To use the API you need <a href="key">an API key</a>, and you may need a
    license from us.
</p>
<p>
    The documentation for each individual API function is linked from this
    page: you can read what each function does, and test it out, without
    needing an API key or to write any code.
</p>

<h3>Terms of usage</h3>
<p>
    Low volume, charitable use of the API is free. This means direct use by
    registered charities, or individuals pursuing a non-profit project on an
    unpaid basis, with a volume of up to 50,000 calls per year. All other use
    requires a licence, as a contribution towards the costs of providing this
    service. Please email us at <a href="mailto:<?=$em?>"><?=$em?></a> if your
    usage is likely to fall into the non-free bracket: <strong>unlicensed users
    may be blocked without warning</strong>.
</p>

<h3>Pricing</h3>
<p>
    &pound;500 per year for up to 50,000 calls (free for charitable usage)<br/>
    &pound;1,000 per year for up to 100,000 calls<br/>
    &pound;2,000 per year for up to 200,000 calls<br/>
    &pound;2,500 per year for up to 300,000 calls<br/>
    &pound;3,000 per year for up to 500,000 calls<br/>
</p>
<p>In addition, we offer a 50% discount on the above rates for charitable usage.</p>

<h3>Credits</h3>
<p>Parliamentary material (that's data returned from getDebates, getWrans, and
getWMS) may be reused under the terms of the
<a href="http://www.parliament.uk/site-information/copyright/">Open Parliament Licence</a>.
Our own data &ndash; lists of MPs, Lords, constituencies and so on &ndash; is
available under the
<a href="http://creativecommons.org/licenses/by-sa/2.5/">Creative Commons
Attribution-ShareAlike license version 2.5</a>.

<p>Please credit us by linking to <a href="http://www.theyworkforyou.com/">TheyWorkForYou</a>
with wording such as "Data service provided by TheyWorkForYou" on the page
where the data is used. This attribution is optional if you've paid for use of
the service.


<h3>Technical documentation</h3>
<p>
    All requests are made by GETting a particular URL with a number of
    parameters. <em>key</em> is required; <em>output</em> is optional, and
    defaults to <kbd>js</kbd>.
</p>
<p align="center">
    <strong>http://www.theyworkforyou.com/api/<em>function</em>?key=<em>key</em>&amp;output=<em>output</em>&amp;<em>other_variables</em></strong>
</p>
<p>
    The current version of the API is <em>1.0.0</em>. If we make changes to
    the API functions, we'll increase the version number and make it an
    argument so you can still use the old version.
</p>

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

<h3>Bindings</h3>

<p>These help you interface with the API more easily in a particular language:</p>
<ul>
<li><a href="https://github.com/rubenarakelyan/twfyapi/">ASP.net</a> (thanks to Ruben Arakelyan)</li>
<li><a href="https://github.com/rhinocratic/twfy">Clojure</a> (thanks to Andrew Baxter)</li>
<li><a href="https://github.com/jamtho/twfy">Common Lisp</a> (thanks to James Thompson)</li>
<li><a href="https://github.com/rubenarakelyan/twfyapi/">JavaScript</a> (thanks to Ruben Arakelyan)</li>
<li><a href="https://sourceforge.net/projects/twfyjavaapi">Java</a> (thanks to Mitch Kent)</li>
<li><a href="http://search.cpan.org/perldoc?WebService::TWFY::API">Perl</a> (thanks to Spiros Denaxas)</li>
<li><a href="https://github.com/rubenarakelyan/twfyapi/">PHP</a> (thanks to Ruben Arakelyan)</li>
<li><a href="http://code.google.com/p/twfython/">Python</a> (thanks to Paul Doran)</li>
<li><a href="http://github.com/bruce/twfy">Ruby</a> (thanks to Bruce Williams and Martin Owen)</li>
<li><a href="https://github.com/ifraixedes/node-theyworkforyou-api">Node</a> (thanks to Ivan Fraixedes)</li>
</ul>

<p>If anyone wishes to write bindings for the API in any language, please
do so, let us know and we'll link to it here. You might want to
<a href="https://groups.google.com/a/mysociety.org/forum/#!forum/theyworkforyou">join our mailing list</a>
to discuss things.</p>

<h3>Example</h3>

<ul>
<li><a href="javascript:function foo(r) {if (r.twfy.url)window.location=r.twfy.url;};(function () {var s=document.createElement('script');s.setAttribute('src','http://theyworkforyou.com/api/convertURL?key=Gbr9QgCDzHExFzRwPWGAiUJ5&callback=foo&url='+encodeURIComponent(window.location));s.setAttribute('type','text/javascript');document.getElementsByTagName('head')[0].appendChild(s);})()">Hansard prettifier</a> - drag this bookmarklet to your bookmarks bar, or bookmark it. Then if you ever find yourself on the official site, clicking this will try and take you to the equivalent page on TheyWorkForYou. (Tested in IE, Firefox, Opera.)</li>
</ul>

<?php
    $sidebar = api_sidebar();
    $PAGE->stripe_end(array($sidebar));
    $PAGE->page_end();
}

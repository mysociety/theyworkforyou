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
            api_error('No API key provided. Please see https://www.theyworkforyou.com/api/key for more information.');
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
                    include_once 'api_' . $method . '.php';
                    api_call_user_func_or_error('api_' . $method . '_' . $parameter, [$q_param], 'API call not yet functional', 'api');
                    break;
                }
            }
            if ($match == 1 && (get_http_var('output') || !get_http_var('docs'))) {
                if ($data['required']) {
                    api_error('No parameter provided to function "' .
                    _htmlspecialchars($q_method) .
                        '". Possible choices are: ' .
                        join(', ', $data['parameters']));
                } else {
                    include_once 'api_' . $method . '.php';
                    api_call_user_func_or_error('api_' . $method, [], 'API call not yet functional', 'api');
                    break;
                }
            }
            break;
        }
    }
    if (!$match) {
        api_log_call($key);
        $msg = 'Unknown function "' . _htmlspecialchars($q_method) .
            '". Possible functions are: ' .
            join(', ', array_keys($methods));
        if (get_http_var('output')) {
            api_error($msg);
        } else {
            api_front_page($msg);
        }
    } else {
        if (get_http_var('docs')) {
            $explorer = ob_get_clean();
            api_documentation_front($method, $explorer);
        }
    }
} elseif (get_http_var('docs')) {
    api_front_page();
} else {
    $subscription = new MySociety\TheyWorkForYou\Subscription($THEUSER);
    MySociety\TheyWorkForYou\Renderer::output('static/api-index', [
        'subscription' => $subscription->stripe,
    ]);
}

function api_documentation_front($method, $explorer) {
    global $PAGE, $this_page, $DATA, $THEUSER;
    $this_page = 'api_doc_front';
    $DATA->set_page_metadata($this_page, 'title', "$method function");
    $PAGE->page_start();
    $PAGE->stripe_start();
    include_once 'api_' . $method . '.php';
    print '<p align="center"><strong>https://www.theyworkforyou.com/api/' . $method . '</strong></p>';
    api_call_user_func_or_error('api_' . $method . '_front', [], 'No documentation yet', 'html');
    if ($method != 'getQuota') {
        api_documentation_explorer($method, $explorer);
    }

    $subscription = new MySociety\TheyWorkForYou\Subscription($THEUSER);
    $sidebar = api_sidebar($subscription);
    $PAGE->stripe_end([$sidebar]);
    $PAGE->page_end();
}

function api_documentation_explorer($method, $explorer) {
    global $methods;
    ?>
<h4>Explorer</h4>
<p>Try out this function without writing any code!</p>
<form method="get" action="?#output">
<p>
<?php foreach ($methods[$method]['parameters'] as $parameter) {
    print $parameter . ': <input type="text" name="' . $parameter . '" value="';
    if ($val = get_http_var($parameter)) {
        print _htmlspecialchars($val);
    }
    print '" size="30"><br>';
}
    ?>
Output:
<input id="output_json" type="radio" name="output" value="json"<?php if (get_http_var('output') == 'json' || !get_http_var('output')) {
    print ' checked';
}?>>
<label for="output_json" class="inline">JSON</label>
<input id="output_js" type="radio" name="output" value="js"<?php if (get_http_var('output') == 'js') {
    print ' checked';
}?>>
<label for="output_js" class="inline">JS</label>
<input id="output_xml" type="radio" name="output" value="xml"<?php if (get_http_var('output') == 'xml') {
    print ' checked';
}?>>
<label for="output_xml" class="inline">XML</label>
<input id="output_php" type="radio" name="output" value="php"<?php if (get_http_var('output') == 'php') {
    print ' checked';
}?>>
<label for="output_php" class="inline">Serialised PHP</label>
<input id="output_rabx" type="radio" name="output" value="rabx"<?php if (get_http_var('output') == 'rabx') {
    print ' checked';
}?>>
<label for="output_rabx" class="inline">RABX</label>

<input type="submit" value="Go">
</p>
</form>
<?php
    if ($explorer) {
        $qs = [];
        foreach ($methods[$method]['parameters'] as $parameter) {
            if (get_http_var($parameter)) {
                $qs[] = _htmlspecialchars(rawurlencode($parameter) . '=' . urlencode(get_http_var($parameter)));
            }
        }
        print '<h4><a name="output"></a>Output</h4>';
        print '<p>URL for this: <strong>https://www.theyworkforyou.com/api/';
        print $method . '?' . join('&amp;', $qs) . '&amp;output=' . _htmlspecialchars(get_http_var('output')) . '</strong></p>';
        print '<pre>' . _htmlspecialchars($explorer) . '</pre>';
    }
}

function api_front_page($error = '') {
    global $PAGE, $methods, $this_page, $THEUSER;
    $this_page = 'api_front';
    $PAGE->page_start();
    $PAGE->stripe_start();
    if ($error) {
        print "<p style='color: #cc0000'>$error</p>";
    }

    $subscription = new MySociety\TheyWorkForYou\Subscription($THEUSER);

    ?>

<p>
    Welcome to TheyWorkForYou’s API section. The API (Application Programming
    Interface) is a way of querying our database for information.
</p>

<?php if ($subscription->stripe) { ?>
<p align="center"><big>
    <a href="/api/key">Manage your keys and payments</a>
</big></p>
<?php } else { ?>
<p align="center"><big>
    To use the API you need to <a href="/api/key">get an API key</a>.
</big></p>
<?php } ?>

<p>
    The documentation for each individual API function is linked from this
    page: you can read what each function does, and test it out, without
    needing an API key or to write any code.
</p>

<p>
    <strong>Important note:</strong> Politicians’ contact details can’t be
    obtained via this API. If that’s what you’re looking for, see
    <a href="https://everypolitician.org/">EveryPolitician</a> instead.
    APIs and datasets for other mySociety services can be found on our
    data portal, <a href="https://data.mysociety.org">data.mysociety.org</a>.
</p>

<h3 id="plans">Pricing</h3>

<ul>
    <li>&pound;20 per month for 1,000 calls per month (free for charitable usage)<br/>
    <li>&pound;50 per month for 5,000 calls<br/>
    <li>&pound;100 per month for 10,000 calls<br/>
    <li>&pound;300 per month for unlimited calls
</ul>

<p>In addition, we offer a 50% discount on the above rates for charitable usage.
This means direct use by registered charities, or individuals pursuing a
non-profit project on an unpaid basis.</p>

<p>Please read our full <a href="/api/terms">terms of usage</a>, including
licence and attribution requirements.</p>

<?php if ($subscription->stripe) { ?>
<p align="center"><big>
    <a href="/api/key">Manage your keys and payments</a>
</big></p>
<?php } else { ?>
<p align="center"><big>
    To use the API you need to <a href="/api/key">get an API key</a>.
</big></p>
<?php } ?>


<hr>

<h2>Technical documentation</h2>
<p>
    All requests are made by GETting a particular URL with a number of
    parameters. <em>key</em> is required; <em>output</em> is optional, and
    defaults to <kbd>js</kbd>.
</p>
<p align="center">
    <strong>https://www.theyworkforyou.com/api/<em>function</em>?key=<em>key</em>&amp;output=<em>output</em>&amp;<em>other_variables</em></strong>
</p>
<p>
    The current version of the API is <em>1.0.0</em>. If we make changes to
    the API functions, we’ll increase the version number and make it an
    argument so you can still use the old version.
</p>

<table>
<tr valign="top">
<td width="60%">

<h3>Outputs</h3>
<p>The <em>output</em> argument can take any of the following values:
<ul>
<li><strong>xml</strong>. XML. The root element is twfy.</li>
<li><strong>php</strong>. Serialized PHP, that can be turned back into useful information with the unserialize() command. Quite useful in Python as well, using e.g. <a href="https://pypi.org/project/phpserialize/">phpserialize</a>.</li>
<li><strong>json</strong>. JSON data.</li>
<li><strong>js</strong>. A JavaScript object, with data in ISO-8859-1. You can
provide a callback function with the <em>callback</em> variable, and then that
function will be called with the data as its argument.</li>
<li><strong>rabx</strong>. “RPC over Anything But XML”.</li>
</ul>

</td><td>

<h3>Errors</h3>

<p>If there’s an error, either in the arguments provided or in trying to perform the request,
this is returned as a top-level error string, ie. in XML it returns
<code>&lt;twfy&gt;&lt;error&gt;ERROR&lt;/error&gt;&lt;/twfy&gt;</code>;
in JS <code>{"error":"ERROR"}</code>;
and in PHP and RABX a serialised array containing one entry with key <code>error</code>.

</td></tr></table>

<h3>Bindings</h3>

<p>These help you interface with the API more easily in a particular language:</p>
<ul>
<li><a href="https://github.com/rubenarakelyan/twfyapi-aspnet/">ASP.net</a> (thanks to Ruben Arakelyan)</li>
<li><a href="https://github.com/rhinocratic/twfy">Clojure</a> (thanks to Andrew Baxter)</li>
<li><a href="https://github.com/jamtho/twfy">Common Lisp</a> (thanks to James Thompson)</li>
<li><a href="https://github.com/rubenarakelyan/twfyapi-js">JavaScript</a> (thanks to Ruben Arakelyan)</li>
<li><a href="https://sourceforge.net/projects/twfyjavaapi">Java</a> (thanks to Mitch Kent)</li>
<li><a href="https://github.com/ifraixedes/node-theyworkforyou-api">Node</a> (thanks to Ivan Fraixedes)</li>
<li><a href="https://metacpan.org/pod/WebService::TWFY::API">Perl</a> (thanks to Spiros Denaxas)</li>
<li><a href="https://github.com/rubenarakelyan/twfyapi/">PHP</a> (thanks to Ruben Arakelyan)</li>
<li><a href="https://code.google.com/archive/p/twfython/">Python</a> (thanks to Paul Doran)</li>
<li><a href="https://github.com/conjugateprior/twfy">R</a> (thanks to Will Lowe)</li>
<li><a href="https://github.com/bruce/twfy">Ruby</a> (thanks to Bruce Williams and Martin Owen)</li>
</ul>

<p>If anyone wishes to write bindings for the API in any language, please
do so, let us know and we’ll link to it here. You might want to
<a href="https://groups.google.com/a/mysociety.org/forum/#!forum/theyworkforyou">join our mailing list</a>
to discuss things.</p>

<h3>Example</h3>

<ul>
<li><a href="javascript:function foo(r) {if (r.twfy.url)window.location=r.twfy.url;};(function () {var s=document.createElement('script');s.setAttribute('src','https://www.theyworkforyou.com/api/convertURL?key=Gbr9QgCDzHExFzRwPWGAiUJ5&callback=foo&url='+encodeURIComponent(window.location));s.setAttribute('type','text/javascript');document.getElementsByTagName('head')[0].appendChild(s);})()">Hansard prettifier</a> - drag this bookmarklet to your bookmarks bar, or bookmark it. Then if you ever find yourself on the official site, clicking this will try and take you to the equivalent page on TheyWorkForYou. (Tested in IE, Firefox, Opera.)</li>
</ul>

<h3>Bulk Analysis</h3>

<p>
If you are doing analysis over long periods of time, or generating statistical data then you might find our <a href="https://parser.theyworkforyou.com/">raw data</a>
more suitable that using the API.
</p>

<?php
        $sidebar = api_sidebar($subscription);
    $PAGE->stripe_end([$sidebar]);
    $PAGE->page_end();
}

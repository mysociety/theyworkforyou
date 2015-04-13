<?php

include_once '../../includes/easyparliament/init.php';
include_once INCLUDESPATH . 'postcode.inc';
include_once './api_functions.php';
include_once INCLUDESPATH . '../../commonlib/phplib/auth.php';

$a = auth_ab64_encode(urandom_bytes(32));

$this_page = 'api_key';
$PAGE->page_start();
$PAGE->stripe_start();
?>

<h3>About API Keys</h3>
<p>TheyWorkForYou API calls require a key, so that we can monitor usage
of the service, and provide usage stats to you. Please see <a href="/api">the
API overview</a> for how to use your key.<br/><br/>

<?php
if ($THEUSER->loggedin()) {
    if (get_http_var('create_key') && get_http_var('reason')) {
        $estimated_usage = (int) get_http_var('estimated_usage');
        $commercial = get_http_var('commercial');
        create_key($commercial, get_http_var('reason'),  $estimated_usage);
        if ($commercial == '1' || $estimated_usage > 50000) {
            echo '<p><strong>It looks like your usage may fall outside of our free-of-charge bracket: if that\'s the case, this key might get blocked, so we\'d advise you to email us at <a href="enquiries@mysociety.org">enquiries@mysociety.org</a> to discuss licensing options.</strong></p>';
        }
    }
    $db = new ParlDB;
    $q = $db->query('SELECT api_key, commercial, created, reason, estimated_usage FROM api_key WHERE user_id=' . $THEUSER->user_id());
    $keys = array();
    for ($i=0; $i<$q->rows(); $i++) {
        $keys[] = array($q->field($i, 'api_key'), $q->field($i, 'commercial'), $q->field($i, 'created'), $q->field($i, 'reason'), $q->field($i, 'estimated_usage'));
    }
    if ($keys) {
        echo '<h3>Your keys</h3> <ul>';
    }
    foreach ($keys as $keyarr) {
        list($key, $commercial, $created, $reason, $estimated_usage) = $keyarr;
        echo '<li><span style="font-size:200%">' . $key . '</span><br><span style="color: #666666;">';
        if ($commercial==1) echo 'Commercial key,';
        elseif ($commercial==-1) echo 'Key';
        else echo 'Non-commercial key,';
        echo ' created ', $created, '; ', $reason, '; estimated usage ', $estimated_usage;
        echo '</span><br><em>Usage statistics</em>: ';
        $q = $db->query('SELECT count(*) as count FROM api_stats WHERE api_key="' . $key . '" AND query_time > NOW() - interval 1 day');
        $c = $q->field(0, 'count');
        echo "last 24 hours: $c, ";
        $q = $db->query('SELECT count(*) as count FROM api_stats WHERE api_key="' . $key . '" AND query_time > NOW() - interval 1 week');
        $c = $q->field(0, 'count');
        echo "last week: $c, ";
        $q = $db->query('SELECT count(*) as count FROM api_stats WHERE api_key="' . $key . '" AND query_time > NOW() - interval 1 month');
        $c = $q->field(0, 'count');
        echo "last month: $c";
        echo '</p>';
    }
    if ($keys) {
        echo '</ul>';
    }
    api_key_form();
} else {
    echo ' The key is tied to your TheyWorkForYou account,
so if you don\'t yet have one, please <a href="/user/?pg=join">sign up</a>, then
return here to get a key.</p>';
    echo '<p style="font-size:200%"><strong><a href="/user/login/?ret=/api/key">Sign in</a></strong> (or <a href="/user/?pg=join">sign up</a>) to get an API key.</p>';
}

$sidebar = api_sidebar();
$PAGE->stripe_end(array($sidebar));
$PAGE->page_end();

# ---

function create_key($commercial, $reason, $estimated_usage) {
    global $THEUSER;
    $key = auth_ab64_encode(urandom_bytes(16));
    $db = new ParlDB;
    if ($commercial=='') $commercial = 0;
    $db->query('INSERT INTO api_key (user_id, api_key, commercial, created, reason, estimated_usage) VALUES
        (:user_id, :key, :commercial, NOW(), :reason, :estimated_usage)', array(
        ':user_id' => $THEUSER->user_id(),
        ':key' => $key,
        ':commercial' => $commercial,
        ':reason' => $reason,
        ':estimated_usage' => $estimated_usage
        ));
}

function api_key_form() {
?>
<br>
<h3>Get a new key</h3>
<form action="/api/key" method="post">
<p>About you:<br/>
<input id="non_comm" type="radio" name="commercial" value="0"> <label for="non_comm">an individual pursuing a non-profit project on an unpaid basis</label><br/>
<input id="non_comm_2" type="radio" name="commercial" value="2"> <label for="non_comm_2">a registered charity</label><br/>
<input id="comm" type="radio" name="commercial" value="1"> <label for="comm">neither of the above</label>
</p>
<p><label for="reason">Please describe what you're going to use the key for:</label>
<br>
<textarea id="reasons" name="reason" rows=7 cols=40></textarea>
</p>
<p>What's your estimated annual API call volume?<br/>
    <input id="estimated_usage_50k" type="radio" name="estimated_usage" value="50000"> <label for="estimated_usage_50k">up to 50,000</label><br/>
    <input id="estimated_usage_100k" type="radio" name="estimated_usage" value="100000"> <label for="estimated_usage_100k">100,000</label><br/>
    <input id="estimated_usage_200k" type="radio" name="estimated_usage" value="200000"> <label for="estimated_usage_200k">200,000</label><br/>
    <input id="estimated_usage_300k" type="radio" name="estimated_usage" value="300000"> <label for="estimated_usage_300k">300,000</label><br/>
    <input id="estimated_usage_500k" type="radio" name="estimated_usage" value="500000"> <label for="estimated_usage_500k">500,000</label><br/>
    <input id="estimated_usage_na" type="radio" name="estimated_usage" value="0"> <label for="estimated_usage_na">not sure yet</label>
</p>
<p><input type="submit" value="Get key">
<input type="hidden" name="create_key" value="1">
</form>
<?php
}

<?

include_once '../../includes/easyparliament/init.php';
include_once '../../includes/postcode.inc';
include_once 'api_functions.php';
include_once '../../../../phplib/auth.php';

$a = auth_ab64_encode(random_bytes(32));

$this_page = 'api_key';
$PAGE->page_start();
$PAGE->stripe_start();
api_key_current_message();
echo '<p>TheyWorkForYou API calls require a key, so that we can monitor usage
of the service, and provide usage stats to you.';
if ($THEUSER->loggedin()) {
	if (get_http_var('create_key')) {
		create_key(get_http_var('commercial'), get_http_var('reason'));
	}
	$db = new ParlDB;
	$q = $db->query('SELECT api_key, commercial, created, reason FROM api_key WHERE user_id=' . $THEUSER->user_id());
	$keys = array();
	for ($i=0; $i<$q->rows(); $i++) {
		$keys[] = array($q->field($i, 'api_key'), $q->field($i, 'commercial'), $q->field($i, 'created'), $q->field($i, 'reason'));
	}
	if ($keys) {
		echo '<h3>Your keys</h3> <ul>';
	}
	foreach ($keys as $keyarr) {
		list($key, $commercial, $created, $reason) = $keyarr;
		echo '<li><span style="font-size:200%">' . $key . '</span><br><span style="color: #666666;">';
		if ($commercial==1) echo 'Commercial key';
		elseif ($commercial==-1) echo 'Key';
		else echo 'Non-commercial key';
		echo ', created ', $created; # , ' ', $reason;
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
	echo '<p style="font-size:200%"><strong><a href="/user/login/?ret=/api/key">Log in</a></strong> (or <a href="/user/?pg=join">sign up</a>) to get an API key.</p>';
}

$sidebar = api_sidebar();
$PAGE->stripe_end(array($sidebar));
$PAGE->page_end();

# ---

function create_key($commercial, $reason) {
	global $THEUSER;
	$key = auth_ab64_encode(random_bytes(16));
	$db = new ParlDB;
	$db->query('INSERT INTO api_key (user_id, api_key, commercial, created, reason) VALUES
		(' . $THEUSER->user_id() . ', "' . $key . '", ' . $commercial . ', NOW(), "' . $reason . '")');
}

function api_key_form() {
?>
<br>
<h3>Apply for a new key</h3>
<form action="/api/key" method="post">
<p>Is your application for:
<input id="non_comm" type="radio" name="commercial" value="0"> <label for="non_comm">Non-commercial use</label>
<input id="comm" type="radio" name="commercial" value="1"> <label for="comm">Commercial use</label>
<p><label for="reason">Please describe what you're going to use the key for:</label>
<br>
<textarea id="reasons" name="reason" rows=7 cols=40></textarea>
<p><input type="submit" value="Get key">
<input type="hidden" name="create_key" value="1">
</form>
<?
}

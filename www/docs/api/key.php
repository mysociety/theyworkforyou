<?

include_once '../../includes/easyparliament/init.php';
include_once '../../includes/postcode.inc';
include_once 'api_functions.php';
include_once '../../../../phplib/auth.php';

$a = auth_ab64_encode(random_bytes(32));

$this_page = 'api_key';
$PAGE->page_start();
$PAGE->stripe_start();
?>
<p id="video_already" style="text-align:left"><em>Current API users</em>: We
realise the inconvenience of adding a key to an API that previously did not
require one. However, we feel it is now necessary in order to monitor the
service for abuse and help with support and maintenance. You will also be
able to view usage stats of your key.</p>

<p>TheyWorkForYou API calls require a key, so that we can monitor usage of the service,
and provide usage stats to you.

<?

if ($THEUSER->loggedin()) {
	$db = new ParlDB;
	$q = $db->query('SELECT api_key FROM users WHERE user_id=' . $THEUSER->user_id());
	$key = $q->field(0, 'api_key');
	if (!$key) {
		# Haven't yet got a key, generate one
		$key = auth_ab64_encode(random_bytes(16));
		$db->query('UPDATE users SET api_key="' . $key . '" where user_id=' . $THEUSER->user_id());
	}
	echo '<p style="font-size:200%">Your key is: ', $key, '</p>';
	echo '<h3>Usage stats</h3> <ul style="font-size:125%">';
	$q = $db->query('SELECT count(*) as count FROM api_stats WHERE api_key="' . $key . '" AND query_time > NOW() - interval 1 day');
	$c = $q->field(0, 'count');
	echo "<li>Last 24 hours: $c";
	$q = $db->query('SELECT count(*) as count FROM api_stats WHERE api_key="' . $key . '" AND query_time > NOW() - interval 1 week');
	$c = $q->field(0, 'count');
	echo "<li>Last week: $c";
	$q = $db->query('SELECT count(*) as count FROM api_stats WHERE api_key="' . $key . '" AND query_time > NOW() - interval 1 month');
	$c = $q->field(0, 'count');
	echo "<li>Last month: $c";
	echo '</ul>';
} else {
	echo ' This key is tied to your TheyWorkForYou account,
so if you don\'t yet have one, please <a href="/user/?pg=join">sign up</a>, then 
return here to find your key.</p>';
	echo '<p style="font-size:200%"><strong><a href="/user/login/?ret=/api/key">Log in</a></strong> (or <a href="/user/?pg=join">sign up</a>) to view your API key.</p>';
}

$sidebar = api_sidebar();
$PAGE->stripe_end(array($sidebar));
$PAGE->page_end();


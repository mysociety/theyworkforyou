<?

ini_set('display_errors', 'On');
include_once '../min-init.php';
include_once INCLUDESPATH . 'easyparliament/member.php';
include_once '../../api/api_functions.php';

$action = get_http_var('action');
$pid = get_http_var('pid');
if (!$pid) output_error('<error>No ID</error>');
$member = load_member($pid);

twfy_debug_timestamp();

switch ($action) {
	# Resources
	case 'rmi-resource':
		$title = "Register of Members' Interests: " . $member->full_name();
		output_resource($title, $member->extra_info['register_member_interests_html']);
		break;
	case 'voting-record-resource':
		$title = "Voting record: " . $member->full_name();
		output_resource($title, 'Not done yet');
		break;
	case 'expenses-resource':
		include_once INCLUDESPATH . 'easyparliament/expenses.php';
		$title = "Allowances: " . $member->full_name();
		output_resource($title, expenses_display_table($member->extra_info)); # XXX This is currently broken
		break;

	# Components
	case 'voting-record-component':
		echo '<p>Votes go here</p>';
		echo '<p><a href="{microapp-link:resource:mp_voting_record:key:[aristotle_id]}">More votes for ', $member->full_name(), '</a></p>';
		break;
	case 'key-facts-component':
		echo '<h2>Key facts</h2>';
		echo '<p>Will go here</p>';
		echo '<p><a href="http://www.guardian.co.uk/politics/person/[aristotle_id]">Full profile</a></p>';
		break;
	case 'recent-speeches-component':
		include_once INCLUDESPATH . 'easyparliament/hansardlist.php';
		include_once INCLUDESPATH . 'easyparliament/searchengine.php';
		$HANSARDLIST = new HANSARDLIST();
		$searchstring = "speaker:$pid";
		global $SEARCHENGINE;
		$SEARCHENGINE = new SEARCHENGINE($searchstring); 
		$args = array (
			's' => $searchstring,
			'p' => 1,
			'num' => 1,
		       'pop' => 1,
			'o' => 'd',
		);
		$HANSARDLIST->display('search_min', $args);
	        twfy_debug_timestamp();
		echo '<p><a href="http://www.theyworkforyou.com/search/?pid=', $member->person_id(), '">More speeches from ', $member->full_name(), '</a></p>';
		break;
	case 'parliamentary-jobs-component':
		echo 'To do';
		break;
	case 'expenses-component':
		include_once INCLUDESPATH . 'easyparliament/expenses.php';
		echo expenses_mostrecent($member->extra_info);
		echo '<p><a href="{microapp-link:resource:mp-expenses:key:[aristotle_id]}">More expenses</a></p>';
		break;
	case 'rmi-component':
		$rmi = $member->extra_info['register_member_interests_html'];
		$show_more = false;
		if (preg_match('#(<div class="regmemcategory">.*?<div class="regmemcategory">.*?)<div class="regmemcategory"#s', $rmi, $m)) {
			$rmi = $m[1];
			$show_more = true;
		}
		if (strlen($rmi) > 50 && preg_match('#(<div class="regmemcategory">.*?)<div class="regmemcategory"#s', $rmi, $m)) {
			$rmi = $m[1];
			$show_more = true;
		}
		echo $rmi;
		if ($show_more) {
			echo '<p><a href="{microapp-link:resource:register-of-members-interests:key:[aristotle_id]}">More from the register</a></p>';
		}
		break;
	default:
		output_error('Unknown action');
}

twfy_debug_timestamp();

# ---

function load_member($pid) {
	$member = new MEMBER(array('person_id' => $pid));
	if (!$member->valid) output_error('Unknown ID');
	$member->load_extra_info();
	return $member;
}

function output_error($str) {
	echo '<error>', $str, '</error>';
	exit;
}

function output_resource($title, $body) {
	echo "<html>
<head><title>$title | Politics | The Guardian</title></head>
<body>
$body
</body>
</html>
";
}

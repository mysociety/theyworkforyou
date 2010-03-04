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
$resources_path = "/gadget/guardian/resources/";
switch ($action) {
	# Resources
	case 'rmi-resource':
                $title = "Extract from the register of members' interests"; 
                $body = "<h1>" . $member->full_name() . ": <span>Members' Interests</span></h1>";
                $body .= "<h2>$title</h2>";
                $body .= $member->extra_info['register_member_interests_html'];
		if (isset($member->extra_info['register_member_interests_date'])) {
		    $body .= '<div class="rmi-lastupdate">Register last updated: ';
		    $body .= format_date($member->extra_info['register_member_interests_date'], SHORTDATEFORMAT);
		    $body .= '. </div>';
	        }
                $body .= '<div class="mysociety-footer"><span>Powered by </span><img src="http://' . DOMAIN .
'/gadget/guardian/mysociety.gif" alt="mySociety"></div>';
		output_resource($title, $body, 'rmi-full') ;
		break;
	case 'voting-record-resource':
		$title = "Voting record: " . $member->full_name();
		output_resource($title, 'Not done yet', 'voting-full');
		break;
	case 'expenses-resource':
                $start_year = get_http_var('start_year');
                if (! preg_match('/^20\d\d$/', $start_year) ){
                    $start_year = null;
                } 
                if (empty($start_year)) {
                    $start_year = '2009';
                }
                $int_start_year = intval($start_year) - 2000; 
		include_once INCLUDESPATH . 'easyparliament/expenses.php';
		$title = "Allowances: " . $member->full_name();
                $body = "<h1>" . $member->full_name() . ": <span>Expenses</span></h1>";
		$body .= expenses_display_table($member->extra_info, $gadget=true, $int_start_year);
                output_resource($title, $body, 'expenses-full');
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
		echo '<p><a href="http://www.theyworkforyou.com/search/?pid=', $member->guardian_aristotle_id(), '">More 
speeches from ', $member->full_name(), '</a></p>';
		break;
	case 'parliamentary-jobs-component':
		echo 'To do';
		break;
	case 'expenses-component':
		include_once INCLUDESPATH . 'easyparliament/expenses.php';
                $body = expenses_mostrecent($member->extra_info, $gadget=true);
		$body .= "<p class=\"more\"><a 
href=\"{microapp-href:http://" . DOMAIN . $resources_path . "mp/expenses/$member->guardian_aristotle_id}\">More 
expenses</a></p>";
                $body .= '<div class="mysociety-footer">Powered by <img src="http://' . DOMAIN . '/gadget/guardian/mysociety.gif" alt="mySociety"></div>';
                output_component($body, 'expenses-brief');                
		break;
	case 'rmi-component':
                $show_more = false;
		$rmi = $member->extra_info['register_member_interests_html'];
		if (preg_match('#(<div class="regmemcategory">.*?<div class="regmemcategory">.*?)<div class="regmemcategory"#s', $rmi, $m)) {
			$rmi = $m[1];
			$show_more = true;
		}
		if (strlen($rmi) > 50 && preg_match('#(<div class="regmemcategory">.*?)<div class="regmemcategory"#s', $rmi, $m)) {
			$rmi = $m[1];
			$show_more = true;
		}
                $body = "<div id=\"rmi-header\">Extract from the register of members' interests</div>";	
                $body .= $rmi;
		if ($show_more) {
			$body .= "<p class=\"more\"><a 
href=\"{microapp-href:http://" . DOMAIN . $resources_path . "mp/rmi/$member->guardian_aristotle_id}\">Full members' 
interests</a></p>";
		}
                $body .= '<div class="mysociety-footer">Powered by <img src="http://' . DOMAIN .
'/gadget/guardian/mysociety.gif" alt="mySociety"></div>';
                output_component($body, 'rmi-brief');
		break;
	default:
		output_error('Unknown action');
}

twfy_debug_timestamp();

# ---

function load_member($pid) {
	$member = new MEMBER(array('guardian_aristotle_id' => $pid));
	if (!$member->valid) output_error('Unknown ID');
	$member->load_extra_info();
	return $member;
}

function output_error($str, $status_code = "404 Not Found") {
        header("HTTP/1.0 $status_code");
	echo '<error>', $str, '</error>';
	exit;
}

function output_component($body, $outer_div_id) {
        echo "
<style type=\"text/css\">@import \"http://" . DOMAIN . "/gadget/guardian/core.css\";</style>
<!--{microapp-css:/gadget/guardian/core.css}--> 
<div class=\"mysociety component\">
    <div id=\"$outer_div_id\">
        $body
    </div>
</div>
";
}

function output_resource($title, $body, $outer_div_id) {
	echo "<html>
<head>
  <title>$title | Politics | The Guardian
  </title>
<style type=\"text/css\">@import \"http://" . DOMAIN . "/gadget/guardian/core.css\";</style>
<!--{microapp-css:/gadget/guardian/core.css}-->
</head>
<body>
<style type=\"text/css\">@import \"http://" . DOMAIN . "/gadget/guardian/core.css\";</style>
<div class=\"mysociety\">
    <div id=\"$outer_div_id\">
         $body
    </div>
</div>
</body>
</html>
";
}

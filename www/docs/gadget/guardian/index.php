<?php

ini_set('display_errors', 'On');
include_once '../min-init.php';
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
                $body = "<h1>" . $member->full_name() . ": <span>Member's interests</span></h1>";
                $body .= "<h2>$title</h2>";
                $rmi  = $member->extra_info['register_member_interests_html'];
                if (strlen($rmi) == 0) {
                    output_error('No data');
                }
                $body .= $rmi;
        if (isset($member->extra_info['register_member_interests_date'])) {
            $body .= '<div class="rmi-lastupdate">Register last updated: ';
            $body .= format_date($member->extra_info['register_member_interests_date'], SHORTDATEFORMAT);
            $body .= '. </div>';
            }
                $body .= mysociety_footer();
        output_resource($title, $body, 'rmi-full') ;
        break;
    case 'voting-record-resource':
        $title = "Voting record: " . $member->full_name();
        output_resource($title, 'Not done yet', 'voting-full');
        break;
    case 'expenses-resource':
                $start_year = get_http_var('start_year');
                if (! preg_match('/^20\d\d$/', $start_year) ) {
                    $start_year = null;
                }
                if (empty($start_year)) {
                    $start_year = '2009';
                }
                $int_start_year = intval($start_year) - 2000;
        $title = "Allowances: " . $member->full_name();
                $body = "<h1>" . $member->full_name() . ": <span>Expenses</span></h1>";
                $table = \MySociety\TheyWorkForYou\Utility\Expenses::displaytable($member->extra_info, $gadget=true, $int_start_year);
                if (strlen($table) == 0) {
                    output_error('No data');
                }
        $body .= $table;
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

        if (defined('XAPIANDB') AND XAPIANDB != '') {
            if (file_exists('/usr/share/php/xapian.php')) {
                include_once '/usr/share/php/xapian.php';
            } else {
                twfy_debug('SEARCH', '/usr/share/php/xapian.php does not exist');
            }
        }

        global $SEARCHENGINE;
        $SEARCHENGINE = null;

        global $SEARCHLOG;
        $SEARCHLOG = new \MySociety\TheyWorkForYou\SearchLog();

        $HANSARDLIST = new \MySociety\TheyWorkForYou\HansardList();
        $searchstring = "speaker:$pid";
        global $SEARCHENGINE;
        $SEARCHENGINE = new \MySociety\TheyWorkForYou\SearchEngine($searchstring);
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
                $body = \MySociety\TheyWorkForYou\Utility\Expenses::mostRecent($member->extra_info, $gadget=true);
                if (strlen($body) == 0) {
                    output_error('No data');
                }
        $body .= "<p class=\"more\"><a
href=\"{microapp-href:http://" . DOMAIN . $resources_path . "mp/expenses/$member->guardian_aristotle_id}\">More
expenses</a></p>";
                $body .= mysociety_footer();
                output_component($body, 'expenses-brief');
        break;
    case 'rmi-component':
                $show_more = false;
        $rmi = $member->extra_info['register_member_interests_html'];
        if (strlen($rmi) == 0) {
                    output_error('No data');
                }
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
href=\"{microapp-href:http://" . DOMAIN . $resources_path . "mp/rmi/$member->guardian_aristotle_id}\">Full member's
interests</a></p>";
        }
                $body .= mysociety_footer();
                output_component($body, 'rmi-brief');
        break;
    default:
        output_error('Unknown action');
}

twfy_debug_timestamp();

# ---

function load_member($pid) {
    $member = new \MySociety\TheyWorkForYou\Member(array('guardian_aristotle_id' => $pid));
    if (!$member->valid) output_error('Unknown ID');
    $member->load_extra_info();
    return $member;
}

function mysociety_footer() {
        return '<div class="mysociety-footer"><span><a href="http://mysociety.org">Powered by
</a></span><a class="mysociety-footer-image-link" href="http://mysociety.org"><img src="http://' .
DOMAIN .
'/gadget/guardian/mysociety.gif"
alt="mySociety"></a></div>';
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

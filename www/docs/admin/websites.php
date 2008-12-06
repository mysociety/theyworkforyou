<?php

include_once '../../includes/easyparliament/init.php';
#include_once INCLUDESPATH . 'easyparliament/commentreportlist.php';
#include_once INCLUDESPATH . 'easyparliament/searchengine.php';
include_once INCLUDESPATH . 'easyparliament/member.php';
#include_once INCLUDESPATH . 'easyparliament/people.php';

$this_page = 'admin_mpurls';

$db = new ParlDB;

$scriptpath = '../../../scripts';


$PAGE->page_start();
$PAGE->stripe_start();

$out = '';
if (get_http_var('editperson') && get_http_var('url')) {
    $out = update_url();
}


if (get_http_var('editperson')) {
    $out .= edit_member_form();
} else {
    $out .= list_members();
}

$subnav = subnav();

print '<div id="adminbody">';
print $subnav;
print $out;
print '</div>';


function edit_member_form() {
    global $db; 
    $personid = get_http_var('editperson');
    $q = $db->query("SELECT member.person_id, house, title, first_name, last_name, constituency, data_value AS mp_website 
    FROM personinfo JOIN member ON member.person_id = personinfo.person_id WHERE data_key = 'mp_website'
    AND member.person_id = '" . mysql_real_escape_string($personid)."';");

    for ($row = 0; $row < $q->rows(); $row++) {    

        $mpname = member_full_name($q->field($row, 'house'), $q->field($row, 'title'), $q->field($row, 'first_name'), $q->field($row, 'last_name'), $q->field($row, 'constituency'));

        $out = "<h3>Edit MP:" .  $mpname  . " </h3>\n";

        $out .= '<form action="websites.php?editperson=' . $q->field($row, 'person_id') . '" method="post">';
        $out .= '<label for="url">Url:</label>';
        $out .= '<span class="formw"><input name="url" type="text"  size="60" value="' . $q->field($row, 'mp_website') . '" /></span>' . "\n";
        $out .= '<span class="formw"><input name="action" type="submit" value="Save URL"/></span>';
        $out .= '</form>';
    }
    return $out;
}

function list_members() {
    global $db; 
    $out = "<h2>MP's Websites</h2>\n";
    #$errors = array();
    #array_push($errors, 'Not got the photo.');
    $q = $db->query("SELECT member.person_id, house, title, first_name, last_name, constituency, data_value AS mp_website FROM personinfo JOIN member ON member.person_id = personinfo.person_id WHERE data_key = 'mp_website';");
        
        for ($row = 0; $row < $q->rows(); $row++) {
        $out .= '<p>';
        $mpname = member_full_name($q->field($row, 'house'), $q->field($row, 'title'), $q->field($row, 'first_name'), $q->field($row, 'last_name'), $q->field($row, 'constituency'));
        #$mpname = $q->field($row, 'title') . ' ' . $q->field($row, 'first_name')  . ' ' .  $q->field($row, 'last_name') . ' (' . $q->field($row, 'constituency') . ')';
        $out .= '' . $mpname . '<br />';
        $out .= '' . $q->field($row, 'mp_website');
        $out .= ' (<a href="websites.php?editperson=' . $q->field($row, 'person_id') . '" class="">Edit</a>) ';
        $out .= "</p>\n";
            
            #print $q->field($row, 'mp_website') . " for " . $q->field($row, 'first_name');
	    #	    if ($q->field($row, 'count') > 1)
	    #	    	$this->extra_info[$q->field($row, 'data_key').'_joint'] = true;
        }
    
    $out .= <<<EOF

EOF;
    return $out;
}

function update_url() {
    global $db; 
    global $scriptpath; 
    $out = '';
    $sysretval = 0;
    $personid = get_http_var('editperson');
    $q = $db->query("UPDATE personinfo SET data_value = '".mysql_real_escape_string(get_http_var('url'))."' 
    WHERE data_key = 'mp_website' AND personinfo.person_id = '".mysql_real_escape_string($personid)."';");
    if ($q->success()) {
        exec($scriptpath . "/db2xml.pl --update_person --personid=" . escapeshellarg($personid) . " --debug", $exec_output);
        $out = '<p id="warning">';
        foreach ($exec_output as $message) {$out .= $message . "<br />";}
        $out .= '</p>';
        # ../../../scripts/db2xml.pl  --update_person --personid=10001
    }
    if ($sysretval) {
        $out .= '<p id="warning">Update Successful</p>';
    }
    return $out;
}

function subnav() {
    $rettext = '';
    $subnav = array(
        'List Websites' => '/admin/websites.php',
    );
    
    $rettext .= '<div id="subnav_websites">';
    foreach ($subnav as $label => $path) {
        $rettext .=  '<a href="'. $path . '">'. $label .'</a>';
    }
    $rettext .=  '</div>';
    return $rettext;
}


$menu = $PAGE->admin_menu();

$PAGE->stripe_end(array(
	array(
		'type'		=> 'html',
		'content'	=> $menu
	)
));

$PAGE->page_end();

?>

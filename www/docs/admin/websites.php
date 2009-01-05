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
if (get_http_var('editperson') && get_http_var('action') === 'SaveURL') {
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
    FROM member LEFT JOIN personinfo ON member.person_id = personinfo.person_id AND  data_key = 'mp_website'
    WHERE member.person_id = '" . mysql_real_escape_string($personid)."';");

    for ($row = 0; $row < $q->rows(); $row++) {    

        $mpname = member_full_name($q->field($row, 'house'), $q->field($row, 'title'), $q->field($row, 'first_name'), $q->field($row, 'last_name'), $q->field($row, 'constituency'));

        $out = "<h3>Edit person: $mpname</h3>\n";

        $out .= '<form action="websites.php?editperson=' . $q->field($row, 'person_id') . '" method="post">';
        $out .= '<input name="action" type="hidden" value="SaveURL">';
        $out .= '<label for="url">URL:</label>';
        $out .= '<span class="formw"><input id="url" name="url" type="text"  size="60" value="' . $q->field($row, 'mp_website') . '"></span>' . "\n";
        $out .= '<span class="formw"><input name="btnaction" type="submit" value="Save URL"></span>';
        $out .= '</form>';
    }
    return $out;
}

function list_members() {
    global $db; 
    $out = '<ul>';
    # this returns everyone so possibly over the top maybe limit to member.house = '1'
    $q = $db->query("SELECT member.person_id, house, title, first_name, last_name, constituency, data_value FROM member 
    LEFT JOIN personinfo ON member.person_id = personinfo.person_id AND personinfo.data_key = 'mp_website' GROUP BY member.person_id ORDER BY last_name, first_name");
        
    for ($row = 0; $row < $q->rows(); $row++) {
        $out .= '<li>';
        $mpname = member_full_name($q->field($row, 'house'), $q->field($row, 'title'), $q->field($row, 'first_name'), $q->field($row, 'last_name'), $q->field($row, 'constituency'));
        $mp_website = $q->field($row, 'data_value');
        $out .= ' <small>[<a href="websites.php?editperson=' . $q->field($row, 'person_id') . '"';
	if ($mp_website) {
            $out .= ' title="Change URL ' . $mp_website . '">Edit URL</a>]</small>';
	} else {
            $out .= '>Add URL</a>]</small>';
	}
        $out .= ' ' . $mpname;
        if ($q->field($row, 'constituency') && $q->field($row, 'house')!=2) {
            $out .= ' (' . $q->field($row, 'constituency') . ')';        
        }
        $out .= "</li>\n";
    }
    $out .= '</ul>';
    return $out;
}

function update_url() {
    global $db; 
    global $scriptpath; 
    $out = '';
    $sysretval = 0;
    $personid = get_http_var('editperson');
    
    $q  = $db->query("DELETE FROM personinfo WHERE data_key = 'mp_website' AND personinfo.person_id = '".mysql_real_escape_string($personid)."';");

    if ($q->success()) {
        $q = $db->query("INSERT INTO personinfo (data_key, person_id, data_value) VALUES ('mp_website', '" . mysql_real_escape_string($personid) . "', '" . mysql_real_escape_string(get_http_var('url')). "');");
    }    

    if ($q->success()) {
        exec($scriptpath . "/db2xml.pl --update_person --personid=" . escapeshellarg($personid) . " --debug", $exec_output);
        $out = '<p id="warning">';
        foreach ($exec_output as $message) {$out .= $message . "<br>";}
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

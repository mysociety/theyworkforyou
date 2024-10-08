<?php

include_once '../../includes/easyparliament/init.php';
#include_once INCLUDESPATH . 'easyparliament/commentreportlist.php';
#include_once INCLUDESPATH . 'easyparliament/searchengine.php';
include_once INCLUDESPATH . 'easyparliament/member.php';

$this_page = 'admin_mpurls';

$db = new ParlDB();

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
    # XXX This is stupid, it fetches all memberships and then displays the last
    $query = "SELECT member.person_id, house, title, given_name, family_name, lordofname, constituency, data_value
        AS mp_website
        FROM person_names pn, member
        LEFT JOIN personinfo ON member.person_id = personinfo.person_id AND data_key = 'mp_website'
        WHERE member.person_id = :person_id
            AND member.person_id = pn.person_id AND pn.type='name'
            AND pn.end_date = (SELECT MAX(end_date) FROM person_names WHERE person_id=:person_id AND type='name')
        ORDER BY left_house DESC LIMIT 1";
    $row = $db->query($query, [
        ':person_id' => $personid,
    ])->first();

    $name = member_full_name($row['house'], $row['title'], $row['given_name'], $row['family_name'], $row['lordofname']);

    $out = "<h3>Edit person: $name</h3>\n";
    $out .= '<form action="websites.php?editperson=' . $row['person_id'] . '" method="post">';
    $out .= '<input name="action" type="hidden" value="SaveURL">';
    $out .= '<label for="url">URL:</label>';
    $out .= '<span class="formw"><input id="url" name="url" type="text"  size="60" value="' . $row['mp_website'] . '"></span>' . "\n";
    $out .= '<span class="formw"><input name="btnaction" type="submit" value="Save URL"></span>';
    $out .= '</form>';

    return $out;
}

function list_members() {
    global $db;
    $out = '<ul>';
    # this returns everyone so possibly over the top maybe limit to member.house = '1'
    $q = $db->query("SELECT house, member.person_id, title, given_name, family_name, lordofname, constituency, data_value
        FROM
        (SELECT person_id, MAX(end_date) max_date FROM person_names WHERE type='name' GROUP by person_id) md,
        person_names, member
        LEFT JOIN personinfo ON member.person_id = personinfo.person_id AND personinfo.data_key = 'mp_website'
        WHERE member.person_id = person_names.person_id AND person_names.type = 'name'
        AND md.person_id = person_names.person_id AND md.max_date = person_names.end_date
        GROUP by person_id
        ORDER BY house, family_name, lordofname, given_name");

    foreach ($q as $row) {
        $out .= '<li>';
        $name = member_full_name($row['house'], $row['title'], $row['given_name'], $row['family_name'], $row['lordofname']);
        $mp_website = $row['data_value'];
        $out .= ' <small>[<a href="websites.php?editperson=' . $row['person_id'] . '"';
        if ($mp_website) {
            $out .= ' title="Change URL ' . $mp_website . '">Edit URL</a>]</small>';
        } else {
            $out .= '>Add URL</a>]</small>';
        }
        $out .= ' ' . $name;
        if ($row['constituency']) {
            $out .= ' (' . $row['constituency'] . ')';
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

    $q  = $db->query("DELETE FROM personinfo WHERE data_key = 'mp_website' AND personinfo.person_id = :person_id", [
        ':person_id' => $personid,
    ]);

    if ($q->success()) {
        $q = $db->query("INSERT INTO personinfo (data_key, person_id, data_value) VALUES ('mp_website', :person_id, :url)", [
            ':person_id' => $personid,
            ':url' => get_http_var('url'),
        ]);
    }

    if ($q->success()) {
        exec($scriptpath . "/db2xml.pl --update_person --personid=" . escapeshellarg($personid) . " --debug", $exec_output);
        $out = '<p id="warning">';
        foreach ($exec_output as $message) {
            $out .= $message . "<br>";
        }
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
    $subnav = [
        'List Websites' => '/admin/websites.php',
    ];

    $rettext .= '<div id="subnav_websites">';
    foreach ($subnav as $label => $path) {
        $rettext .=  '<a href="' . $path . '">' . $label . '</a>';
    }
    $rettext .=  '</div>';

    return $rettext;
}

$menu = $PAGE->admin_menu();

$PAGE->stripe_end([
    [
        'type'		=> 'html',
        'content'	=> $menu,
    ],
]);

$PAGE->page_end();

<?php

include_once '../../includes/easyparliament/init.php';
#include_once INCLUDESPATH . 'easyparliament/commentreportlist.php';
#include_once INCLUDESPATH . 'easyparliament/searchengine.php';
include_once INCLUDESPATH . 'easyparliament/member.php';
#include_once INCLUDESPATH . 'easyparliament/people.php';

$this_page = 'admin_policies';

$db = new ParlDB;

$scriptpath = '../../../scripts';

$PAGE->page_start();
$PAGE->stripe_start();

$out = '';
if (get_http_var('editpolicy') && get_http_var('action') === 'Save') {
    $out = update_policy();
}

if (get_http_var('editpolicy')) {
    $out .= edit_policy_form();
} else {
    $out .= list_policies();
}

$subnav = subnav();

print '<div id="adminbody">';
print $subnav;
print $out;
print '</div>';

function edit_policy_form() {
    global $db;
    $policyid = get_http_var('editpolicy');
    $query = "SELECT policy_id, title, description, image, image_attrib, image_license, image_license_url, image_source FROM policies WHERE policy_id = :policy_id";
    $q = $db->query($query, array(
        ':policy_id' => $policyid
    ));

    $out = '';

    for ($row = 0; $row < $q->rows(); $row++) {

        $out = "<h3>Edit policy " . $q->field($row, 'title') . "</h3>\n";

        $out .= '<form action="policies.php?editpolicy=' . $q->field($row, 'policy_id') . '" method="post">';
        $out .= '<input name="action" type="hidden" value="Save">';
        $out .= '<label for="title">Title</label>';
        $out .= '<span class="formw"><input id="title" name="title" type="text"  size="60" value="' . htmlentities($q->field($row, 'title')) . '"></span>' . "<br>\n";
        $out .= '<label for="description">Description</label>';
        $out .= '<span class="formw"><input id="description" name="description" type="text"  size="60" value="' . htmlentities($q->field($row, 'description')) . '"></span>' . "<br>\n";
        $out .= '<label for="image">Image URL:</label>';
        $out .= '<span class="formw"><input id="image" name="image" type="text"  size="60" value="' . htmlentities($q->field($row, 'image')) . '"></span>' . "<br>\n";
        $out .= '<label for="image_attrib">Image attribution</label>';
        $out .= '<span class="formw"><input id="image_attrib" name="image_attrib" type="text"  size="60" value="' . htmlentities($q->field($row, 'image_attrib')) . '"></span>' . "<br>\n";
        $out .= '<label for="image_license">Image license</label>';
        $out .= '<span class="formw"><input id="image_license" name="image_license" type="text"  size="60" value="' . htmlentities($q->field($row, 'image_license')) . '"></span>' . "<br>\n";
        $out .= '<label for="image_license_url">Image license URL:</label>';
        $out .= '<span class="formw"><input id="image_license_url" name="image_license_url" type="text"  size="60" value="' . htmlentities($q->field($row, 'image_license_url')) . '"></span>' . "<br>\n";
        $out .= '<label for="image_source">Image source:</label>';
        $out .= '<span class="formw"><input id="image_source" name="image_source" type="text"  size="60" value="' . htmlentities($q->field($row, 'image_source')) . '"></span>' . "<br>\n";
        $out .= '<span class="formw"><input name="btnaction" type="submit" value="Save"></span>';
        $out .= '</form>';
    }

    return $out;
}

function list_policies() {
    global $db;
    $out = '<ul>';
    # this returns everyone so possibly over the top maybe limit to member.house = '1'
    $q = $db->query("SELECT policy_id, title, description, image_attrib, image_license, image_license_url, image_source FROM policies");

    for ($row = 0; $row < $q->rows(); $row++) {
        $out .= '<li>';
        $out .= sprintf('%s - %s', htmlentities($q->field($row, 'title')), htmlentities($q->field($row, 'description')));
        $out .= ' <small>[<a href="policies.php?editpolicy=' . $q->field($row, 'policy_id') . '">Edit Policy</a>]</small>';
        $out .= "</li>\n";
    }
    $out .= '</ul>';

    return $out;
}

function update_policy() {
    global $db;

    $out = '';
    $policyid = get_http_var('editpolicy');

    $q = $db->query("UPDATE policies SET title = :title, description = :description, image = :image,
        image_attrib = :image_attrib, image_license = :image_license, image_license_url = :image_license_url,
        image_source = :image_source WHERE policy_id = :policy_id",
        array(
            ':policy_id' => $policyid,
            ':title' => get_http_var('title'),
            ':description' => get_http_var('description'),
            ':image' => get_http_var('image'),
            ':image_attrib' => get_http_var('image_attrib'),
            ':image_license' => get_http_var('image_license'),
            ':image_license_url' => get_http_var('image_license_url'),
            ':image_source' => get_http_var('image_source')
        )
    );

    if ( $q->success() ) {
        $out = "<h4>update successful</h4>";
    }

    return $out;
}

function subnav() {
    $rettext = '';
    $subnav = array(
        'List Policies' => '/admin/policies.php',
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

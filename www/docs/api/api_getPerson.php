<?php

include_once INCLUDESPATH . 'easyparliament/member.php';

function api_getPerson_front() {
?>
<p><big>Fetch a particular person.</big></p>

<h4>Arguments</h4>
<dl>

<dt>id</dt>
<dd>If you know the person ID for the member you want (returned from getMPs or elsewhere), this will return data for that person.
This will return all database entries for this person, so will include previous elections, party changes, etc.</dd>
</dl>

<?php
}

function _api_getPerson_row($row, $has_party=FALSE) {
    global $parties;
    $row['full_name'] = member_full_name($row['house'], $row['title'], $row['first_name'],
        $row['last_name'], $row['constituency']);
    if ($row['house'] == 1) {
        $URL = new URL('mp');
        $row['url'] = $URL->generate('none') . make_member_url($row['full_name'], $row['constituency'], $row['house'], $row['person_id']);
    }
    if ($has_party && isset($parties[$row['party']]))
        $row['party'] = $parties[$row['party']];
    list($image,$sz) = find_rep_image($row['person_id']);
    if ($image) {
        list($width, $height) = getimagesize(str_replace(IMAGEPATH, BASEDIR . '/images/', $image));
        $row['image'] = $image;
        $row['image_height'] = $height;
        $row['image_width'] = $width;
    }

    if ($row['house'] == 1 && ($row['left_house'] == '9999-12-31' || $row['left_house'] == '2010-04-12')) { # XXX
        # Ministerialships and Select Committees
        $db = new ParlDB;
        $q = $db->query('SELECT * FROM moffice WHERE to_date="9999-12-31" and person=' . $row['person_id'] . ' ORDER BY from_date DESC');
        for ($i=0; $i<$q->rows(); $i++) {
            $row['office'][] = $q->row($i);
        }
    }

    foreach ($row as $k => $r) {
        if (is_string($r)) $row[$k] = html_entity_decode($r);
    }

    return $row;
}

function api_getPerson_id($id) {
    $db = new ParlDB;
    $q = $db->query("select * from member
        where person_id = '" . mysql_real_escape_string($id) . "'
        order by left_house desc");
    if ($q->rows()) {
        _api_getPerson_output($q);
    } else {
        api_error('Unknown person ID');
    }
}

function _api_getPerson_output($q) {
    $output = array();
    $last_mod = 0;
    for ($i=0; $i<$q->rows(); $i++) {
        $house = $q->field($i, 'house');
        $out = _api_getPerson_row($q->row($i), $house == 0 ? false : true);
        $output[] = $out;
        $time = strtotime($q->field($i, 'lastupdate'));
        if ($time > $last_mod)
            $last_mod = $time;
    }
    api_output($output, $last_mod);
}

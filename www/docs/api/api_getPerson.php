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
    $row['full_name'] = member_full_name($row['house'], $row['title'], $row['given_name'],
        $row['family_name'], $row['lordofname']);
    if ($row['house'] != 2) {
        unset($row['lordofname']);
    }
    if ($row['house'] == 1) {
        $URL = new URL('mp');
        $row['url'] = $URL->generate('none') . make_member_url($row['full_name'], $row['constituency'], $row['house'], $row['person_id']);
    }
    if ($has_party && isset($parties[$row['party']]))
        $row['party'] = $parties[$row['party']];
    list($image,$sz) = MySociety\TheyWorkForYou\Utility\Member::findMemberImage($row['person_id']);
    if ($image) {
        list($width, $height) = getimagesize(str_replace(IMAGEPATH, BASEDIR . '/images/', $image));
        $row['image'] = $image;
        $row['image_height'] = $height;
        $row['image_width'] = $width;
    }

    if ($row['house'] == 1 && ($row['left_house'] == '9999-12-31' || (DISSOLUTION_DATE && $row['left_house'] == DISSOLUTION_DATE))) {
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

function api_getPerson_id($id, $house='') {
    $db = new ParlDB;
    $params = array(
        ':person_id' => $id
    );
    if ($house) {
        $params[':house'] = $house;
        $house = 'house = :house AND';
    }
    $q = $db->query("SELECT member.*, p.title, p.given_name, p.family_name, p.lordofname
        FROM member, person_names p
        where $house member.person_id = :person_id
            AND member.person_id = p.person_id AND p.type = 'name'
            AND p.start_date <= left_house and left_house <= p.end_date
        order by left_house desc", $params);
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
        $out = _api_getPerson_row($q->row($i), $house == HOUSE_TYPE_ROYAL ? false : true);
        $output[] = $out;
        $time = strtotime($q->field($i, 'lastupdate'));
        if ($time > $last_mod)
            $last_mod = $time;
    }
    api_output($output, $last_mod);
}

function api_getPerson_constituency($constituency, $house) {
    if ($house == HOUSE_TYPE_COMMONS) {
        $output = _api_getMP_constituency($constituency);
    } else {
        $output = _api_getPerson_constituency(array($constituency), $house);
    }
    if (!$output) {
        api_error('Unknown constituency, or no results for that constituency');
    }
}

function api_getPerson_postcode($pc, $house) {
    $pc = preg_replace('#[^a-z0-9 ]#i', '', $pc);
    $types = array();
    if ($house == HOUSE_TYPE_NI) {
        $types = array('NIE');
    } elseif ($house == HOUSE_TYPE_SCOTLAND) {
        $types = array('SPC', 'SPE');
    }
    if (validate_postcode($pc)) {
        $constituencies = postcode_to_constituencies($pc, true);
        if ($constituencies == 'CONNECTION_TIMED_OUT') {
            api_error('Connection timed out');
        } elseif ($types && isset($constituencies[$types[0]])) {
            $constituencies = array_map(function($c) use ($constituencies) { return $constituencies[$c]; }, $types); 
            _api_getPerson_constituency($constituencies, $house);
        } elseif ($types && isset($constituencies['WMC'])) {
            api_error('Postcode not in correct region');
        } elseif (isset($constituencies['WMC'])) {
            _api_getMP_constituency($constituencies['WMC']);
        } else {
            api_error('Unknown postcode');
        }
    } else {
        api_error('Invalid postcode');
    }
}

# Very similary to MEMBER's constituency_to_person_id
# Should all be abstracted properly :-/
function _api_getPerson_constituency($constituencies, $house) {
    $db = new ParlDB;

    $cons = array();
    foreach ($constituencies as $constituency) {
        if ($constituency == '') continue;
        if ($constituency == 'Orkney ')
            $constituency = 'Orkney & Shetland';
        $cons[] = $constituency;
    }

    $cons_params = array();
    $params = array(':house' => $house);
    foreach ($cons as $key => $constituency) {
        $cons_params[] = ':constituency' . $key;
        $params[':constituency' . $key] = $constituency;
    }

    $q = $db->query("SELECT member.*, p.title, p.given_name, p.family_name, p.lordofname
        FROM member, person_names p
        WHERE constituency in (" . join(",", $cons_params) . ")
            AND member.person_id = p.person_id AND p.type = 'name'
            AND p.start_date <= left_house AND left_house <= p.end_date
        AND left_reason = 'still_in_office' AND house=:house", $params);
    if ($q->rows > 0) {
        _api_getPerson_output($q);
        return true;
    }

    return false;
}

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

function _api_getPerson_row($row, $has_party = false) {
    global $parties;
    $row['full_name'] = member_full_name(
        $row['house'],
        $row['title'],
        $row['given_name'],
        $row['family_name'],
        $row['lordofname']
    );
    if ($row['house'] != HOUSE_TYPE_LORDS) {
        unset($row['lordofname']);
    }
    if ($row['house'] == HOUSE_TYPE_COMMONS) {
        $URL = new \MySociety\TheyWorkForYou\Url('mp');
        $row['url'] = $URL->generate('none') . make_member_url($row['full_name'], $row['constituency'], $row['house'], $row['person_id']);
    }
    if ($has_party && isset($parties[$row['party']])) {
        $row['party'] = $parties[$row['party']];
    }
    [$image, $sz] = MySociety\TheyWorkForYou\Utility\Member::findMemberImage($row['person_id']);
    if ($image) {
        [$width, $height] = getimagesize(str_replace(IMAGEPATH, BASEDIR . IMAGEPATH, $image));
        $row['image'] = $image;
        $row['image_height'] = $height;
        $row['image_width'] = $width;
    }

    $dissolution = MySociety\TheyWorkForYou\Dissolution::dates();
    if ($row['house'] == HOUSE_TYPE_COMMONS && ($row['left_house'] == '9999-12-31' || (isset($dissolution[1]) && $row['left_house'] == $dissolution[1]))) {
        # Ministerialships and Select Committees
        $db = new ParlDB();
        $q = $db->query('SELECT * FROM moffice WHERE to_date="9999-12-31" and person=' . $row['person_id'] . ' ORDER BY from_date DESC');
        foreach ($q as $office) {
            $row['office'][] = $office;
        }
    }

    foreach ($row as $k => $r) {
        if (is_string($r)) {
            $row[$k] = html_entity_decode($r);
        }
    }

    return $row;
}

function api_getPerson_id($id, $house = '') {
    $db = new ParlDB();
    $params = [
        ':person_id' => $id,
    ];
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

function _api_getPerson_output($q, $flatten = false) {
    $output = [];
    $last_mod = 0;
    $house = null;
    foreach ($q as $row) {
        $house = $row['house'];
        $out = _api_getPerson_row($row, $house == HOUSE_TYPE_ROYAL ? false : true);
        $output[] = $out;
        $time = strtotime($row['lastupdate']);
        if ($time > $last_mod) {
            $last_mod = $time;
        }
    }
    # Only one MP, not an array
    if ($flatten && count($output) == 1 && $house == HOUSE_TYPE_COMMONS) {
        $output = $output[0];
    }
    api_output($output, $last_mod);
}

function api_getPerson_constituency($constituency, $house) {
    _api_getPerson_constituency([$constituency], $house);
}

function api_getPerson_postcode($pc, $house) {
    $pc = preg_replace('#[^a-z0-9]#i', '', $pc);
    $types = [];
    if ($house == HOUSE_TYPE_NI) {
        $types = ['NIE'];
    } elseif ($house == HOUSE_TYPE_SCOTLAND) {
        $types = ['SPC', 'SPE'];
    } elseif ($house == HOUSE_TYPE_WALES) {
        $types = ['WAC', 'WAE'];
    }
    if (validate_postcode($pc)) {
        $constituencies = MySociety\TheyWorkForYou\Utility\Postcode::postcodeToConstituencies($pc);
        if ($constituencies == 'CONNECTION_TIMED_OUT') {
            api_error('Connection timed out');
        } elseif ($types && isset($constituencies[$types[0]])) {
            $constituencies = array_map(function ($c) use ($constituencies) { return $constituencies[$c]; }, $types);
            _api_getPerson_constituency($constituencies, $house);
        } elseif ($types && isset($constituencies['WMC'])) {
            api_error('Postcode not in correct region');
        } elseif (isset($constituencies['WMC'])) {
            _api_getPerson_constituency([$constituencies['WMC']], $house);
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
    $db = new ParlDB();
    $dissolution = MySociety\TheyWorkForYou\Dissolution::db();

    $cons = [];
    foreach ($constituencies as $constituency) {
        if ($constituency == '') {
            continue;
        }
        if ($constituency == 'Orkney ') {
            $constituency = 'Orkney & Shetland';
        }

        if ($house == HOUSE_TYPE_COMMONS) {
            $normalised = MySociety\TheyWorkForYou\Utility\Constituencies::normaliseConstituencyName($constituency);
            if ($normalised) {
                $constituency = $normalised;
            }
        }

        $cons[] = $constituency;
    }

    $cons_params = [];
    $params = [':house' => $house];
    foreach ($cons as $key => $constituency) {
        $cons_params[] = ':constituency' . $key;
        $params[':constituency' . $key] = $constituency;
    }

    $query_base = "SELECT member.*, p.title, p.given_name, p.family_name, p.lordofname
        FROM member, person_names p
        WHERE constituency in (" . join(",", $cons_params) . ")
            AND member.person_id = p.person_id AND p.type = 'name'
            AND p.start_date <= left_house AND left_house <= p.end_date
        AND house = :house";

    $q = $db->query("$query_base AND left_reason = 'still_in_office'", $params);
    if ($q->rows() == 0 && get_http_var('always_return') && $dissolution) {
        $q = $db->query("$query_base AND $dissolution[query]", $params + $dissolution['params']);
    }

    if ($q->rows() > 0) {
        _api_getPerson_output($q, true);
    } else {
        api_error('Unknown constituency, or no results for that constituency');
    }
}

<?php

/* Shared API functions for get<Members> */

function _api_getMembers_output($sql, $params) {
    global $parties;
    $db = new ParlDB;
    $q = $db->query($sql, $params);
    $output = array();
    $last_mod = 0;
    for ($i=0; $i<$q->rows(); $i++) {
        $pid = $q->field($i, 'person_id');
        $row = array(
            'member_id' => $q->field($i, 'member_id'),
            'person_id' => $pid,
            'name' => html_entity_decode(member_full_name($q->field($i, 'house'), $q->field($i, 'title'),
                $q->field($i, 'given_name'), $q->field($i, 'family_name'),
                $q->field($i, 'lordofname') )),
            'party' => isset($parties[$q->field($i, 'party')]) ? $parties[$q->field($i, 'party')] : $q->field($i, 'party'),
        );
        if ($q->field($i, 'house') != 2) {
            $row['constituency'] = $q->field($i, 'constituency');
        }
        $output[$pid] = $row;
        $time = strtotime($q->field($i, 'lastupdate'));
        if ($time > $last_mod) {
            $last_mod = $time;
        }
    }

    $pids = array_keys($output);
    if (count($pids)) {
        $q = $db->query('SELECT person, dept, position, from_date, to_date FROM moffice
            WHERE to_date="9999-12-31" AND person IN (' . join(',', $pids) . ')');
        for ($i=0; $i<$q->rows(); $i++) {
            $row = $q->row($i);
            $pid = $row['person'];
            unset($row['person']);
            $output[$pid]['office'][] = $row;
        }
    }
    $output = array_values($output);
    api_output($output, $last_mod);
}

function api_getMembers_party($house, $s) {
    global $parties;
    $canon_to_short = array_flip($parties);
    if (isset($canon_to_short[ucwords($s)])) {
        $s = $canon_to_short[ucwords($s)];
    }
    _api_getMembers_output('select * from member, person_names p
        where house = :house
        AND member.person_id = p.person_id AND p.type = "name"
            AND p.start_date <= date(now()) and date(now()) <= p.end_date
        and party like :party and entered_house <= date(now()) and date(now()) <= left_house', array(
            ':house' => $house,
            ':party' => '%' . $s . '%'
            ));
}

function api_getMembers_search($house, $s) {

    $query = "select * from member, person_names p
        where house = :house
        AND member.person_id = p.person_id AND p.type = 'name'
            AND p.start_date <= date(now()) and date(now()) <= p.end_date
        and (given_name like :name
        or family_name like :name
        or concat(given_name,' ',family_name) like :name";

        if ($house == 2) {
            $query .= " or constituency like :name";
        }

        $query .= ") and entered_house <= date(now()) and date(now()) <= left_house";

    _api_getMembers_output($query, array(
        ':house' => $house,
        ':name' => '%' . $s . '%'
        ));
}

function api_getMembers_date($house, $date) {
    if ($date = parse_date($date)) {
        api_getMembers($house, '"' . $date['iso'] . '"');
    } else {
        api_error('Invalid date format');
    }
}

function api_getMembers($house, $date = 'now()') {
    # $date may be a function such as now(), the default, and so cannot be used
    # as a parameter.
    _api_getMembers_output("SELECT * FROM member, person_names p
        WHERE house = :house
            AND member.person_id = p.person_id AND p.type = 'name'
                AND p.start_date <= date($date) and date($date) <= p.end_date
            AND entered_house <= date($date)
            AND date($date) <= left_house",
        array(':house' => $house)
    );
}

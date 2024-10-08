<?php

function api_getCommittee_front() {
    ?>
<p><big>Fetch the members of a Select Committee.</big></p>

<p class="informational">We have no information since the 2010 general election, and information before may be inaccurate.</p>

<h4>Arguments</h4>
<dl>
<dt>name (optional)</dt>
<dd>Fetch the members of the committee that match this name - if more than one committee matches, return their names.
If left blank, return all committee names for the date provided (or current date) in the database.</dd>
<dt>date (optional)</dt>
<dd>Return the members of the committee as they were on this date.</dd>
</dl>

<h4>Example responses</h4>

<pre>{ "committees" : [
    { "name" : "Scottish Affairs Committee" },
    { "name" : "Northern Ireland Affairs Committee" },
    { "name" : "Home Affairs Committee" },
    { "name" : "Constitutional Affairs Committee" },
    { "name" : "Environment, Food and Rural Affairs Committee" },
    { "name" : "Foreign Affairs Committee" },
    { "name" : "Welsh Affairs Committee" }
] }</pre>

<pre>{
    "committee" : "Health Committee",
    "members" : [
    { "person_id" : "10009", "name" : "David Amess" },
    { "person_id" : "10018", "name" : "Charlotte Atkins" },
    { "person_id" : "10176", "name" : "Jim Dowd" },
    { "person_id" : "11603", "name" : "Anne Milton" },
    { "person_id" : "10455", "name" : "Doug Naysmith" },
    { "person_id" : "11626", "name" : "Michael Penning" },
    { "person_id" : "10571", "name" : "Howard Stoate" },
    { "person_id" : "11275", "name" : "Richard Taylor" },
    { "person_id" : "10027", "name" : "Kevin Barron", "position" : "Chairman" },
    { "person_id" : "10089", "name" : "Ronnie Campbell" },
    { "person_id" : "10677", "name" : "Sandra Gidley" }
  ]
}</pre>

<?php
}

function api_getCommittee() {
    return api_getCommittee_date(get_http_var('date'));
}

function api_getCommittee_name($name) {
    $db = new ParlDB();

    $name = preg_replace('#\s+Committee#', '', $name);

    $date = parse_date(get_http_var('date'));
    if ($date) {
        $date = '"' . $date['iso'] . '"';
    } else {
        $date = 'date(now())';
    }
    $q = $db->query("select distinct(dept) from moffice
        where dept like :department
        and from_date <= $date and $date <= to_date", [
        ':department' => '%' . $name . '%Committee',
    ]);
    if ($q->rows() > 1) {
        # More than one committee matches
        $output = [];
        foreach ($q as $row) {
            $output['committees'][] = [
                'name' => $row['dept'],
            ];
        }
        api_output($output);
    } elseif ($q->rows()) {
        # One committee
        $q = $db->query("select * from moffice, member, person_names pn
            where moffice.person = member.person_id
                AND member.person_id = pn.person_id AND pn.type='name' AND pn.start_date <= $date AND $date <= pn.end_date
            and dept like :department
            and from_date <= $date and $date <= to_date
            and entered_house <= $date and $date <= left_house", [
            ':department' => '%' . $name . '%Committee',
        ]);
        if ($q->rows()) {
            $output = [];
            $output['committee'] = $q->first()['dept'];
            foreach ($q as $row) {
                $member = [
                    'person_id' => $row['person'],
                    'name' => $row['given_name'] . ' ' . $row['family_name'],
                ];
                if ($row['position'] == 'Chairman') {
                    $member['position'] = $row['position'];
                }
                $output['members'][] = $member;
            }
            api_output($output);
        } else {
            api_error('That committee has no members...?');
        }
    } else {
        api_error('That name was not recognised');
    }
}

function api_getCommittee_date($date) {
    $db = new ParlDB();

    $date = parse_date($date);
    if ($date) {
        $date = '"' . $date['iso'] . '"';
    } else {
        $date = 'date(now())';
    }
    $q = $db->query("select distinct(dept) from moffice
        where source = 'chgpages/selctee'
        and from_date <= $date and $date <= to_date");
    if ($q->rows()) {
        $output = [];
        foreach ($q as $row) {
            $output['committees'][] = [
                'name' => $row['dept'],
            ];
        }
        api_output($output);
    } else {
        api_error('No committees found');
    }
}

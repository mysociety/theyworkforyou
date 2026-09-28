<?php

use MySociety\TheyWorkForYou\PostType;

function api_getCommittee_front() {
    ?>
<p><big>Fetch the members of a parliamentary committee.</big></p>

<p class="informational">Covers committees in the UK Parliament, Scottish Parliament, Senedd and Northern Ireland Assembly. Older information may be inaccurate.</p>

<h4>Arguments</h4>
<dl>
<dt>name (optional)</dt>
<dd>Fetch the members of the committee that match this name - if more than one committee matches, return their names.
If left blank, return all committee names for the date provided (or current date) in the database.</dd>
<dt>parliament (optional)</dt>
<dd>One of <code>uk</code>, <code>scotland</code>, <code>wales</code>, or
<code>northern-ireland</code>. Defaults to <code>uk</code> for existing callers.
An unrecognised value returns an error. Committee names are returned in English.</dd>
<dt>date (optional)</dt>
<dd>Return the members of the committee as they were on this date.</dd>
</dl>

<h4>Example requests</h4>
<ul>
<li><code>getCommittee</code> or <code>getCommittee?parliament=uk</code></li>
<li><code>getCommittee?parliament=scotland&amp;name=Health</code></li>
<li><code>getCommittee?parliament=wales&amp;name=Finance</code></li>
<li><code>getCommittee?parliament=northern-ireland&amp;name=Health</code></li>
</ul>

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

/**
 * Validate the shared selector before either API lookup path queries the DB.
 */
function api_getCommittee_parliament(): ?string {
    $parliament = get_http_var('parliament', default: 'uk');
    if ($parliament === '') {
        $parliament = 'uk';
    }
    if (!in_array($parliament, ['uk', 'scotland', 'wales', 'northern-ireland'], true)) {
        api_error('Unknown parliament. Expected uk, scotland, wales, or northern-ireland.');
        return null;
    }
    return $parliament;
}

function api_getCommittee(): void {
    api_getCommittee_date(get_http_var('date'));
}

function api_getCommittee_name(string $name): void {
    $parliament = api_getCommittee_parliament();
    if ($parliament === null) {
        return;
    }
    $db = new ParlDB();
    $parsed_date = parse_date(get_http_var('date'));
    $date = $parsed_date ? $parsed_date['iso'] : date('Y-m-d');

    // Existing UK callers can omit the trailing "Committee". Other parliaments
    // also use names such as "Committee for Health", so do not require a suffix.
    if ($parliament === 'uk') {
        $name = preg_replace('#\s+Committee#', '', $name);
        $pattern = '%' . $name . '%Committee';
    } else {
        $pattern = '%' . $name . '%';
    }
    $q = $db->query(
        "SELECT DISTINCT mo.org_id, mo.dept
         FROM moffice mo
         WHERE mo.post_type = :post_type AND mo.parliament = :parliament
           AND mo.dept LIKE :name
           AND mo.from_date <= :date_start AND :date_end <= mo.to_date",
        [':post_type' => PostType::COMMITTEE->value, ':parliament' => $parliament, ':name' => $pattern,
            ':date_start' => $date, ':date_end' => $date]
    );
    if (!$q->rows()) {
        api_error('That name was not recognised');
        return;
    }
    if ($q->rows() > 1) {
        $output = ['committees' => []];
        foreach ($q as $row) {
            $output['committees'][] = ['name' => $row['dept']];
        }
        api_output($output);
        return;
    }

    $committee = $q->first();
    $houses = match ($parliament) {
        'uk' => [HOUSE_TYPE_COMMONS, HOUSE_TYPE_LORDS],
        'scotland' => [HOUSE_TYPE_SCOTLAND],
        'wales' => [HOUSE_TYPE_WALES],
        'northern-ireland' => [HOUSE_TYPE_NI],
    };

    $identity = $committee['org_id'] !== null
        ? 'mo.org_id = :identity'
        : 'mo.org_id IS NULL AND mo.dept = :identity';
    $house_ids = implode(',', $houses);
    $q = $db->query(
        "SELECT DISTINCT mo.person, pn.given_name, pn.family_name, mo.position
         FROM moffice mo
         JOIN person_names pn ON pn.person_id = mo.person AND pn.type = 'name'
           AND pn.start_date <= :name_start AND :name_end <= pn.end_date
         WHERE mo.post_type = :post_type AND mo.parliament = :parliament
           AND $identity
           AND mo.from_date <= :date_start AND :date_end <= mo.to_date
           AND EXISTS (
               SELECT 1 FROM member m WHERE m.person_id = mo.person
                 AND m.house IN ($house_ids)
                 AND m.entered_house <= :member_start AND :member_end <= m.left_house
           )",
        [':post_type' => PostType::COMMITTEE->value, ':parliament' => $parliament,
            ':identity' => $committee['org_id'] ?? $committee['dept'],
            ':name_start' => $date, ':name_end' => $date,
            ':date_start' => $date, ':date_end' => $date,
            ':member_start' => $date, ':member_end' => $date]
    );
    if (!$q->rows()) {
        api_error('That committee has no members...?');
        return;
    }
    $output = ['committee' => $committee['dept'], 'members' => []];
    foreach ($q as $row) {
        $member = [
            'person_id' => $row['person'],
            'name' => $row['given_name'] . ' ' . $row['family_name'],
        ];
        // Preserve the existing UK response contract, including chair labels.
        if ($row['position'] == 'Chairman') {
            $member['position'] = $row['position'];
        }
        $output['members'][] = $member;
    }
    api_output($output);
}

function api_getCommittee_date(string $date): void {
    $parliament = api_getCommittee_parliament();
    if ($parliament === null) {
        return;
    }
    $db = new ParlDB();
    $parsed_date = parse_date($date);
    $date = $parsed_date ? $parsed_date['iso'] : date('Y-m-d');
    $q = $db->query(
        "SELECT DISTINCT dept FROM moffice
         WHERE post_type = :post_type AND parliament = :parliament
           AND from_date <= :date_start AND :date_end <= to_date",
        [':post_type' => PostType::COMMITTEE->value, ':parliament' => $parliament, ':date_start' => $date, ':date_end' => $date]
    );
    if (!$q->rows()) {
        api_error('No committees found');
        return;
    }
    $output = ['committees' => []];
    foreach ($q as $row) {
        $output['committees'][] = ['name' => $row['dept']];
    }
    api_output($output);
}

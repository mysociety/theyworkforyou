<?php

# For looking up a postcode and redirecting or displaying appropriately

use MySociety\TheyWorkForYou\Office;

include_once '../../includes/easyparliament/init.php';
include_once INCLUDESPATH . 'easyparliament/member.php';

$data = [];

$valid_scotland_single_member_mapit_codes = ['SPC', 'SPCF'];
$valid_scotland_multi_member_mapit_codes = ['SPE', 'SPEF'];
$valid_wales_mapit_codes = ['WAC', 'WACF'];
$valid_ni_mapit_codes = ['NIE'];
$valid_wmc_mapit_codes = ['WMC'];

$valid_scotland_mapit_codes = array_merge($valid_scotland_single_member_mapit_codes, $valid_scotland_multi_member_mapit_codes);
$valid_mapit_area_types = array_merge($valid_wmc_mapit_codes, $valid_scotland_mapit_codes, $valid_wales_mapit_codes, $valid_ni_mapit_codes);

// Local authority area types from MapIt (for councillor section)
// Two-tier: CTY (county) + DIS (district). Single-tier: UTA, MTD, LBO, LGD, COI.
$valid_local_authority_types = ['CTY', 'DIS', 'UTA', 'MTD', 'LBO', 'LGD', 'COI'];

$pc = get_http_var('pc');
if (!$pc) {
    postcode_error('Please supply a postcode!');
}
$data['pc'] = $pc;
$data['expand'] = get_http_var('expand') === '1';

$pc = preg_replace('#[^a-z0-9]#i', '', $pc);
if (!validate_postcode($pc)) {
    twfy_debug('MP', "Can't display an MP because the submitted postcode wasn't of a valid form.");
    postcode_error("Sorry, " . _htmlentities($pc) . " isn't a valid postcode");
}

# 2026 DEVOLVED ELECTIONS

/*
$data['address'] = $address = get_http_var('address');
if ($address) {
    $dc_data = democracy_club_address($address);
    $constituencies = mapit_address($address, $pc);
} else {
    $dc_data = democracy_club_postcode($pc);
    if (!isset($dc_data->error) && isset($dc_data->address_picker) && $dc_data->address_picker) {
        show_address_list($pc, $dc_data->addresses);
        exit;
    }
    $constituencies = mapit_postcode($pc);
}
*/
$constituencies = mapit_postcode($pc);
if (!$constituencies) {
    postcode_error("Sorry, " . _htmlentities($pc) . " isn't a known postcode");
}

# Get dissolution dates to check if parliaments are dissolved
$dissolution_dates = MySociety\TheyWorkForYou\Dissolution::dates();

# Check for 2025 Scottish Parliament election ballots (only show if Scottish Parliament is dissolved)
$data['sp_ballots'] = [];
$sp_dissolved = isset($dissolution_dates[HOUSE_TYPE_SCOTLAND]);
# If dissovled and we have future constituency information
if ($sp_dissolved && (isset($constituencies['SPCF']) || isset($constituencies['SPEF'])) && isset($dc_data->dates)) {
    foreach ($dc_data->dates as $date) {
        foreach ($date->ballots as $b) {
            # Scottish Parliament constituency election (e.g. sp.c.2025-05-07)
            if (preg_match('/^sp\.c\./', $b->election_id)) {
                $data['sp_ballots']['constituency'] = $b;
            }
            # Scottish Parliament regional election (e.g. sp.r.2025-05-07)
            if (preg_match('/^sp\.r\./', $b->election_id)) {
                $data['sp_ballots']['regional'] = $b;
            }
        }
    }
}

# Check for 2025 Welsh Senedd election ballot (only show if Senedd is dissolved)
$data['senedd_ballot'] = null;
$senedd_dissolved = isset($dissolution_dates[HOUSE_TYPE_WALES]);
# if dissolved and we have future constituency information
if ($senedd_dissolved && (isset($constituencies['WACF'])) && isset($dc_data->dates)) {
    foreach ($dc_data->dates as $date) {
        foreach ($date->ballots as $b) {
            # Senedd election (e.g. senedd.2026-05-07)
            if (preg_match('/^senedd\./', $b->election_id)) {
                $data['senedd_ballot'] = $b;
            }
        }
    }
}


if (has_any_area_type($constituencies, $valid_scotland_mapit_codes)) {
    $data['multi'] = "scotland";
    $data['devolved_anchor'] = 'scotland';
    $rep_data = pick_multiple($pc, $constituencies, HOUSE_TYPE_SCOTLAND);
} elseif (has_any_area_type($constituencies, $valid_wales_mapit_codes)) {
    $data['multi'] = "wales";
    $data['devolved_anchor'] = 'senedd';
    $rep_data = pick_multiple($pc, $constituencies, HOUSE_TYPE_WALES);
} elseif (has_any_area_type($constituencies, $valid_ni_mapit_codes)) {
    $data['multi'] = "northern-ireland";
    $data['devolved_anchor'] = 'ni';
    $rep_data = pick_multiple($pc, $constituencies, HOUSE_TYPE_NI);
} else {
    $MEMBER = fetch_mp($pc, $constituencies, 1);
    if ($MEMBER->valid) {
        member_redirect($MEMBER);
    }
    postcode_error(gettext('Your MP is currently unknown.'));
}

$data['mp_data'] = fetch_mp_data($pc, $constituencies);
$data['members'] = $rep_data['members'];
$data['current'] = $rep_data['current'];
$data['member_names'] = $rep_data['member_names'];
$CHANGEURL = new \MySociety\TheyWorkForYou\Url('userchangepc');
if ($THEUSER->isloggedin()) {
    $CHANGEURL = new \MySociety\TheyWorkForYou\Url('useredit');
}
$data['change_url'] = $CHANGEURL->generate();
$data['sections'] = build_postcode_sections(
    $pc,
    $data['mp_data'],
    $rep_data,
    $data['multi'],
    $data['devolved_anchor'],
    $constituencies
);

MySociety\TheyWorkForYou\Renderer::output('postcode/index', $data);

# ---

function postcode_error($error) {
    global $PAGE;
    $PAGE->page_start();
    $PAGE->stripe_start();
    $PAGE->error_message($error);
    $PAGE->postcode_form();
    $PAGE->stripe_end();
    $PAGE->page_end();
    exit;
}

function buildRepData(MySociety\TheyWorkForYou\Member $member, int $house, bool $former = false): array {
    [$image, ] = MySociety\TheyWorkForYou\Utility\Member::findMemberImage($member->person_id(), false, true);

    $member->load_extra_info();

    $committees = [];
    foreach ($member->offices('current', Office::COMMITTEE_TYPES) as $office) {
        $committees[] = $office->title;
    }

    $appgs = [];
    $extra_info = $member->extra_info();
    if (isset($extra_info['appg_membership'])) {
        $appg_data = MySociety\TheyWorkForYou\DataClass\APPGs\APPGMembershipAssignment::fromJson($extra_info['appg_membership']);
        foreach ($appg_data->is_officer_of as $membership) {
            $appgs[] = ['title' => $membership->appg->shortTitle(), 'role' => $membership->role];
        }
        foreach ($appg_data->is_ordinary_member_of as $membership) {
            $appgs[] = ['title' => $membership->appg->shortTitle(), 'role' => 'Member'];
        }
    }

    return [
        'name' => $member->full_name(),
        'party' => $member->party(),
        'constituency' => $member->constituency(),
        'mp_url' => $member->url(),
        'person_id' => $member->person_id(),
        'image' => $image,
        'former' => $former,
        'committees' => $committees,
        'appgs' => $appgs,
        'appgs_label' => MySociety\TheyWorkForYou\Utility\House::groupsName($house),
    ];
}

function fetch_mp(string $pc, array $constituencies, ?int $house = null): MySociety\TheyWorkForYou\Member {
    global $THEUSER;
    $args = ['constituency' => $constituencies['WMC']];
    if ($house) {
        $args['house'] = $house;
    }
    try {
        $MEMBER = new MySociety\TheyWorkForYou\Member($args);
    } catch (MySociety\TheyWorkForYou\MemberException $e) {
        postcode_error($e->getMessage());
    }
    if ($MEMBER->person_id()) {
        $THEUSER->set_postcode_cookie($pc);
    }
    return $MEMBER;
}

function fetch_mp_data(string $pc, array $constituencies): ?array {
    $MEMBER = fetch_mp($pc, $constituencies);
    if (!$MEMBER->valid) {
        return null;
    }

    $former = isset($MEMBER->left_house[HOUSE_TYPE_COMMONS])
        && $MEMBER->left_house[HOUSE_TYPE_COMMONS]['date'] !== '9999-12-31';

    $mp_data = buildRepData($MEMBER, HOUSE_TYPE_COMMONS, $former);

    $db = new ParlDB();
    $q = $db->query(
        "SELECT data_value FROM personinfo WHERE person_id = :person_id AND data_key = 'standing_down_2024'",
        [':person_id' => $MEMBER->person_id()]
    );
    $mp_data['standing_down_2024'] = $q['data_value'] ?? 0;

    return $mp_data;
}

function build_postcode_sections(
    string $pc,
    ?array $mp_data,
    array $rep_data,
    string $multi,
    string $devolved_anchor,
    array $constituencies
): array {
    return [
        build_mp_section($mp_data),
        build_devolved_section($rep_data, $multi, $devolved_anchor),
        build_council_section($pc, $constituencies),
    ];
}

function build_council_section(string $pc, array $constituencies): array {
    return [
        'id' => 'council',
        'title' => gettext('Your local councillors'),
        'writetothem_url' => 'https://www.writetothem.com/who?pc=' . urlencode($pc),
        'council_names' => local_authority_names($constituencies),
    ];
}

/**
 * Extract local authority names from MapIt areas.
 * Handles two-tier (county + district) and single-tier (unitary, metropolitan,
 * London borough, NI district) councils.
 */
function local_authority_names(array $areas): array {
    // Two-tier: both county and district
    if (isset($areas['CTY']) && isset($areas['DIS'])) {
        return [$areas['DIS'], $areas['CTY']];
    }

    // Single-tier authorities
    foreach (['UTA', 'MTD', 'LBO', 'LGD', 'COI'] as $type) {
        if (isset($areas[$type])) {
            return [$areas[$type]];
        }
    }

    return [];
}

function build_mp_section(?array $mp_data): array {
    return [
        'id' => 'uk',
        'title' => $mp_data && $mp_data['former']
            ? gettext('Your former MP')
            : gettext('Your MP'),
        'description' => gettext('Your MP represents you in the House of Commons. The House of Commons is responsible for making laws in the UK and for overall scrutiny of all aspects of government.'),
        'groups' => $mp_data ? [['members' => [$mp_data]]] : [],
        'empty_message' => $mp_data ? null : gettext('Your MP is currently unknown.'),
        'footer' => $mp_data && $mp_data['standing_down_2024']
            ? gettext('They are standing down at the general election.')
            : null,
    ];
}

function build_devolved_section(array $rep_data, string $multi, string $devolved_anchor): array {
    $title = $rep_data['current']
        ? sprintf(gettext('Your %s'), $rep_data['member_names']['plural'])
        : sprintf(gettext('Your former %s'), $rep_data['member_names']['plural']);

    return [
        'id' => $devolved_anchor,
        'title' => $title,
        'description' => devolved_description($multi),
        'groups' => devolved_groups($multi, $rep_data['members']),
    ];
}

function devolved_description(string $multi): string {
    if ($multi === 'scotland') {
        return gettext('Your MSPs represent you in the Scottish Parliament. The Scottish Parliament is responsible for a wide range of devolved matters in which it sets policy independently of the London Parliament. Devolved matters include education, health, agriculture, justice and prisons. It also has some tax-raising powers.');
    }

    if ($multi === 'northern-ireland') {
        return gettext('Your MLAs represent you in the Northern Ireland Assembly. The Northern Ireland Assembly has full authority over "transferred matters", which include agriculture, education, employment, the environment and health.');
    }

    return gettext('Your MSs represent you in the Senedd. The Senedd has a wide range of powers over areas including economic development, transport, finance, local government, health, housing and the Welsh Language.');
}

function devolved_groups(string $multi, array $members): array {
    if ($multi !== 'scotland') {
        return [[
            'title' => null,
            'members' => shuffle_grouped_by_party($members),
        ]];
    }

    $constituency = array_values(array_filter($members, function ($member) {
        return $member['type'] === 'constituency';
    }));
    $regional = array_values(array_filter($members, function ($member) {
        return $member['type'] === 'regional';
    }));

    return [
        [
            'title' => gettext('Constituency MSP'),
            'members' => $constituency,
        ],
        [
            'title' => gettext('Regional MSPs'),
            'members' => shuffle_grouped_by_party($regional),
        ],
    ];
}

/**
 * Randomise representative order while keeping members of the same party
 * together. Parties appear in a random order, but all members within each
 * party are grouped consecutively (sorted alphabetically by name).
 */
function shuffle_grouped_by_party(array $members): array {
    // Group members by party
    $by_party = [];
    foreach ($members as $member) {
        $by_party[$member['party']][] = $member;
    }

    // Sort members within each party by name
    foreach ($by_party as &$group) {
        usort($group, fn($a, $b) => strcmp($a['name'], $b['name']));
    }
    unset($group);

    // Shuffle the party order
    $parties = array_keys($by_party);
    shuffle($parties);

    // Flatten back into a single list
    $result = [];
    foreach ($parties as $party) {
        foreach ($by_party[$party] as $member) {
            $result[] = $member;
        }
    }
    return $result;
}

function has_any_area_type(array $areas, array $area_types): bool {
    foreach ($area_types as $area_type) {
        if (isset($areas[$area_type])) {
            return true;
        }
    }
    return false;
}

function get_area_names_by_type(array $areas, array $area_types): array {
    $values = [];
    foreach ($area_types as $area_type) {
        if (isset($areas[$area_type])) {
            $values[] = $areas[$area_type];
        }
    }
    return $values;
}

function pick_multiple(string $pc, array $areas, int $house): array {
    global $valid_ni_mapit_codes;
    global $valid_scotland_single_member_mapit_codes, $valid_scotland_multi_member_mapit_codes;
    global $valid_wales_mapit_codes;
    $db = new ParlDB();

    $member_names = \MySociety\TheyWorkForYou\Utility\House::house_to_members($house);
    $single_member_areas = [];
    $multi_member_areas = [];
    $member_area_names = [];
    if ($house == HOUSE_TYPE_SCOTLAND) {
        $single_member_areas = get_area_names_by_type($areas, $valid_scotland_single_member_mapit_codes);
        $multi_member_areas = get_area_names_by_type($areas, $valid_scotland_multi_member_mapit_codes);
        $member_area_names = array_merge($single_member_areas, $multi_member_areas);
    } elseif ($house == HOUSE_TYPE_WALES) {
        $member_area_names = get_area_names_by_type($areas, $valid_wales_mapit_codes);
    } elseif ($house == HOUSE_TYPE_NI) {
        $member_area_names = get_area_names_by_type($areas, $valid_ni_mapit_codes);
    }

    $params = [];
    foreach ($member_area_names as $i => $name) {
        $params[":area$i"] = $name;
    }
    $query_base = "SELECT member.person_id, constituency, house
        FROM member, person_names pn
        WHERE constituency IN (" . join(',', array_keys($params)) . ")
            AND member.person_id = pn.person_id AND pn.type = 'name'
            AND pn.end_date = (SELECT MAX(end_date) from person_names where person_names.person_id = member.person_id)
            AND house = $house";
    $q = $db->query($query_base . " AND left_reason = 'still_in_office'", $params);
    $current = true;
    if (!$q->rows() && ($dissolution = MySociety\TheyWorkForYou\Dissolution::db())) {
        $current = false;
        $q = $db->query(
            $query_base . " AND $dissolution[query]",
            array_merge($dissolution['params'], $params),
        );
    }

    $members = [];
    foreach ($q as $row) {
        $cons = $row['constituency'];
        try {
            $member = new MySociety\TheyWorkForYou\Member(['person_id' => $row['person_id']]);
        } catch (MySociety\TheyWorkForYou\MemberException $e) {
            continue;
        }
        if (!$member->valid) {
            continue;
        }
        $rep = buildRepData($member, $house, !$current);

        if ($house == HOUSE_TYPE_SCOTLAND && in_array($cons, $single_member_areas, true)) {
            $rep['type'] = 'constituency';
        } elseif ($house == HOUSE_TYPE_SCOTLAND) {
            $rep['type'] = 'regional';
        } else {
            $rep['type'] = 'regional';
        }
        $members[] = $rep;
    }

    // Sort: constituency members first, then regional
    usort($members, function ($a, $b) {
        if ($a['type'] === 'constituency' && $b['type'] !== 'constituency') {
            return -1;
        }
        if ($a['type'] !== 'constituency' && $b['type'] === 'constituency') {
            return 1;
        }
        return strcmp($a['name'], $b['name']);
    });

    return [
        'members' => $members,
        'current' => $current,
        'member_names' => $member_names,
    ];
}

function member_redirect(MySociety\TheyWorkForYou\Member &$MEMBER): void {
    if ($MEMBER->valid) {
        $url = $MEMBER->url();
        header("Location: $url");
        exit;
    }
}

function democracy_club_postcode($pc) {
    $pc = urlencode($pc);
    $data = web_lookup("https://developers.democracyclub.org.uk/api/v1/postcode/$pc/?include_current=1&auth_token=" . OPTION_DEMOCRACYCLUB_TOKEN);
    $data = json_decode($data);
    return $data;
}

function democracy_club_address($address) {
    $address = urlencode($address);
    $data = web_lookup("https://developers.democracyclub.org.uk/api/v1/address/$address/?include_current=1&auth_token=" . OPTION_DEMOCRACYCLUB_TOKEN);
    $data = json_decode($data);
    return $data;
}

function mapit_postcode($postcode) {
    $filename = 'postcode/' . rawurlencode($postcode);
    return mapit_lookup('postcode', $filename);
}

function mapit_address($address, $pc) {
    $address = urlencode($address);
    $url = str_replace('{s}', $address, OPTION_MAPIT_UPRN_LOOKUP);
    $file = web_lookup($url);
    $r = json_decode($file);
    if (isset($r->error)) {
        return mapit_postcode($pc);
    }
    $filename = 'point/4326/' . $r->wgs84_lon . ',' . $r->wgs84_lat;
    return mapit_lookup('point', $filename);
}

function mapit_lookup($type, $filename) {
    global $valid_mapit_area_types, $valid_local_authority_types;
    $headers = [];
    if (defined('OPTION_MAPIT_API_KEY') && OPTION_MAPIT_API_KEY) {
        $headers[] = 'X-Api-Key: ' . OPTION_MAPIT_API_KEY;
    }
    $file = web_lookup(OPTION_MAPIT_URL . $filename, $headers);
    $r = json_decode($file);
    if (isset($r->error)) {
        return '';
    }
    if ($type == 'postcode' && !isset($r->areas)) {
        return '';
    }

    $input = ($type == 'postcode') ? $r->areas : $r;
    $areas = [];
    foreach ($input as $row) {
        if (in_array($row->type, $valid_mapit_area_types, true)) {
            $areas[$row->type] = $row->name;
        }
        if (in_array($row->type, $valid_local_authority_types, true)) {
            $areas[$row->type] = $row->name;
        }
    }
    if (!isset($areas['WMC'])) {
        return '';
    }
    return $areas;
}

function show_address_list($pc, $addresses) {
    global $PAGE;
    $PAGE->page_start();
    $PAGE->stripe_start();
    include("address_list.php");
    $PAGE->page_end();
}

function web_lookup($url, $headers = []) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 20);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    if ($headers) {
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    }
    $file = curl_exec($ch);
    curl_close($ch);
    return $file;
}

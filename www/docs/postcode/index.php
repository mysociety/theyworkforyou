<?php

# For looking up a postcode and redirecting or displaying appropriately

use MySociety\TheyWorkForYou\DataClass\Postcode\RepresentativeData;
use MySociety\TheyWorkForYou\DataClass\Postcode\SectionData;
use MySociety\TheyWorkForYou\DataClass\Postcode\RepresentativeSectionData;
use MySociety\TheyWorkForYou\DataClass\Postcode\RepresentativeGroupData;
use MySociety\TheyWorkForYou\DataClass\Postcode\DevolvedRepresentativesData;
use MySociety\TheyWorkForYou\MapItAreaType;
use MySociety\TheyWorkForYou\PostcodeSection;
use MySociety\TheyWorkForYou\HouseType;
use MySociety\TheyWorkForYou\RepresentativeType;
use MySociety\TheyWorkForYou\Member;
use MySociety\TheyWorkForYou\MemberException;
use MySociety\TheyWorkForYou\Utility\House;
use MySociety\TheyWorkForYou\Utility\Member as MemberUtility;

include_once '../../includes/easyparliament/init.php';
include_once INCLUDESPATH . 'easyparliament/member.php';

$data = [];

$pc = get_http_var('pc');
if (!$pc) {
    postcode_error('Please supply a postcode!');
}
$data['pc'] = $pc;

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


if (has_any_area_type($constituencies, area_types: MapItAreaType::SCOTLAND)) {
    $rep_data = pick_multiple($pc, areas: $constituencies, house: HouseType::SCOTLAND);
} elseif (has_any_area_type($constituencies, area_types: MapItAreaType::WALES)) {
    $rep_data = pick_multiple($pc, areas: $constituencies, house: HouseType::WALES);
} elseif (has_any_area_type($constituencies, area_types: [MapItAreaType::NIE])) {
    $rep_data = pick_multiple($pc, areas: $constituencies, house: HouseType::NI);
} else {
    $MEMBER = fetch_mp($pc, constituencies: $constituencies, house: HouseType::COMMONS);
    if ($MEMBER?->valid) {
        member_redirect($MEMBER);
    }
    postcode_error(gettext('We were unable to find your MP.'));
}

$mp_data = fetch_mp_data($pc, constituencies: $constituencies);
if ($THEUSER->isloggedin()) {
    $CHANGEURL = new \MySociety\TheyWorkForYou\Url('useredit');
} else {
    $CHANGEURL = new \MySociety\TheyWorkForYou\Url('userchangepc');
}
$data['change_postcode_url'] = $CHANGEURL->generate();
$data['sections'] = build_postcode_sections(
    $pc,
    mp_data: $mp_data,
    rep_data: $rep_data,
    constituencies: $constituencies
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

/**
 * Build the representative card data shared by MP and devolved sections.
 */
function buildRepData(Member $member, HouseType $house, bool $former = false): RepresentativeData {
    [$image, ] = MemberUtility::findMemberImage($member->person_id(), smallonly: false, substitute_missing: true);

    $rep = new RepresentativeData();
    $rep->name = $member->full_name();
    $rep->party = $member->party();
    $rep->constituency = $member->constituency();
    $rep->mp_url = $member->url();
    $rep->person_id = $member->person_id();
    $rep->image = $image;
    $rep->former = $former;
    return $rep;
}

/**
 * @param array<string, string> $constituencies MapIt area types mapped to names.
 */
function fetch_mp(string $pc, array $constituencies, HouseType $house = HouseType::COMMONS): ?Member {
    global $THEUSER;
    $args = ['constituency' => $constituencies['WMC'], 'house' => $house->int_id()];
    try {
        $MEMBER = new Member($args);
    } catch (MemberException $e) {
        return null;
    }
    if ($MEMBER->person_id()) {
        $THEUSER->set_postcode_cookie($pc);
    }
    return $MEMBER;
}

/**
 * Returns null when no valid MP can be found.
 *
 * @param array<string, string> $constituencies MapIt area types mapped to names.
 */
function fetch_mp_data(string $pc, array $constituencies): ?RepresentativeData {
    $MEMBER = fetch_mp($pc, constituencies: $constituencies);
    if (!$MEMBER?->valid) {
        return null;
    }

    $former = isset($MEMBER->left_house[HOUSE_TYPE_COMMONS])
        && $MEMBER->left_house[HOUSE_TYPE_COMMONS]['date'] !== '9999-12-31';

    $mp_data = buildRepData($MEMBER, house: HouseType::COMMONS, former: $former);

    // Keep the election lookup in place so its data key can be updated for the next election.
    $db = new ParlDB();
    $q = $db->query(
        "SELECT data_value FROM personinfo WHERE person_id = :person_id AND data_key = 'standing_down_2024'",
        params: [':person_id' => $MEMBER->person_id()]
    );
    $mp_data->standing_down_upcoming_election = (bool) ($q->first()['data_value'] ?? false);

    return $mp_data;
}

/**
 * @return list<SectionData>
 */
function build_postcode_sections(
    string $pc,
    ?RepresentativeData $mp_data,
    DevolvedRepresentativesData $rep_data,
    array $constituencies
): array {
    return [
        build_mp_section($mp_data),
        build_devolved_section($rep_data),
    ];
}

function build_mp_section(?RepresentativeData $mp_data): RepresentativeSectionData {
    $section = new RepresentativeSectionData();
    $section->id = PostcodeSection::COMMONS;
    $section->title = $mp_data?->former
        ? gettext('Your former MP')
        : gettext('Your MP');
    $section->house = HouseType::COMMONS;
    if ($mp_data === null) {
        $section->data_available = false;
        $section->empty_message = gettext('We were unable to find your MP.');
        return $section;
    }
    $group = new RepresentativeGroupData();
    $group->members = [$mp_data];
    $section->groups = [$group];
    if ($mp_data->standing_down_upcoming_election) {
        $section->footer = gettext('They are standing down at the general election.');
    }
    return $section;
}

function build_devolved_section(DevolvedRepresentativesData $rep_data): RepresentativeSectionData {
    $section = new RepresentativeSectionData();
    $section->id = PostcodeSection::fromHouse($rep_data->house);
    $section->title = $rep_data->current
        ? sprintf(gettext('Your %s'), $rep_data->member_name_plural)
        : sprintf(gettext('Your former %s'), $rep_data->member_name_plural);
    $section->house = $rep_data->house;
    if (!$rep_data->members) {
        $section->data_available = false;
        $section->empty_message = sprintf(gettext('We were unable to find your %s.'), $rep_data->member_name_plural);
        return $section;
    }
    $section->groups = devolved_groups($rep_data->house, members: $rep_data->members);
    return $section;
}


/**
 * @param list<RepresentativeData> $members
 * @return list<RepresentativeGroupData>
 */
function devolved_groups(HouseType $house, array $members): array {
    if ($house !== HouseType::SCOTLAND) {
        $group = new RepresentativeGroupData();
        $group->members = shuffle_grouped_by_party($members);
        return [$group];
    }

    // Only Scotland remains: show the single constituency MSP separately from
    // the regional list MSPs, rather than treating them as one electoral group.
    $constituency = array_values(array_filter($members, function (RepresentativeData $member): bool {
        return $member->type === RepresentativeType::CONSTITUENCY;
    }));
    $regional = array_values(array_filter($members, function (RepresentativeData $member): bool {
        return $member->type === RepresentativeType::REGIONAL;
    }));

    $constituency_group = new RepresentativeGroupData();
    $constituency_group->title = gettext('Constituency MSP');
    $constituency_group->members = $constituency;

    $regional_group = new RepresentativeGroupData();
    $regional_group->title = gettext('Regional MSPs');
    $regional_group->members = shuffle_grouped_by_party($regional);

    return [$constituency_group, $regional_group];
}

/**
 * Randomise representative order while keeping members of the same party
 * together. Parties appear in a random order, but all members within each
 * party are grouped consecutively (sorted alphabetically by name).
 *
 * @param list<RepresentativeData> $members
 * @return list<RepresentativeData>
 */
function shuffle_grouped_by_party(array $members): array {
    // Group members by party
    $by_party = [];
    foreach ($members as $member) {
        $by_party[$member->party][] = $member;
    }

    // Sort members within each party by name
    foreach ($by_party as &$group) {
        usort($group, fn(RepresentativeData $a, RepresentativeData $b): int => strcmp($a->name, $b->name));
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
        if (isset($areas[$area_type->value])) {
            return true;
        }
    }
    return false;
}

function get_area_names_by_type(array $areas, array $area_types): array {
    $values = [];
    foreach ($area_types as $area_type) {
        if (isset($areas[$area_type->value])) {
            $values[] = $areas[$area_type->value];
        }
    }
    return $values;
}

function pick_multiple(string $pc, array $areas, HouseType $house): DevolvedRepresentativesData {
    $db = new ParlDB();

    $member_names = House::house_to_members($house->int_id());
    $single_member_areas = [];
    $multi_member_areas = [];
    $member_area_names = [];
    if ($house === HouseType::SCOTLAND) {
        $single_member_areas = get_area_names_by_type($areas, area_types: MapItAreaType::SCOTLAND_CONSTITUENCIES);
        $multi_member_areas = get_area_names_by_type($areas, area_types: MapItAreaType::SCOTLAND_REGIONS);
        $member_area_names = array_merge($single_member_areas, $multi_member_areas);
    } elseif ($house === HouseType::WALES) {
        $member_area_names = get_area_names_by_type($areas, area_types: MapItAreaType::WALES);
    } elseif ($house === HouseType::NI) {
        $member_area_names = get_area_names_by_type($areas, area_types: [MapItAreaType::NIE]);
    }

    $params = [':house' => $house->int_id()];
    $area_placeholders = [];
    foreach ($member_area_names as $i => $name) {
        $placeholder = ":area$i";
        $area_placeholders[] = $placeholder;
        $params[$placeholder] = $name;
    }
    // Only generated placeholder names form the IN clause; values are bound.
    $query_base = "SELECT member.person_id, constituency, house
        FROM member, person_names pn
        WHERE constituency IN (" . join(',', $area_placeholders) . ")
            AND member.person_id = pn.person_id AND pn.type = 'name'
            AND pn.end_date = (SELECT MAX(end_date) from person_names where person_names.person_id = member.person_id)
            AND house = :house";
    $q = $db->query($query_base . " AND left_reason = 'still_in_office'", params: $params);
    $current = true;
    if (!$q->rows() && ($dissolution = MySociety\TheyWorkForYou\Dissolution::db())) {
        $current = false;
        // Dissolution::db() supplies SQL structure with bound date values.
        $q = $db->query(
            $query_base . ' AND ' . $dissolution['query'],
            params: array_merge($dissolution['params'], $params),
        );
    }

    $members = [];
    foreach ($q as $row) {
        $cons = $row['constituency'];
        try {
            $member = new Member(['person_id' => $row['person_id']]);
        } catch (MemberException $e) {
            continue;
        }
        if (!$member->valid) {
            continue;
        }
        $rep = buildRepData($member, house: $house, former: !$current);

        if ($house === HouseType::SCOTLAND && in_array($cons, $single_member_areas, true)) {
            $rep->type = RepresentativeType::CONSTITUENCY;
        } else {
            $rep->type = RepresentativeType::REGIONAL;
        }
        $members[] = $rep;
    }

    // Sort: constituency members first, then regional
    usort($members, function (RepresentativeData $a, RepresentativeData $b): int {
        if ($a->type === RepresentativeType::CONSTITUENCY && $b->type !== RepresentativeType::CONSTITUENCY) {
            return -1;
        }
        if ($a->type !== RepresentativeType::CONSTITUENCY && $b->type === RepresentativeType::CONSTITUENCY) {
            return 1;
        }
        return strcmp($a->name, $b->name);
    });

    $result = new DevolvedRepresentativesData();
    $result->house = $house;
    $result->members = $members;
    $result->current = $current;
    $result->member_name_plural = $member_names['plural'];
    return $result;
}

function member_redirect(Member &$MEMBER): void {
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
    return mapit_lookup('postcode', filename: $filename);
}

function mapit_address($address, $pc) {
    $address = urlencode($address);
    $url = str_replace('{s}', replace: $address, subject: OPTION_MAPIT_UPRN_LOOKUP);
    $file = web_lookup($url);
    $r = json_decode($file);
    if (isset($r->error)) {
        return mapit_postcode($pc);
    }
    $filename = 'point/4326/' . $r->wgs84_lon . ',' . $r->wgs84_lat;
    return mapit_lookup('point', filename: $filename);
}

function mapit_lookup($type, $filename) {
    $headers = [];
    if (defined('OPTION_MAPIT_API_KEY') && OPTION_MAPIT_API_KEY) {
        $headers[] = 'X-Api-Key: ' . OPTION_MAPIT_API_KEY;
    }
    $file = web_lookup(OPTION_MAPIT_URL . $filename, headers: $headers);
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
        if (MapItAreaType::tryFrom($row->type) !== null) {
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

<?php

# For looking up a postcode and redirecting or displaying appropriately

include_once '../../includes/easyparliament/init.php';
include_once INCLUDESPATH . 'easyparliament/member.php';

$data = [];
$errors = [];


$valid_scotland_single_member_mapit_codes = ['SPC', 'SPCF'];
$valid_scotland_multi_member_mapit_codes = ['SPE', 'SPEF'];
$valid_wales_single_member_mapit_codes = ['WAC'];
$valid_wales_multi_member_mapit_codes = ['WAE','WACF'];
$valid_ni_mapit_codes = ['NIE'];
$valid_wmc_mapit_codes = ['WMC'];

$valid_scotland_mapit_codes = array_merge($valid_scotland_single_member_mapit_codes, $valid_scotland_multi_member_mapit_codes);
$valid_wales_mapit_codes = array_merge($valid_wales_single_member_mapit_codes, $valid_wales_multi_member_mapit_codes);
$valid_mapit_area_types = array_merge($valid_wmc_mapit_codes, $valid_scotland_mapit_codes, $valid_wales_mapit_codes, $valid_ni_mapit_codes);

$old_single_member_mapit_codes = ['SPC', 'WAC'];
$new_single_member_mapit_codes = ['SPCF', 'WACF'];
$old_multi_member_mapit_codes = ['SPE'];
$new_multi_member_mapit_codes = ['SPEF'];

// handling to switch the GE message based either on time or a query string

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
    $MEMBER = fetch_mp($pc, $constituencies);
    pick_multiple($pc, $constituencies, 'SPE', HOUSE_TYPE_SCOTLAND);
} elseif (has_any_area_type($constituencies, $valid_wales_mapit_codes)) {
    $data['multi'] = "wales";
    $MEMBER = fetch_mp($pc, $constituencies);
    pick_multiple($pc, $constituencies, 'WAE', HOUSE_TYPE_WALES);
} elseif (has_any_area_type($constituencies, $valid_ni_mapit_codes)) {
    $data['multi'] = "northern-ireland";
    $MEMBER = fetch_mp($pc, $constituencies);
    pick_multiple($pc, $constituencies, 'NIE', HOUSE_TYPE_NI);
} else {
    $data['multi'] = "uk";
    $MEMBER = fetch_mp($pc, $constituencies, 1);
    member_redirect($MEMBER);
}

$data['constituencies'] = $constituencies;
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

function fetch_mp($pc, $constituencies, $house = null) {
    global $THEUSER;
    $args = ['constituency' => $constituencies['WMC']];
    if ($house) {
        $args['house'] = $house;
    }
    try {
        $MEMBER = new MEMBER($args);
    } catch (MySociety\TheyWorkForYou\MemberException $e) {
        postcode_error($e->getMessage());
    }
    if ($MEMBER->person_id()) {
        $THEUSER->set_postcode_cookie($pc);
    }
    return $MEMBER;
}

/**
 * Check whether any of the given area types exist in the areas array.
 *
 * @param array $areas
 * @param array $area_types
 * @return bool
 */
function has_any_area_type($areas, $area_types) {
    foreach ($area_types as $area_type) {
        if (isset($areas[$area_type])) {
            return true;
        }
    }
    return false;
}

/**
 * Return areas for matching area types
 *
 * @param array $areas
 * @param array $area_types
 * @return array
 */
function get_area_names_by_type($areas, $area_types) {
    $values = [];
    foreach ($area_types as $area_type) {
        if (isset($areas[$area_type])) {
            $values[] = $areas[$area_type];
        }
    }
    return $values;
}

function pick_multiple($pc, $areas, $area_type, $house) {
    global $PAGE, $data;
    global $valid_ni_mapit_codes;
    global $valid_scotland_single_member_mapit_codes, $valid_scotland_multi_member_mapit_codes;
    global $valid_wales_single_member_mapit_codes, $valid_wales_multi_member_mapit_codes;
    $db = new ParlDB();

    $member_names = \MySociety\TheyWorkForYou\Utility\House::house_to_members($house);
    $single_member_areas = [];
    $multi_member_areas = [];
    $member_area_names = [];
    if ($house == HOUSE_TYPE_SCOTLAND) {
        $urlp = 'msp';
        $single_member_areas = get_area_names_by_type($areas, $valid_scotland_single_member_mapit_codes);
        $multi_member_areas = get_area_names_by_type($areas, $valid_scotland_multi_member_mapit_codes);
        $member_area_names = array_merge($single_member_areas, $multi_member_areas);
    } elseif ($house == HOUSE_TYPE_WALES) {
        $urlp = 'ms';
        $single_member_areas = get_area_names_by_type($areas, $valid_wales_single_member_mapit_codes);
        $multi_member_areas = get_area_names_by_type($areas, $valid_wales_multi_member_mapit_codes);
        $member_area_names = array_merge($single_member_areas, $multi_member_areas);
    } elseif ($house == HOUSE_TYPE_NI) {
        $urlp = 'mla';
        $member_area_names = get_area_names_by_type($areas, $valid_ni_mapit_codes);
    }
    $urlpl = $urlp . 's';
    $urlp = "/$urlp/?p=";

    $q = $db->query("SELECT member.person_id, given_name, family_name, constituency, left_house
        FROM member, person_names pn
        WHERE constituency = :constituency
            AND member.person_id = pn.person_id AND pn.type = 'name'
            AND pn.end_date = (SELECT MAX(end_date) from person_names where person_names.person_id = member.person_id)
        AND house = 1 ORDER BY left_house DESC LIMIT 1", [
        ':constituency' => MySociety\TheyWorkForYou\Utility\Constituencies::normaliseConstituencyName($areas['WMC']),
    ])->first();
    $mp = [];
    if ($q) {
        $mp = $q;
        $mp['former'] = ($mp['left_house'] != '9999-12-31');
        $q = $db->query("SELECT * FROM personinfo where person_id=:person_id AND data_key='standing_down_2024'", [':person_id' => $mp['person_id']]);
        $mp['standing_down_2024'] = $q['data_value'] ?? 0;
        $mp['name'] = $mp['given_name'] . ' ' . $mp['family_name'];
    }

    $query_base = "SELECT member.person_id, given_name, family_name, constituency, house
        FROM member, person_names pn
        WHERE constituency IN ('" . join("','", $member_area_names) . "')
            AND member.person_id = pn.person_id AND pn.type = 'name'
            AND pn.end_date = (SELECT MAX(end_date) from person_names where person_names.person_id = member.person_id)
            AND house = $house";
    $q = $db->query($query_base . " AND left_reason = 'still_in_office'");
    $current = true;
    if (!$q->rows() && ($dissolution = MySociety\TheyWorkForYou\Dissolution::db())) {
        $current = false;
        $q = $db->query(
            $query_base . " AND $dissolution[query]",
            $dissolution['params']
        );
    }

    // in this file we talk about single_member multiple member constituencies
    // externally this becomes mcon for single, mreg for multiple.
    $mcon = [];
    $mreg = [];
    foreach ($q as $row) {
        $cons = $row['constituency'];
        if ($house == HOUSE_TYPE_NI) {
            $mreg[] = $row;
        } elseif ($house == HOUSE_TYPE_SCOTLAND) {
            if (in_array($cons, $single_member_areas, true)) {
                $mcon = $row;
            } elseif (in_array($cons, $multi_member_areas, true)) {
                $mreg[] = $row;
            }
        } elseif ($house == HOUSE_TYPE_WALES) {
            if (in_array($cons, $single_member_areas, true)) {
                $mcon = $row;
            } elseif (in_array($cons, $multi_member_areas, true)) {
                $mreg[] = $row;
            }
        } else {
            $PAGE->error_message('Odd result returned, please let us know!');
            return;
        }
    }
    $data['mcon'] = $mcon;
    $data['mreg'] = $mreg;
    $data['house'] = $house;
    $data['urlp'] = $urlp;
    $data['current'] = $current;
    $data['areas'] = $areas;
    $data['area_type'] = $area_type;
    $data['member_names'] = $member_names;
    $data['mp'] = $mp;

    $data['MPSURL'] = new \MySociety\TheyWorkForYou\Url('mps');
    $data['REGURL'] = new \MySociety\TheyWorkForYou\Url($urlpl);
    $data['browse_text'] = sprintf(gettext('Browse all %s'), $member_names['plural']);
}

function member_redirect(&$MEMBER) {
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
    global $valid_mapit_area_types;
    $file = web_lookup(OPTION_MAPIT_URL . $filename);
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

function web_lookup($url) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 20);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $file = curl_exec($ch);
    curl_close($ch);
    return $file;
}

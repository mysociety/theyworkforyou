<?php

# For looking up a postcode and redirecting or displaying appropriately

include_once '../../includes/easyparliament/init.php';
include_once INCLUDESPATH . 'easyparliament/member.php';

$data = array();
$errors = array();

// handling to switch the GE message based either on time or a query string

$pc = get_http_var('pc');
if (!$pc) {
    postcode_error('Please supply a postcode!');
}
$data['pc'] = $pc;

$pc = preg_replace('#[^a-z0-9]#i', '', $pc);
if (!validate_postcode($pc)) {
    twfy_debug ('MP', "Can't display an MP because the submitted postcode wasn't of a valid form.");
    postcode_error("Sorry, " . _htmlentities($pc) . " isn't a valid postcode");
}

# 2024 ELECTION EXTRA

$constituencies = mapit_postcode($pc);
if (!$constituencies) {
    postcode_error("Sorry, " . _htmlentities($pc) . " isn't a known postcode");
}

if (isset($constituencies['SPE']) || isset($constituencies['SPC'])) {
    $data['multi'] = "scotland";
    $MEMBER = fetch_mp($pc, $constituencies);
    pick_multiple($pc, $constituencies, 'SPE', HOUSE_TYPE_SCOTLAND);
} elseif (isset($constituencies['WAE']) || isset($constituencies['WAC'])) {
    $data['multi'] = "wales";
    $MEMBER = fetch_mp($pc, $constituencies);
    pick_multiple($pc, $constituencies, 'WAE', HOUSE_TYPE_WALES);
} elseif (isset($constituencies['NIE'])) {
    $data['multi'] = "northern-ireland";
    $MEMBER = fetch_mp($pc, $constituencies);
    pick_multiple($pc, $constituencies, 'NIE', HOUSE_TYPE_NI);
} else {
    $data['multi'] = "uk";
    $MEMBER = fetch_mp($pc, $constituencies, 1);
    member_redirect($MEMBER);
}

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

function fetch_mp($pc, $constituencies, $house=null) {
    global $THEUSER;
    $args = array('constituency' => $constituencies['WMC']);
    if ($house) {
        $args['house'] = $house;
    }
    try {
        $MEMBER = new MEMBER($args);
    } catch (MySociety\TheyWorkForYou\MemberException $e){
        postcode_error($e->getMessage());
    }
    if ($MEMBER->person_id()) {
        $THEUSER->set_postcode_cookie($pc);
    }
    return $MEMBER;
}

function pick_multiple($pc, $areas, $area_type, $house) {
    global $PAGE, $data;
    $db = new ParlDB;

    $member_names = \MySociety\TheyWorkForYou\Utility\House::house_to_members($house);
    if ($house == HOUSE_TYPE_SCOTLAND) {
        $urlp = 'msp';
        $a = [ $areas['SPC'], $areas['SPE'] ];
    } elseif ($house == HOUSE_TYPE_WALES) {
        $urlp = 'ms';
        $a = [ $areas['WAC'], $areas['WAE'] ];
    } elseif ($house == HOUSE_TYPE_NI) {
        $urlp = 'mla';
        $a = [ $areas['NIE'] ];
    }
    $urlpl = $urlp . 's';
    $urlp = "/$urlp/?p=";

    $q = $db->query("SELECT member.person_id, given_name, family_name, constituency, left_house
        FROM member, person_names pn
        WHERE constituency = :constituency
            AND member.person_id = pn.person_id AND pn.type = 'name'
            AND pn.end_date = (SELECT MAX(end_date) from person_names where person_names.person_id = member.person_id)
        AND house = 1 ORDER BY left_house DESC LIMIT 1", array(
            ':constituency' => MySociety\TheyWorkForYou\Utility\Constituencies::normaliseConstituencyName($areas['WMC'])
            ))->first();
    $mp = array();
    if ($q) {
        $mp = $q;
        $mp['former'] = ($mp['left_house'] != '9999-12-31');
        $q = $db->query("SELECT * FROM personinfo where person_id=:person_id AND data_key='standing_down_2024'", [':person_id' => $mp['person_id']]);
        $mp['standing_down_2024'] = $q['data_value'] ?? 0;
        $mp['name'] = $mp['given_name'] . ' ' . $mp['family_name'];
    }

    $query_base = "SELECT member.person_id, given_name, family_name, constituency, house
        FROM member, person_names pn
        WHERE constituency IN ('" . join("','", $a) . "')
            AND member.person_id = pn.person_id AND pn.type = 'name'
            AND pn.end_date = (SELECT MAX(end_date) from person_names where person_names.person_id = member.person_id)
            AND house = $house";
    $q = $db->query($query_base . " AND left_reason = 'still_in_office'");
    $current = true;
    if (!$q->rows() && ($dissolution = MySociety\TheyWorkForYou\Dissolution::db())) {
        $current = false;
        $q = $db->query($query_base . " AND $dissolution[query]",
            $dissolution['params']);
    }

    $mcon = array(); $mreg = array();
    foreach ($q as $row) {
        $cons = $row['constituency'];
        if ($house == HOUSE_TYPE_NI) {
            $mreg[] = $row;
        } elseif ($house == HOUSE_TYPE_SCOTLAND) {
            if ($cons == $areas['SPC']) {
                $mcon = $row;
            } elseif ($cons == $areas['SPE']) {
                $mreg[] = $row;
            }
        } elseif ($house == HOUSE_TYPE_WALES) {
            if ($cons == $areas['WAC']) {
                $mcon = $row;
            } elseif ($cons == $areas['WAE']) {
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

function mapit_postcode($postcode) {
    $filename = 'postcode/' . rawurlencode($postcode);
    return mapit_lookup('postcode', $filename);
}

function mapit_lookup($type, $filename) {
    $file = web_lookup(OPTION_MAPIT_URL . $filename);
    $r = json_decode($file);
    if (isset($r->error)) return '';
    if ($type == 'postcode' && !isset($r->areas)) return '';

    $input = ($type == 'postcode') ? $r->areas : $r;
    $areas = array();
    foreach ($input as $row) {
        if (in_array($row->type, array('WMC', 'WMCF', 'SPC', 'SPE', 'NIE', 'WAC', 'WAE')))
            $areas[$row->type] = $row->name;
    }
    if (!isset($areas['WMC'])) {
        return '';
    }
    return $areas;
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

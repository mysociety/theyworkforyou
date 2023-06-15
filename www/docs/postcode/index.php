<?php

# For looking up a postcode and redirecting or displaying appropriately

include_once '../../includes/easyparliament/init.php';
include_once INCLUDESPATH . 'easyparliament/member.php';

$errors = array();

$pc = get_http_var('pc');
if (!$pc) {
    postcode_error('Please supply a postcode!');
}

$pc = preg_replace('#[^a-z0-9]#i', '', $pc);
if (!validate_postcode($pc)) {
    twfy_debug ('MP', "Can't display an MP because the submitted postcode wasn't of a valid form.");
    postcode_error("Sorry, " . _htmlentities($pc) . " isn't a valid postcode");
}

$constituencies = MySociety\TheyWorkForYou\Utility\Postcode::postcodeToConstituencies($pc);
if ($constituencies == 'CONNECTION_TIMED_OUT') {
    postcode_error("Sorry, we couldn't check your postcode right now, as our postcode lookup server is under quite a lot of load.");
} elseif (!$constituencies) {
    postcode_error("Sorry, " . _htmlentities($pc) . " isn't a known postcode");
}

$out = ''; $sidebars = array();
if (isset($constituencies['SPE']) || isset($constituencies['SPC'])) {
    $multi = "scotland";
    $MEMBER = fetch_mp($pc, $constituencies);
    list($out, $sidebars) = pick_multiple($pc, $constituencies, 'SPE', HOUSE_TYPE_SCOTLAND);
} elseif (isset($constituencies['WAE']) || isset($constituencies['WAC'])) {
    $multi = "wales";
    $MEMBER = fetch_mp($pc, $constituencies);
    list($out, $sidebars) = pick_multiple($pc, $constituencies, 'WAE', HOUSE_TYPE_WALES);
} elseif (isset($constituencies['NIE'])) {
    $multi = "northern-ireland";
    $MEMBER = fetch_mp($pc, $constituencies);
    list($out, $sidebars) = pick_multiple($pc, $constituencies, 'NIE', HOUSE_TYPE_NI);
} else {
    # Just have an MP, redirect instantly to the canonical page
    $multi = "uk";
    $MEMBER = fetch_mp($pc, $constituencies, 1);
    member_redirect($MEMBER);
}

$PAGE->page_start();
$PAGE->stripe_start();
echo $out;
include("repexplain.php");
$PAGE->stripe_end($sidebars);
$PAGE->page_end();

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
    global $PAGE;
    $db = new ParlDB;

    $member_names = \MySociety\TheyWorkForYou\Utility\House::house_to_members($house);
    if ($house == HOUSE_TYPE_SCOTLAND) {
        $urlp = 'msp';
    } elseif ($house == HOUSE_TYPE_WALES) {
        $urlp = 'ms';
    } elseif ($house == HOUSE_TYPE_NI) {
        $urlp = 'mla';
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
        if ($mp['left_house'] != '9999-12-31') {
            $mp['former'] = true;
        }
    }

    $a = array_values($areas);

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

    $out = '';
    $out .= '<h1>' . gettext("Your representatives") . '</h1>';
    $out .= '<ul><li>';
    $name = $mp['given_name'] . ' ' . $mp['family_name'];
    if (isset($mp['former'])) {
        $out .= sprintf(gettext('Your former <strong>MP</strong> (Member of Parliament) is <a href="%s">%s</a>, %s'), '/mp/?p=' . $mp['person_id'], $name, gettext($mp['constituency']));
    } else {
        $out .= sprintf(gettext('Your <strong>MP</strong> (Member of Parliament) is <a href="%s">%s</a>, %s'), '/mp/?p=' . $mp['person_id'], $name, gettext($mp['constituency']));
    }
    $out .= '</li>';
    if ($mcon) {
        $name = $mcon['given_name'] . ' ' . $mcon['family_name'];
        $out .= '<li>';
        if ($house == HOUSE_TYPE_SCOTLAND) {
            $url = $urlp . $mcon['person_id'];
            $cons = $mcon['constituency'];
            if ($current) {
                $out .= sprintf(gettext('Your <strong>constituency MSP</strong> (Member of the Scottish Parliament) is <a href="%s">%s</a>, %s'), $url, $name, $cons);
            } else {
                $out .= sprintf(gettext('Your <strong>constituency MSP</strong> (Member of the Scottish Parliament) was <a href="%s">%s</a>, %s'), $url, $name, $cons);
            }
        } elseif ($house == HOUSE_TYPE_WALES) {
            $url = $urlp . $mcon['person_id'];
            $cons = gettext($mcon['constituency']);
            if ($current) {
                # First %s is URL, second %s is name, third %s is constituency
                $out .= sprintf(gettext('Your <strong>constituency MS</strong> (Member of the Senedd) is <a href="%s">%s</a>, %s'), $url, $name, $cons);
            } else {
                # First %s is URL, second %s is name, third %s is constituency
                $out .= sprintf(gettext('Your <strong>constituency MS</strong> (Member of the Senedd) was <a href="%s">%s</a>, %s'), $url, $name, $cons);
            }
        }
        $out .= '</li>';
    }
    if ($current) {
        if ($house == HOUSE_TYPE_NI) {
            $out .= '<li>' . sprintf(gettext('Your <strong>%s MLAs</strong> (Members of the Legislative Assembly) are:'), $areas[$area_type]);
        } elseif ($house == HOUSE_TYPE_WALES){
            $out .= '<li>' . sprintf(gettext('Your <strong>%s region MSs</strong> are:'), gettext($areas[$area_type]));
        } else {
            $out .= '<li>' . sprintf(gettext('Your <strong>%s %s</strong> are:'), gettext($areas[$area_type]), $member_names['plural']);
        }
    } else {
        if ($house == HOUSE_TYPE_NI) {
            $out .= '<li>' . sprintf(gettext('Your <strong>%s MLAs</strong> (Members of the Legislative Assembly) were:'), $areas[$area_type]);
        } elseif ($house == HOUSE_TYPE_WALES){
            $out .= '<li>' . sprintf(gettext('Your <strong>%s region MSs</strong> were:'), gettext($areas[$area_type]));
        } else {
            $out .= '<li>' . sprintf(gettext('Your <strong>%s %s</strong> were:'), gettext($areas[$area_type]), $member_names['plural']);
        }
    }
    $out .= '<ul>';
    foreach ($mreg as $reg) {
        $out .= '<li><a href="' . $urlp . $reg['person_id'] . '">';
        $out .= $reg['given_name'] . ' ' . $reg['family_name'];
        $out .= '</a>';
    }
    $out .= '</ul></ul>';

    $MPSURL = new \MySociety\TheyWorkForYou\Url('mps');
    $REGURL = new \MySociety\TheyWorkForYou\Url($urlpl);
    $browse_text = sprintf(gettext('Browse all %s'), $member_names['plural']);
    $sidebar = array(array(
        'type' => 'html',
        'content' => '<div class="block"><h4>' . gettext('Browse people') . '</h4>
            <ul><li><a href="' . $MPSURL->generate() . '">' . gettext('Browse all MPs') . '</a></li>
            <li><a href="' . $REGURL->generate() . '">' . $browse_text . '</a></li>
            </ul></div>'
    ));
    return array($out, $sidebar);
}

function member_redirect(&$MEMBER) {
    if ($MEMBER->valid) {
        $url = $MEMBER->url();
        header("Location: $url");
        exit;
    }
}

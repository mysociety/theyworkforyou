<?php

# For looking up a postcode and redirecting or displaying appropriately

include_once '../../includes/easyparliament/init.php';
include_once INCLUDESPATH . 'easyparliament/member.php';
include_once INCLUDESPATH . 'postcode.inc';

$errors = array();

$pc = get_http_var('pc');
if (!$pc) {
    $PAGE->error_message('Please supply a postcode!', true);
    exit;
}

$pc = preg_replace('#[^a-z0-9]#i', '', $pc);
$out = ''; $sidebars = array();
if (validate_postcode($pc)) {
    $constituencies = postcode_to_constituencies($pc);
    if ($constituencies == 'CONNECTION_TIMED_OUT') {
        $errors['pc'] = "Sorry, we couldn't check your postcode right now, as our postcode lookup server is under quite a lot of load.";
    } elseif (!$constituencies) {
        $errors['pc'] = "Sorry, " . _htmlentities($pc) . " isn't a known postcode";
    } elseif (isset($constituencies['SPE']) || isset($constituencies['SPC'])) {
        $MEMBER = new MEMBER(array('constituency' => $constituencies['WMC']));
        if ($MEMBER->person_id()) {
            $THEUSER->set_postcode_cookie($pc);
        }
        list($out, $sidebars) = pick_multiple($pc, $constituencies, 'SPE', 'MSP');
    } elseif (isset($constituencies['NIE'])) {
        $MEMBER = new MEMBER(array('constituency' => $constituencies['WMC']));
        if ($MEMBER->person_id()) {
            $THEUSER->set_postcode_cookie($pc);
        }
        list($out, $sidebars) = pick_multiple($pc, $constituencies, 'NIE', 'MLA');
    } else {
        # Just have an MP, redirect instantly to the canonical page
        $MEMBER = new MEMBER(array('constituency' => $constituencies['WMC'], 'house' => 1));
        if ($MEMBER->person_id()) {
            $THEUSER->set_postcode_cookie($pc);
        }
        member_redirect($MEMBER);
    }
} else {
    $errors['pc'] = "Sorry, " . _htmlentities($pc) . " isn't a valid postcode";
    twfy_debug ('MP', "Can't display an MP because the submitted postcode wasn't of a valid form.");
}

$PAGE->page_start();
$PAGE->stripe_start();
if (isset($errors['pc'])) {
    $PAGE->error_message($errors['pc']);
    $PAGE->postcode_form();
}
echo $out;
$PAGE->stripe_end($sidebars);
$PAGE->page_end();

# ---

function pick_multiple($pc, $areas, $area_type, $rep_type) {
    global $PAGE;
    $db = new ParlDB;

    $q = $db->query("SELECT person_id, first_name, last_name, constituency, left_house FROM member
        WHERE constituency = '" . mysql_real_escape_string(normalise_constituency_name($areas['WMC'])) . "'
        AND house = 1 ORDER BY left_house DESC LIMIT 1");
    $mp = array();
    if ($q->rows()) {
        $mp = $q->row(0);
        if ($mp['left_house'] != '9999-12-31') $mp['former'] = true;
    }

    $a = array_values($areas);

    $q = $db->query("SELECT person_id, first_name, last_name, constituency, house FROM member
        WHERE constituency IN ('" . join("','", $a) . "')
        AND left_reason = 'still_in_office' AND house in (3,4)");
    $current = true;
    if (!$q->rows()) {
        # XXX No results implies dissolution, fix for 2011.
        $current = false;
        $q = $db->query("SELECT person_id, first_name, last_name, constituency, house FROM member
            WHERE constituency IN ('" . join("','", $a) . "')
            AND ( (house=3 AND left_house='2011-03-24') OR (house=4 AND left_house='2011-03-23') )");
    }

    $mcon = array(); $mreg = array();
    for ($i=0; $i<$q->rows(); $i++) {
        $house = $q->field($i, 'house');
        $cons = $q->field($i, 'constituency');
        if ($house==3) {
            $mreg[] = $q->row($i);
        } elseif ($house==4) {
            if ($cons == $areas['SPC']) {
                $mcon = $q->row($i);
            } elseif ($cons == $areas['SPE']) {
                $mreg[] = $q->row($i);
            }
        } else {
            $PAGE->error_message('Odd result returned, please let us know!');
            return;
        }
    }

    $out = '';
    $out .= '<p>That postcode has multiple results, please pick who you are interested in:</p>';
    $out .= '<ul><li>Your ';
    if (isset($mp['former'])) $out .= 'former ';
    $out .= '<strong>MP</strong> (Member of Parliament) is <a href="/mp/?p=' . $mp['person_id'] . '">';
    $out .= $mp['first_name'] . ' ' . $mp['last_name'] . '</a>, ' . $mp['constituency'] . '</li>';
    if ($mcon) {
        $out .= '<li>Your <strong>constituency MSP</strong> (Member of the Scottish Parliament) ';
        $out .= $current ? 'is' : 'was';
        $out .= ' <a href="/msp/?p=' . $mcon['person_id'] . '">';
        $out .= $mcon['first_name'] . ' ' . $mcon['last_name'] . '</a>, ' . $mcon['constituency'] . '</li>';
    }
    $out .= '<li>Your <strong>' . $areas[$area_type] . ' ' . $rep_type . 's</strong> ';
    if ($rep_type=='MLA') $out .= '(Members of the Legislative Assembly)';
    $out .= ' ' . ($current ? 'are' : 'were') . ':';
    $out .= '<ul>';
    foreach ($mreg as $reg) {
        $out .= '<li><a href="/' . strtolower($rep_type) . '/?p=' . $reg['person_id'] . '">';
        $out .= $reg['first_name'] . ' ' . $reg['last_name'];
        $out .= '</a>';
    }
    $out .= '</ul></ul>';

    $MPSURL = new URL('mps');
    $REGURL = new URL(strtolower($rep_type) . 's');
    $sidebar = array(array(
        'type' => 'html',
        'content' => '<div class="block"><h4>Browse people</h4>
            <ul><li><a href="' . $MPSURL->generate() . '">Browse all MPs</a></li>
            <li><a href="' . $REGURL->generate() . '">Browse all ' . $rep_type . 's</a></li>
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

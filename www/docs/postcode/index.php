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
		$errors['pc'] = "Sorry, ".htmlentities($pc) ." isn't a known postcode";
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
		$MEMBER = new MEMBER(array('constituency' => $constituencies['WMC']));
		if ($MEMBER->person_id()) {
			$THEUSER->set_postcode_cookie($pc);
		}
		member_redirect($MEMBER);
	}
} else {
	$errors['pc'] = "Sorry, ".htmlentities($pc) ." isn't a valid postcode";
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
	$a = array_values($areas);
	$db = new ParlDB;
	$q = $db->query("SELECT person_id, first_name, last_name, constituency, house FROM member 
		WHERE constituency IN ('" . join("','", $a) . "') 
		AND left_reason = 'still_in_office'");
	$mp = array(); $mcon = array(); $mreg = array();
	for ($i=0; $i<$q->rows(); $i++) {
		$house = $q->field($i, 'house');
		$pid = $q->field($i, 'person_id');
		$name = $q->field($i, 'first_name') . ' ' . $q->field($i, 'last_name');
		$cons = $q->field($i, 'constituency');
		if ($house==1) {
			$mp = $q->row($i);
		} elseif ($house==3) {
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
	$out .= '<ul><li>Your <strong>MP</strong> (Member of Parliament) is <a href="/mp/?p=' . $mp['person_id'] . '">';
	$out .= $mp['first_name'] . ' ' . $mp['last_name'] . '</a>, ' . $mp['constituency'] . '</li>';
	if ($mcon) {
		$out .= '<li>Your <strong>constituency MSP</strong> (Member of the Scottish Parliament) is <a href="/msp/?p=' . $mcon['person_id'] . '">';
		$out .= $mcon['first_name'] . ' ' . $mcon['last_name'] . '</a>, ' . $mcon['constituency'] . '</li>';
	}
	$out .= '<li>Your <strong>' . $areas[$area_type] . ' ' . $rep_type . 's</strong> ';
	if ($rep_type=='MLA') $out .= '(Members of the Legislative Assembly)';
	$out .= ' are:';
	$out .= '<ul>';
	foreach($mreg as $reg) {
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


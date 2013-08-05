<?php

/* For displaying info about a person for a postcode or constituency.

This page accepts either 'm' (a member_id), 'pid' (a person_id),
'c' (a postcode or constituency), or 'n' (a name).

First, we check to see if a person_id's been submitted.
If so, we display that person.

Else, we check to see if a member_id's been submitted.
If so, we display that person.

Otherwise, we then check to see if a postcode's been submitted.
If it's valid we put it in a cookie.

If no postcode, we check to see if a constituency's been submitted.

If neither has been submitted, we see if either the user is logged in
and has a postcode set or the user has a cookied postcode from a previous
search.

If we have a valid constituency after all this, we display its MP.

Either way, we print the forms.

*/

include_once "../../includes/easyparliament/init.php";
include_once INCLUDESPATH . "easyparliament/member.php";
include_once INCLUDESPATH . "postcode.inc";
include_once INCLUDESPATH . 'technorati.php';
include_once '../api/api_getGeometry.php';
include_once '../api/api_getConstituencies.php';

twfy_debug_timestamp("after includes");

$errors = array();

// Some legacy URLs use 'p' rather than 'pid' for person id. So we still
// need to detect these.
$pid = get_http_var('pid') != '' ? get_http_var('pid') : get_http_var('p');
$name = strtolower(str_replace('_', ' ', get_http_var('n')));
$constituency = strtolower(str_replace('_', ' ', get_http_var('c')));
if ($constituency == 'mysociety test constituency') {
    header("Location: stom.html");
    exit;
}

# Special case names
$redirect = false;
if ($name == 'sion simon') $name = "si\xf4n simon";
if ($name == 'sian james') $name = "si\xe2n james";
if ($name == 'lembit opik') $name = "lembit \xf6pik";
if ($name == 'bairbre de brun') $name = "bairbre de br\xfan";
if ($name == 'daithi mckay') $name = "daith\xed mckay";
if ($name == 'caral ni chuilin') $name = "car\xe1l n\xed chuil\xedn";
if ($name == 'caledon du pre') $name = "caledon du pr\xe9";
if ($name == 'sean etchingham') $name = "se\xe1n etchingham";
if ($name == 'john tinne') $name = "john tinn\xe9";
if ($name == 'renee short') $name = "ren\xe9e short";

$name_fix = array(
    'a j beith' => 'alan beith',
    'micky brady' => 'mickey brady',
    'daniel rogerson' => 'dan rogerson',
    'andrew slaughter' => 'andy slaughter',
    'robert wilson' => array('rob wilson', 'reading east'),
    'james mcgovern' => 'jim mcgovern',
    'patrick mcfadden' => 'pat mcfadden',
    'chris leslie' => 'christopher leslie',
    'joseph meale' => 'alan meale',
    'james sheridan' => 'jim sheridan',
    'chinyelu onwurah' => 'chi onwurah',
    'steve rotherham' => 'steve rotheram',
    'michael weatherley' => 'mike weatherley',
    'louise bagshawe' => 'louise mensch',
    'andrew sawford' => 'andy sawford',
);
if (array_key_exists($name, $name_fix)) {
    if (is_array($name_fix[$name])) {
        if ($constituency == $name_fix[$name][1]) {
            $name = $name_fix[$name][0];
            $redirect = true;
        }
    } else {
        $name = $name_fix[$name];
        $redirect = true;
    }
}

# Special stuff for Ynys Mon
if ($constituency == 'ynys mon') $constituency = "ynys m\xf4n";
# And cope with Unicode URL
if (preg_match("#^ynys m\xc3\xb4n#i", $constituency))
    $constituency = "ynys m\xf4n";

// Redirect for MP recent appearanecs
if (get_http_var('recent')) {
    if ($THEUSER->postcode_is_set() && !$pid) {
        $MEMBER = new MEMBER(array('postcode' => $THEUSER->postcode(), 'house' => 1));
        if ($MEMBER->person_id())
            $pid = $MEMBER->person_id();
    }
    if ($pid) {
        $URL = new URL('search');
        $URL->insert( array('pid'=>$pid, 'pop'=>1) );
        header('Location: http://' . DOMAIN . $URL->generate('none'));
        exit;
    }
}

/////////////////////////////////////////////////////////
// CHECK SUBMITTED MEMBER (term of office) ID.

if (get_http_var('peer')) $this_page = 'peer';
elseif (get_http_var('royal')) $this_page = 'royal';
elseif (get_http_var('mla')) $this_page = 'mla';
elseif (get_http_var('msp')) $this_page = 'msp';
else $this_page = 'mp';

if (is_numeric(get_http_var('m'))) {
    // Got a member id, redirect to the canonical MP page, with a person id.
    $MEMBER = new MEMBER(array('member_id' => get_http_var('m')));
    member_redirect($MEMBER);

} elseif (is_numeric($pid)) {

    // Normal, plain, displaying an MP by person ID.
    $MEMBER = new MEMBER(array('person_id' => $pid));
    member_redirect($MEMBER);

/////////////////////////////////////////////////////////
// CHECK SUBMITTED POSTCODE

} elseif (get_http_var('pc') != '') {
    // User has submitted a postcode, so we want to display that.
    $pc = get_http_var('pc');
    $pc = preg_replace('#[^a-z0-9]#i', '', $pc);
    if (validate_postcode($pc)) {
        twfy_debug ('MP', "MP lookup by postcode");
        $constituency = strtolower(postcode_to_constituency($pc));
        if ($constituency == "connection_timed_out") {
            $errors['pc'] = "Sorry, we couldn't check your postcode right now, as our postcode lookup server is under quite a lot of load.";
        } elseif ($constituency == "") {
            $errors['pc'] = "Sorry, ".htmlentities($pc) ." isn't a known postcode";
            twfy_debug ('MP', "Can't display an MP, as submitted postcode didn't match a constituency");
        } else {
            // Redirect to the canonical MP page, with a person id.
            $MEMBER = new MEMBER(array('constituency' => $constituency, 'house' => 1));
            if ($MEMBER->person_id()) {
                // This will cookie the postcode.
                $THEUSER->set_postcode_cookie($pc);
            }
            member_redirect($MEMBER, 302);
        }
    } else {
        $errors['pc'] = "Sorry, ".htmlentities($pc) ." isn't a valid postcode";
        twfy_debug ('MP', "Can't display an MP because the submitted postcode wasn't of a valid form.");
    }

/////////////////////////////////////////////////////////
// DOES THE USER HAVE A POSTCODE ALREADY SET?
// (Either in their logged-in details or in a cookie from a previous search.)

} elseif ($this_page == 'msp' && $THEUSER->postcode_is_set() && $name == '' && $constituency == '') {
    $this_page = 'yourmsp';
    if (postcode_is_scottish($THEUSER->postcode())) {
        regional_list($THEUSER->postcode(), 'SPC', 'msp');
        exit;
    } else {
        $PAGE->error_message('Your set postcode is not in Scotland.');
    }
} elseif ($this_page == 'mla' && $THEUSER->postcode_is_set() && $name == '' && $constituency == '') {
    $this_page = 'yourmla';
    if (postcode_is_ni($THEUSER->postcode())) {
        regional_list($THEUSER->postcode(), 'NIE', 'mla');
        exit;
    } else {
        $PAGE->error_message('Your set postcode is not in Northern Ireland.');
    }
} elseif ($THEUSER->postcode_is_set() && $name == '' && $constituency == '') {
    $MEMBER = new MEMBER(array('postcode' => $THEUSER->postcode(), 'house' => 1));
    member_redirect($MEMBER, 302);
} elseif ($name && $constituency) {
    $MEMBER = new MEMBER(array('name'=>$name, 'constituency'=>$constituency));
    if (($MEMBER->house_disp==2 && $this_page!='peer') || !$MEMBER->canonical || $redirect) {
        member_redirect($MEMBER);
    }
    if ($MEMBER->the_users_mp) {
        $this_page = 'yourmp';
    }
    twfy_debug ('MP', 'Displaying MP by name');
} elseif ($name) {
    $MEMBER = new MEMBER(array('name' => $name));
    if (((($MEMBER->house_disp==1)
        || ($MEMBER->house_disp==2 && $this_page!='peer'))
        && ($MEMBER->valid || !is_array($MEMBER->person_id()))) || !$MEMBER->canonical || $redirect) {
        member_redirect($MEMBER);
    }
    if (preg_match('#^(mr|mrs|ms)#', $name)) {
        member_redirect($MEMBER);
    }
} elseif ($constituency) {
    if ($constituency == 'your & my society') {
        header('Location: /mp/stom%20teinberg');
        exit;
    }
    $MEMBER = new MEMBER(array('constituency' => $constituency, 'house' => 1));
    member_redirect($MEMBER);
} else {
    // No postcode, member_id or person_id to use.
    twfy_debug ('MP', "We don't have any way of telling what MP to display");
}




/////////////////////////////////////////////////////////
// DISPLAY A REPRESENTATIVE

#header('Cache-Control: max-age=900');

if (isset($MEMBER) && is_array($MEMBER->person_id())) {
    $PAGE->page_start();
    $PAGE->stripe_start('side');
    print '<p>That name is not unique. Please select from the following:</p><ul>';
    $c = 0;
    foreach ($MEMBER->person_id() as $row) {
        print '<li><a href="' . WEBPATH . 'mp/?pid='.$row['person_id'].'">' . ucwords(strtolower($name)) . ', ' . $row['constituency'] . '</a></li>';
    }
    print '</ul>';

    $MPSURL = new URL('mps');
    $sidebar = array(
        'type' => 'html',
        'content' => '<div class="block">
                <h4><a href="' . $MPSURL->generate() . '">Browse all MPs</a></h4>
            </div>'
    );

    $PAGE->stripe_end(array($sidebar));

} elseif (isset($MEMBER) && $MEMBER->person_id()) {

    twfy_debug_timestamp("before load_extra_info");
    $MEMBER->load_extra_info(true);
    twfy_debug_timestamp("after load_extra_info");

    $member_name = ucfirst($MEMBER->full_name());

    $title = $member_name;
    $desc = "Read $member_name's contributions to Parliament, including speeches and questions";
    if ($MEMBER->current_member_anywhere())
        $desc .= ', investigate their voting record, and get email alerts on their activity';

    if ($MEMBER->house(1)) {
        if (!$MEMBER->current_member(1)) {
            $title .= ', former';
        }
        $title .= ' MP';
        if ($MEMBER->constituency()) $title .= ', ' . $MEMBER->constituency();
    }
    if ($MEMBER->house(3)) {
        if ($MEMBER->house(1) || $MEMBER->house(2)) {
            $desc = str_replace('Parliament', 'Parliament and the Northern Ireland Assembly', $desc);
        } else {
            $desc = str_replace('Parliament', 'the Northern Ireland Assembly', $desc);
        }
        if (!$MEMBER->current_member(3)) {
            $title .= ', former';
        }
        $title .= ' MLA';
        if ($MEMBER->constituency()) $title .= ', ' . $MEMBER->constituency();
    }
    if ($MEMBER->house(4)) {
        if ($MEMBER->house(1) || $MEMBER->house(2)) {
            $desc = str_replace('Parliament', 'the UK and Scottish Parliaments', $desc);
        } else {
            $desc = str_replace('Parliament', 'the Scottish Parliament', $desc);
        }
        $desc = str_replace(', and get email alerts on their activity', '', $desc);
        if (!$MEMBER->current_member(4)) {
            $title .= ', former';
        }
        $title .= ' MSP, '.$MEMBER->constituency();
    }
    $DATA->set_page_metadata($this_page, 'title', $title);
    $DATA->set_page_metadata($this_page, 'meta_description', $desc);
    $DATA->set_page_metadata($this_page, 'heading', '');

    // So we can put a link in the <head> in $PAGE->page_start();
    $feedurl = $DATA->page_metadata('mp_rss', 'url') . $MEMBER->person_id() . '.rdf';
    if (file_exists(BASEDIR . '/' . $feedurl))
        $DATA->set_page_metadata($this_page, 'rss', $feedurl);

    twfy_debug_timestamp("before page_start");
    $PAGE->page_start();
    twfy_debug_timestamp("after page_start");

    twfy_debug_timestamp("before stripe start");
    $PAGE->stripe_start('side', 'person_page');
    twfy_debug_timestamp("after stripe start");

    twfy_debug_timestamp("before display of MP");

    $MEMBER->display();

    twfy_debug_timestamp("after display of MP");

    // SIDEBAR.

    // We have to generate this HTML to pass to stripe_end().
    $linkshtml = generate_member_links($MEMBER);

    $sidebars = array(
/*        array('type'=>'include', 'content' => 'donate'),*/
/*        array (
            'type'        => 'include',
            'content'    => 'mp_email_friend'
        ), */
        array (
            'type'        => 'include',
            'content'    => 'mp_speech_search'
        ),
        array (
            'type'        => 'html',
            'content'    => $linkshtml
        )
    );

/*
    if ($rssurl = $DATA->page_metadata($this_page, 'rss')) {
        $sidebars[] = array (
            'type'         => 'html',
            'content'    => $PAGE->member_rss_block(array('appearances' => WEBPATH . $rssurl))
        );
    }
*/

    if ($MEMBER->house(1)) {
        $previous_people = $MEMBER->previous_mps();
        if ($previous_people) {
            $sidebars[] = array(
                'type' => 'html',
                'content' => '<div class="block"><h4>Previous MPs in this constituency</h4><div class="blockbody"><ul>'
                    . $previous_people . '<li><em>Might show odd results due to bugs</em></li></ul></div></div>'
            );
        }
        $future_people = $MEMBER->future_mps();
        if ($future_people) {
            $sidebars[] = array(
                'type' => 'html',
                'content' => '<div class="block"><h4>Succeeding MPs in this constituency</h4><div class="blockbody"><ul>'
                    . $future_people . '<li><em>Might show odd results due to bugs</em></li></ul></div></div>'
            );
        }
    }

    if ($MEMBER->house(1)) {
        global $memcache;
        if (!$memcache) {
            $memcache = new Memcache;
            $memcache->connect('localhost', 11211);
        }
        $nearby = $memcache->get(OPTION_TWFY_DB_NAME . ':nearby_const:' . $MEMBER->person_id());
        if ($nearby === false) {
            $lat = null; $lon = null;
            $nearby = '';
            $geometry = _api_getGeometry_name($MEMBER->constituency());
            if (isset($geometry['centre_lat'])) {
                $lat = $geometry['centre_lat'];
                $lon = $geometry['centre_lon'];
            }
            if ($lat && $lon) {
                $nearby_consts = 0; #_api_getConstituencies_latitude($lat, $lon, 300); XXX Currently disabled
                if ($nearby_consts) {
                    $conlist = '<ul><!-- '.$lat.','.$lon.' -->';
                    for ($k=1; $k<=min(5, count($nearby_consts)-1); $k++) {
                        $name = $nearby_consts[$k]['name'];
                        $dist = $nearby_consts[$k]['distance'];
                        $conlist .= '<li><a href="' . WEBPATH . 'mp/?c=' . urlencode($name) . '">';
                        $conlist .= $nearby_consts[$k]['name'] . '</a>';
                        $dist_miles = round($dist / 1.609344, 0);
                        $conlist .= ' <small title="Centre to centre">(' . $dist_miles. ' miles)</small>';
                        $conlist .= '</li>';
                    }
                    $conlist .= '</ul>';
                    $nearby = $conlist;
                }
            }
            $memcache->set(OPTION_TWFY_DB_NAME . ':nearby_const:' . $MEMBER->person_id(), $nearby, 0, 3600);
        }
        if ($nearby) {
            $sidebars[] = array(
                'type' => 'html',
                'content' => '<div class="block"><h4>Nearby constituencies</h4><div class="blockbody">' . $nearby . ' </div></div>'
            );
        }
    }

    if (array_key_exists('office', $MEMBER->extra_info())) {
        $office = $MEMBER->extra_info();
        $office = $office['office'];
        $mins = '';
        foreach ($office as $row) {
            if ($row['to_date'] != '9999-12-31') {
                $mins .= '<li>' . prettify_office($row['position'], $row['dept']);
                       $mins .= ' (';
                if (!($row['source'] == 'chgpages/selctee' && $row['from_date'] == '2004-05-28')
                    && !($row['source'] == 'chgpages/privsec' && $row['from_date'] == '2004-05-13')) {
                    if ($row['source'] == 'chgpages/privsec' && $row['from_date'] == '2005-11-10')
                        $mins .= 'before ';
                    $mins .= format_date($row['from_date'],SHORTDATEFORMAT) . ' ';
                }
                $mins .= 'to ';
                if ($row['source'] == 'chgpages/privsec' && $row['to_date'] == '2005-11-10')
                    $mins .= 'before ';
                if ($row['source'] == 'chgpages/privsec' && $row['to_date'] == '2009-01-16')
                    $mins .= '<a href="/help/#pps_unknown">unknown</a>';
                else
                    $mins .= format_date($row['to_date'], SHORTDATEFORMAT);
                $mins .= ')</li>';
            }
        }
        if ($mins) {
            $sidebars[] = array('type'=>'html',
            'content' => '<div class="block"><h4>Other offices held in the past</h4><div class="blockbody"><ul>'.$mins.'</ul><p align="right"><a href="/help/#dates_wrong">Note about dates</a></div></div>');
        }
    }

/*    $body = technorati_pretty();
    if ($body) {
        $sidebars[] = array (
            'type' => 'html',
            'content' => '<div class="block"><h4>People talking about this MP</h4><div class="blockbody">' . $body . '</div></div>'
    );
    }
*/
    $sidebars[] = array('type'=>'html',
        'content' => '<div class="block"><h4>Note for journalists</h4>
<div class="blockbody"><p>Please feel free to use the data
on this page, but if you do you must cite TheyWorkForYou.com in the
body of your articles as the source of any analysis or
data you get off this site. If you ignore this, we might have to start
keeping these sorts of records on you...</p></div></div>'
    );
    $PAGE->stripe_end($sidebars);

} else {
    // Something went wrong
    $PAGE->page_start();
    $PAGE->stripe_start();
    if (isset($errors['pc'])) {
        $PAGE->error_message($errors['pc']);
    }
    $PAGE->postcode_form();
    $PAGE->stripe_end();
}


$PAGE->page_end();



function member_redirect(&$MEMBER, $code = 301) {
    // We come here after creating a MEMBER object by various methods.
    // Now we redirect to the canonical MP page, with a person_id.
    if ($MEMBER->person_id()) {
        $url = $MEMBER->url();
        $params = array();
        foreach ($_GET as $key => $value) {
            if (substr($key, 0, 4) == 'utm_' || $key == 'gclid')
                $params[] = "$key=$value";
        }
        if (count($params))
            $url .= '?' . join('&', $params);
        header('Location: ' . $url, true, $code );
        exit;
    }
}

function regional_list($pc, $area_type, $rep_type) {
    $constituencies = postcode_to_constituencies($pc);
    if ($constituencies == 'CONNECTION_TIMED_OUT') {
        $errors['pc'] = "Sorry, we couldn't check your postcode right now, as our postcode lookup server is under quite a lot of load.";
    } elseif (!$constituencies) {
        $errors['pc'] = 'Sorry, ' . htmlentities($pc) . ' isn\'t a known postcode';
    } elseif (!isset($constituencies[$area_type])) {
        $errors['pc'] = htmlentities($pc) . ' does not appear to be a valid postcode';
    }
    global $PAGE;
    $a = array_values($constituencies);
    $db = new ParlDB;
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
        $pid = $q->field($i, 'person_id');
        $name = $q->field($i, 'first_name') . ' ' . $q->field($i, 'last_name');
        $cons = $q->field($i, 'constituency');
        if ($house==1) {
            continue;
        } elseif ($house==3) {
            $mreg[] = $q->row($i);
        } elseif ($house==4) {
            if ($cons == $constituencies['SPC']) {
                $mcon = $q->row($i);
            } elseif ($cons == $constituencies['SPE']) {
                $mreg[] = $q->row($i);
            }
        } else {
            $PAGE->error_message('Odd result returned!' . $house);
            return;
        }
    }
    $PAGE->page_start();
    $PAGE->stripe_start();
    if ($rep_type == 'msp') {
        if ($current) {
            $out = '<p>You have one constituency MSP (Member of the Scottish Parliament) and multiple region MSPs.</p>';
            $out .= '<p>Your <strong>constituency MSP</strong> is <a href="/msp/?p=' . $mcon['person_id'] . '">';
            $out .= $mcon['first_name'] . ' ' . $mcon['last_name'] . '</a>, MSP for ' . $mcon['constituency'];
            $out .= '.</p> <p>Your <strong>' . $constituencies['SPE'] . ' region MSPs</strong> are:</p>';
        } else {
            $out = '<p>You had one constituency MSP (Member of the Scottish Parliament) and multiple region MSPs.</p>';
            $out .= '<p>Your <strong>constituency MSP</strong> was <a href="/msp/?p=' . $mcon['person_id'] . '">';
            $out .= $mcon['first_name'] . ' ' . $mcon['last_name'] . '</a>, MSP for ' . $mcon['constituency'];
            $out .= '.</p> <p>Your <strong>' . $constituencies['SPE'] . ' region MSPs</strong> were:</p>';
        }
    } else {
        if ($current) {
            $out = '<p>You have multiple MLAs (Members of the Legislative Assembly) who represent you in ' . $constituencies['NIE'] . '. They are:</p>';
        } else {
            $out = '<p>You had multiple MLAs (Members of the Legislative Assembly) who represented you in ' . $constituencies['NIE'] . '. They were:</p>';
        }
    }
    $out .= '<ul>';
    foreach($mreg as $reg) {
        $out .= '<li><a href="/' . $rep_type . '/?p=' . $reg['person_id'] . '">';
        $out .= $reg['first_name'] . ' ' . $reg['last_name'];
        $out .= '</a>';
    }
    $out .= '</ul>';
    echo $out;
    $PAGE->stripe_end();
    $PAGE->page_end();
}

function generate_member_links ($member) {
    // Receives its data from $MEMBER->display_links;
    // This returns HTML, rather than outputting it.
    // Why? Because we need this to be in the sidebar, and
    // we can't call the MEMBER object from the sidebar includes
    // to get the links. So we call this function from the mp
    // page and pass the HTML through to stripe_end(). Better than nothing.

    $links = $member->extra_info();

    // Bah, can't use $this->block_start() for this, as we're returning HTML...
    $html = '<div class="block">
            <h4>More useful links for this person</h4>
            <div class="blockbody">
            <ul>';

    if (isset($links['maiden_speech'])) {
        $maiden_speech = fix_gid_from_db($links['maiden_speech']);
        $html .= '<li><a href="' . WEBPATH . 'debate/?id=' . $maiden_speech . '">Maiden speech</a> (automated, may be wrong)</li>';
    }

    // BIOGRAPHY.
    global $THEUSER;
    if (isset($links['mp_website'])) {
        $html .= '<li><a href="' . $links['mp_website'] . '">'. $member->full_name().'\'s personal website</a>';
        if ($THEUSER->is_able_to('viewadminsection')) {
            $html .= ' [<a href="/admin/websites.php?editperson=' .$member->person_id() . '">Edit</a>]';
        }
        $html .= '</li>';
    } elseif ($THEUSER->is_able_to('viewadminsection')) {
         $html .= '<li>[<a href="/admin/websites.php?editperson=' . $member->person_id() . '">Add personal website</a>]</li>';
    }

    if (isset($links['twitter_username'])) {
        $html .= '<li><a href="http://twitter.com/' . $links['twitter_username'] . '">'. $member->full_name().'&rsquo;s Twitter feed</a></li>';
    }

    if (isset($links['sp_url'])) {
        $html .= '<li><a href="' . $links['sp_url'] . '">'. $member->full_name().'\'s page on the Scottish Parliament website</a></li>';
    }

    if (isset($links['guardian_biography'])) {
        $html .= '    <li><a href="' . $links['guardian_biography'] . '">Guardian profile</a></li>';
    }
    if (isset($links['wikipedia_url'])) {
        $html .= '    <li><a href="' . $links['wikipedia_url'] . '">Wikipedia page</a></li>';
    }

    if (isset($links['bbc_profile_url'])) {
        $html .= '      <li><a href="' . $links['bbc_profile_url'] . '">BBC News profile</a></li>';
    }

    if (isset($links['diocese_url'])) {
        $html .= '    <li><a href="' . $links['diocese_url'] . '">Diocese website</a></li>';
    }

    if ($member->house(1)) {
        $html .= '<li><a href="http://www.edms.org.uk/mps/' . $member->person_id() . '/">Early Day Motions signed by this MP</a> <small>(From edms.org.uk)</small></li>';
    }

    if (isset($links['journa_list_link'])) {
        $html .= '      <li><a href="' . $links['journa_list_link'] . '">Newspaper articles written by this MP</a> <small>(From Journalisted)</small></li>';
    }

    if (isset($links['guardian_election_results'])) {
        $html .= '      <li><a href="' . $links['guardian_election_results'] . '">Election results for ' . $member->constituency() . '</a> <small>(From The Guardian)</small></li>';
    }

    /*
    # BBC Catalogue is offline
    $bbc_name = urlencode($member->first_name()) . "%20" . urlencode($member->last_name());
    if ($member->member_id() == -1)
        $bbc_name = 'Queen Elizabeth';
    $html .= '      <li><a href="http://catalogue.bbc.co.uk/catalogue/infax/search/' . $bbc_name . '">TV/radio appearances</a> <small>(From BBC Programme Catalogue)</small></li>';
    */

    $html .= "      </ul>
                </div>
            </div> <!-- end block -->
";
    return $html;
}


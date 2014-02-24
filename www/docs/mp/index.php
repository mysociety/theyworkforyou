<?php

/*
 * index.php
 *
 * For displaying info about a person for a postcode or constituency.
 *
 * This page accepts either 'm' (a member_id), 'pid' (a person_id),
 * 'c' (a postcode or constituency), or 'n' (a name).
 *
 * First, we check to see if a person_id's been submitted.
 * If so, we display that person.
 *
 * Else, we check to see if a member_id's been submitted.
 * If so, we display that person.
 *
 * Otherwise, we then check to see if a postcode's been submitted.
 * If it's valid we put it in a cookie.
 *
 * If no postcode, we check to see if a constituency's been submitted.
 *
 * If neither has been submitted, we see if either the user is logged in
 * and has a postcode set or the user has a cookied postcode from a previous
 * search.
 *
 * If we have a valid constituency after all this, we display its MP.
 *
 * Either way, we print the forms.
 */

// Disable the old PAGE or NEWPAGE classes.
$new_style_template = TRUE;

// Include all the things this page needs.
include_once '../../includes/easyparliament/init.php';
include_once INCLUDESPATH . 'easyparliament/member.php';
include_once INCLUDESPATH . 'postcode.inc';
include_once INCLUDESPATH . 'technorati.php';
include_once '../api/api_getGeometry.php';
include_once '../api/api_getConstituencies.php';

// Set the PID, name and constituency.
$pid = get_http_var('pid') != '' ? get_http_var('pid') : get_http_var('p');
$name = strtolower(str_replace('_', ' ', get_http_var('n')));
$constituency = strtolower(str_replace('_', ' ', get_http_var('c')));

// Fix for names with non-ASCII characters
if ($name == 'sion simon') $name = 'si\xf4n simon';
if ($name == 'sian james') $name = 'si\xe2n james';
if ($name == 'lembit opik') $name = 'lembit \xf6pik';
if ($name == 'bairbre de brun') $name = 'bairbre de br\xfan';
if ($name == 'daithi mckay') $name = 'daith\xed mckay';
if ($name == 'caral ni chuilin') $name = 'car\xe1l n\xed chuil\xedn';
if ($name == 'caledon du pre') $name = 'caledon du pr\xe9';
if ($name == 'sean etchingham') $name = 'se\xe1n etchingham';
if ($name == 'john tinne') $name = 'john tinn\xe9';
if ($name == 'renee short') $name = 'ren\xe9e short';

// Fix for common misspellings, name changes etc
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
        }
    } else {
        $name = $name_fix[$name];
    }
}

// Fixes for Ynys Mon, and a Unicode URL
if ($constituency == 'ynys mon') $constituency = "ynys m\xf4n";
if (preg_match("#^ynys m\xc3\xb4n#i", $constituency)) {
    $constituency = "ynys m\xf4n";
}

// If this is a request for recent appearances, redirect to search results
if (get_http_var('recent')) {
    if ($THEUSER->postcode_is_set() && !$pid) {
        $MEMBER = new MEMBER(array('postcode' => $THEUSER->postcode(), 'house' => HOUSE_TYPE_COMMONS));
        if ($MEMBER->person_id()) {
            $pid = $MEMBER->person_id();
        }
    }
    if ($pid) {
        $URL = new URL('search');
        $URL->insert( array('pid'=>$pid, 'pop'=>1) );
        header('Location: http://' . DOMAIN . $URL->generate('none'));
        exit;
    }
}

/////////////////////////////////////////////////////////
// DETERMINE TYPE OF REPRESENTITIVE
if (get_http_var('peer')) $this_page = 'peer';
elseif (get_http_var('royal')) $this_page = 'royal';
elseif (get_http_var('mla')) $this_page = 'mla';
elseif (get_http_var('msp')) $this_page = 'msp';
else $this_page = 'mp';

/////////////////////////////////////////////////////////
// CANONICAL PERSON ID
if (is_numeric($pid))
{

    // Normal, plain, displaying an MP by person ID.
    $MEMBER = new MEMBER(array('person_id' => $pid));

    // If the member ID doesn't exist then the object won't have it set.
    if ($MEMBER->member_id)
    {
        // Ensure that we're actually at the current, correct and canonical URL for the person. If not, redirect.
        if (str_replace('/mp/', '/' . $this_page . '/', get_http_var('url')) !== urldecode($MEMBER->url(FALSE)))
        {
            member_redirect($MEMBER);
        }
    }
    else
    {
        $errors['pc'] = 'Sorry, that ID number wasn\'t recognised.';
    }
}

/////////////////////////////////////////////////////////
// MEMBER ID
elseif (is_numeric(get_http_var('m')))
{
    // Got a member id, redirect to the canonical MP page, with a person id.
    $MEMBER = new MEMBER(array('member_id' => get_http_var('m')));
    member_redirect($MEMBER);

}

/////////////////////////////////////////////////////////
// CHECK SUBMITTED POSTCODE

elseif (get_http_var('pc') != '')
{
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
            $MEMBER = new MEMBER(array('constituency' => $constituency, 'house' => HOUSE_TYPE_COMMONS));
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

}

/////////////////////////////////////////////////////////
// DOES THE USER HAVE A POSTCODE ALREADY SET (SCOTLAND)?
// (Either in their logged-in details or in a cookie from a previous search.)

elseif ($this_page == 'msp' && $THEUSER->postcode_is_set() && $name == '' && $constituency == '')
{
    $this_page = 'yourmsp';
    if (postcode_is_scottish($THEUSER->postcode())) {
        regional_list($THEUSER->postcode(), 'SPC', 'msp');
        exit;
    } else {
        $NEWPAGE->error_message('Your set postcode is not in Scotland.');
    }
}

/////////////////////////////////////////////////////////
// DOES THE USER HAVE A POSTCODE ALREADY SET (NI)?
// (Either in their logged-in details or in a cookie from a previous search.)
elseif ($this_page == 'mla' && $THEUSER->postcode_is_set() && $name == '' && $constituency == '')
{
    $this_page = 'yourmla';
    if (postcode_is_ni($THEUSER->postcode())) {
        regional_list($THEUSER->postcode(), 'NIE', 'mla');
        exit;
    } else {
        $NEWPAGE->error_message('Your set postcode is not in Northern Ireland.');
    }
}

/////////////////////////////////////////////////////////
// DOES THE USER HAVE A POSTCODE ALREADY SET (WESTMINISTER)?
// (Either in their logged-in details or in a cookie from a previous search.)
elseif ($THEUSER->postcode_is_set() && $name == '' && $constituency == '')
{
    $MEMBER = new MEMBER(array('postcode' => $THEUSER->postcode(), 'house' => HOUSE_TYPE_COMMONS));
    member_redirect($MEMBER, 302);
}

/////////////////////////////////////////////////////////
// NAME AND CONSTITUENCY
elseif ($name && $constituency)
{
    $MEMBER = new MEMBER(array('name'=>$name, 'constituency'=>$constituency));

    // If this person is not unique in name, don't redirect and instead wait to show list
    if (!is_array($MEMBER->person_id()))
    {
        twfy_debug ('MP', 'Redirecting for member found by name and constituency');
        member_redirect($MEMBER);
    }
}

/////////////////////////////////////////////////////////
// NAME ONLY
elseif ($name)
{
    $MEMBER = new MEMBER(array('name' => $name));

    // Edge case for Elizabeth II
    if ($name !== 'elizabeth the second') {

        // Only attempt further detection if this isn't the Queen.

        if (preg_match('#^(mr|mrs|ms)#', $name)) {
            member_redirect($MEMBER);
        }

        // If this person is not unique in name, don't redirect and instead wait to show list
        if (!is_array($MEMBER->person_id()))
        {
            twfy_debug ('MP', 'Redirecting for MP found by name only');
            member_redirect($MEMBER);
        }

    }
}

/////////////////////////////////////////////////////////
// CONSTITUENCY ONLY
elseif ($constituency)
{
    $MEMBER = new MEMBER(array('constituency' => $constituency, 'house' => HOUSE_TYPE_COMMONS));
    member_redirect($MEMBER);
}

/////////////////////////////////////////////////////////
// UNABLE TO IDENTIFY MP
else
{
    // No postcode, member_id or person_id to use.
    twfy_debug ('MP', "We don't have any way of telling what MP to display");
}

/////////////////////////////////////////////////////////
// DISPLAY A LIST OF REPRESENTATIVES

header('Cache-Control: max-age=900');

if (isset($MEMBER) && is_array($MEMBER->person_id())) {

    $cs = $MEMBER->constituency();
    $c = 0;
    foreach ($MEMBER->person_id() as $id) {
        $data['mps'][] = array(
                'url'  => WEBPATH . 'mp/?pid='.$id,
                'name' => ucwords(strtolower($name)) . ', ' . $cs[$c++]
            );
    }

    $MPSURL = new \URL('mps');

    $data['all_mps_url'] = $MPSURL->generate();

    // Send the output for rendering
    MySociety\TheyWorkForYou\Renderer::output('mp/list', $data);

/////////////////////////////////////////////////////////
// DISPLAY A REPRESENTATIVE

} elseif (isset($MEMBER) && $MEMBER->person_id()) {

    twfy_debug_timestamp("before load_extra_info");
    $MEMBER->load_extra_info(true);
    twfy_debug_timestamp("after load_extra_info");

    // Basic name, title and description
    $member_name = ucfirst($MEMBER->full_name());
    $title = $member_name;
    $desc = "Read $member_name's contributions to Parliament, including speeches and questions";

    // Enhance description if this is a current member
    if ($MEMBER->current_member_anywhere())
        $desc .= ', investigate their voting record, and get email alerts on their activity';

    // Enhance title if this is a member of the Commons
    if ($MEMBER->house(HOUSE_TYPE_COMMONS)) {
        if (!$MEMBER->current_member(1)) {
            $title .= ', former';
        }
        $title .= ' MP';
        if ($MEMBER->constituency()) $title .= ', ' . $MEMBER->constituency();
    }

    // Enhance title if this is a member of NIA
    if ($MEMBER->house(HOUSE_TYPE_NI)) {
        if ($MEMBER->house(HOUSE_TYPE_COMMONS) || $MEMBER->house(HOUSE_TYPE_LORDS)) {
            $desc = str_replace('Parliament', 'Parliament and the Northern Ireland Assembly', $desc);
        } else {
            $desc = str_replace('Parliament', 'the Northern Ireland Assembly', $desc);
        }
        if (!$MEMBER->current_member(HOUSE_TYPE_NI)) {
            $title .= ', former';
        }
        $title .= ' MLA';
        if ($MEMBER->constituency()) $title .= ', ' . $MEMBER->constituency();
    }

    // Enhance title if this is a member of Scottish Parliament
    if ($MEMBER->house(HOUSE_TYPE_SCOTLAND)) {
        if ($MEMBER->house(HOUSE_TYPE_COMMONS) || $MEMBER->house(HOUSE_TYPE_LORDS)) {
            $desc = str_replace('Parliament', 'the UK and Scottish Parliaments', $desc);
        } else {
            $desc = str_replace('Parliament', 'the Scottish Parliament', $desc);
        }
        $desc = str_replace(', and get email alerts on their activity', '', $desc);
        if (!$MEMBER->current_member(HOUSE_TYPE_SCOTLAND)) {
            $title .= ', former';
        }
        $title .= ' MSP, '.$MEMBER->constituency();
    }

    // Set page metadata
    $DATA->set_page_metadata($this_page, 'title', $title);
    $DATA->set_page_metadata($this_page, 'meta_description', $desc);

    // Prepare data for the template
    $data['image'] = person_image($MEMBER);
    $data['member_summary'] = person_summary_description($MEMBER);
    $data['full_name'] = $MEMBER->full_name();
    $data['rebellion_rate'] = person_rebellion_rate($MEMBER);

    /*

    $data['member_id'] = $MEMBER->member_id();
    $data['person_id'] = $MEMBER->person_id();
    $data['constituency'] = $MEMBER->constituency();
    $data['party'] = $MEMBER->party_text();
    $data['other_parties'] = $MEMBER->other_parties;
    $data['other_constituencies'] = $MEMBER->other_constituencies;
    $data['houses'] = $MEMBER->houses();
    $data['entered_house'] = $MEMBER->entered_house();
    $data['left_house'] = $MEMBER->left_house();
    $data['current_member'] = $MEMBER->current_member();
    $data['the_users_mp'] = $MEMBER->the_users_mp();
    $data['current_member_anywhere'] = $MEMBER->current_member_anywhere();
    $data['house_disp'] = $MEMBER->house_disp;

    */

    // Send the output for rendering
    MySociety\TheyWorkForYou\Renderer::output('member', $data);

}

/////////////////////////////////////////////////////////
// SUPPORTING FUNCTIONS

/**
 * Member Redirect
 *
 * Redirect to the canonical page for a member.
 */

function member_redirect (&$MEMBER, $code = 301) {
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

/**
 * Person Image
 *
 * Return the URL to an image of this person
 */

function person_image ($MEMBER) {
    $is_lord = in_array(HOUSE_TYPE_LORDS, $MEMBER->houses());
    if ($is_lord) {
        list($image,$sz) = find_rep_image($MEMBER->person_id(), false, 'lord');
    } else {
        list($image,$sz) = find_rep_image($MEMBER->person_id(), false, true);
    }
    return $image;
}

/**
 * Person Positions Summary
 *
 * Generate the summary of this person's held positions.
 */

function person_summary_description ($MEMBER) {
    if (in_array(HOUSE_TYPE_ROYAL, $MEMBER->houses())) { # Royal short-circuit
        return '<strong>Acceded on ' . $MEMBER->entered_house()[HOUSE_TYPE_ROYAL]['date_pretty']
            . '<br>Coronated on 2 June 1953</strong></li>';
    }
    $desc = '';
    foreach ($MEMBER->houses() as $house) {
        if ($house==HOUSE_TYPE_COMMONS && isset($MEMBER->entered_house()[HOUSE_TYPE_LORDS]))
            continue; # Same info is printed further down

        if (!$MEMBER->current_member()[$house]) $desc .= 'Former ';

        $party = $MEMBER->left_house()[$house]['party'];
        $party_br = '';
        if (preg_match('#^(.*?)\s*\((.*?)\)$#', $party, $m)) {
            $party_br = $m[2];
            $party = $m[1];
        }
        if ($party != 'unknown')
            $desc .= htmlentities($party);
        if ($party == 'Speaker' || $party == 'Deputy Speaker') {
            $desc .= ', and ';
            # XXX: Might go horribly wrong if something odd happens
            if ($party == 'Deputy Speaker') {
                $last = end($MEMBER->other_parties);
                $desc .= $last['from'] . ' ';
            }
        }
        if ($house==HOUSE_TYPE_COMMONS || $house==HOUSE_TYPE_NI || $house==HOUSE_TYPE_SCOTLAND) {
            $desc .= ' ';
            if ($house==HOUSE_TYPE_COMMONS) $desc .= '<abbr title="Member of Parliament">MP</abbr>';
            if ($house==HOUSE_TYPE_NI) $desc .= '<abbr title="Member of the Legislative Assembly">MLA</abbr>';
            if ($house==HOUSE_TYPE_SCOTLAND) $desc .= '<abbr title="Member of the Scottish Parliament">MSP</abbr>';
            if ($party_br) {
                $desc .= " ($party_br)";
            }
            $desc .= ' for ' . $MEMBER->left_house()[$house]['constituency'];
        }
        if ($house==HOUSE_TYPE_LORDS && $party != 'Bishop') $desc .= ' Peer';
        $desc .= ', ';
    }
    $desc = preg_replace('#, $#', '', $desc);
    return $desc;
}

/**
 * Is Person Dead
 *
 * @return boolean If the member is dead or not.
 */

function is_member_dead($member) {

    if (
        $member->left_house() && (
            ( in_array(HOUSE_TYPE_COMMONS, $member->left_house()) && $member->left_house()[HOUSE_TYPE_COMMONS]['reason'] && $member->left_house()[HOUSE_TYPE_COMMONS]['reason'] == 'Died' ) ||
            ( in_array(HOUSE_TYPE_LORDS, $member->left_house()) && $member->left_house()[HOUSE_TYPE_LORDS ]['reason'] && $member->left_house()[HOUSE_TYPE_LORDS]['reason'] == 'Died' ) ||
            ( in_array(HOUSE_TYPE_SCOTLAND, $member->left_house()) && $member->left_house()[HOUSE_TYPE_SCOTLAND ]['reason'] && $member->left_house()[HOUSE_TYPE_SCOTLAND]['reason'] == 'Died' ) ||
            ( in_array(HOUSE_TYPE_NI, $member->left_house()) && $member->left_house()[HOUSE_TYPE_NI ]['reason'] && $member->left_house()[HOUSE_TYPE_NI]['reason'] == 'Died' )
        )
    ) {
        return TRUE;
    } else {
        return FALSE;
    }

}

/**
 * Person Rebellion Rate
 *
 * How often has this person rebelled against their party?
 *
 * @return string A HTML summary of this person's rebellion rate.
 */

function person_rebellion_rate ($member) {

    // Rebellion string may be empty.
    $rebellion_string = '';

    /*

    if (isset($member->extra_info['public_whip_rebellions']) && $member->extra_info['public_whip_rebellions'] != 'n/a') {
        $displayed_stuff = 1;
        $rebels_term = 'rebels';
        if (is_member_dead($member)) {
            $rebels_term = 'rebelled';
        }

        $rebellion_string = '<a href="http://www.publicwhip.org.uk/mp.php?id=uk.org.publicwhip/member/' . $member->member_id() . '#divisions" title="See more details at Public Whip"><strong>' . htmlentities(ucfirst($extra_info['public_whip_rebel_description'])) . ' ' . $rebels_term . '</strong></a> against their party';

        if (isset($member->extra_info['public_whip_rebelrank'])) {
            if ($member->extra_info['public_whip_data_date'] == 'complete') {
                $rebellion_string .= ' in their last parliament';
            } else {
                $rebellion_string .= ' in this parliament';
            }
        }
    }

    */

    return $rebellion_string;

}

/**
 * Person Voting Record
 *
 * Return an array of this person's votes
 */

function person_voting_record ($member, $extra_info) {

    $displayed_stuff = 0;

    # ID, display string, MP only
    $policies = array(
        array(363, 'introducing <b>foundation hospitals</b>'),
        array(811, 'a <b>smoking ban</b>', true),
        array(826, 'equal <b>gay rights</b>'),
        array(984, 'replacing <b>Trident</b> with a new nuclear weapons system'),
        array(996, 'a <b>transparent Parliament</b>'),
        array(1027, 'a referendum on the UK\'s membership of the <b>EU</b>'),
        array(1030, 'laws to <b>stop climate change</b>'),
        array(1049, 'the <b>Iraq war</b>'),
        array(1050, 'the <b>hunting ban</b>'),
        array(1051, 'introducing <b>ID cards</b>'),
        array(1052, 'university <b>tuition fees</b>'),
        array(1053, 'Labour\'s <b title="Including voting to maintain them">anti-terrorism laws</b>', true),
        array(1065, 'more <b>EU integration</b>'),
        array(1071, 'allowing ministers to <b>intervene in inquests</b>'),
        array(1074, 'greater <b>autonomy for schools</b>'),
        array(1079, 'removing <b>hereditary peers</b> from the House of Lords'),
        array(1084, 'a more <a href="http://en.wikipedia.org/wiki/Proportional_representation">proportional system</a> for electing MPs'),
        array(1087, 'a <b>stricter asylum system</b>'),
        array(1110, 'increasing the <b>rate of VAT</b>'),
        array(1113, 'an <b>equal number of electors</b> per parliamentary constituency'),
        array(1124, 'automatic enrolment in <b>occupational pensions</b>'),
        array(1136, '<b>fewer MPs</b> in the House of Commons'),
        array(6670, 'a reduction in spending on <b>welfare benefits</b>'),
        array(6671, 'reducing central government <b>funding of local government</b>'),
        array(6672, 'reducing <b>housing benefit</b> for social tenants deemed to have excess bedrooms (which Labour describe as the "bedroom tax")'),
        array(6673, 'paying higher benefits over longer periods for those unable to work due to <b>illness or disability</b>'),
        array(6674, 'raising <b>welfare benefits</b> at least in line with prices'),
        array(6676, 'reforming the <b>NHS</b> so GPs buy services on behalf of their patients'),
        array(6677, 'restricting the provision of services to <b>private patients</b> by the NHS'),

        # Unfinished
        # array(856, "the <strong>changes to parliamentary scrutiny in the <a href=\"http://en.wikipedia.org/wiki/Legislative_and_Regulatory_Reform_Bill\">Legislative and Regulatory Reform Bill</a></strong>"),
        # array(1080, "government budgets and associated measures"),
        # array(1077, "equal extradition terms with the US"),
    );
    shuffle($policies);

    $joined = array(
        1079 => array(837, "a <strong>wholly elected</strong> House of Lords"),
        1049 => array(975, "an <strong>investigation</strong> into the Iraq war"),
        1052 => array(1132, 'raising England&rsquo;s undergraduate tuition fee cap to &pound;9,000 per year'),
        1124 => array(1109, "encouraging occupational pensions"),
    );

    $got_dream = '';
    foreach ($policies as $policy) {
        if (isset($policy[2]) && $policy[2] && !in_array(HOUSE_TYPE_COMMONS, $member['houses']))
            continue;
        $got_dream .= display_dream_comparison($extra_info, $member, $policy[0], $policy[1]);
        if (isset($joined[$policy[0]])) {
            $policy = $joined[$policy[0]];
            $got_dream .= display_dream_comparison($extra_info, $member, $policy[0], $policy[1]);
        }
    }

    if ($got_dream) {
        $displayed_stuff = 1;
        if (in_array(HOUSE_TYPE_COMMONS, $member['houses']) && $member['entered_house'][HOUSE_TYPE_COMMONS]['date'] > '2001-06-07') {
            $since = '';
        } elseif (!in_array(HOUSE_TYPE_COMMONS, $member['houses']) && in_array(HOUSE_TYPE_LORDS, $member['houses']) && $member['entered_house'][HOUSE_TYPE_LORDS]['date'] > '2001-06-07') {
            $since = '';
        } elseif ($member_has_died) {
            $since = '';
        } else {
            $since = ' since 2001';
        }
        # If not current MP/Lord, but current MLA/MSP, need to say voting record is when MP
        if (!$member['current_member'][HOUSE_TYPE_COMMONS] && !$member['current_member'][HOUSE_TYPE_LORDS] && ($member['current_member'][HOUSE_TYPE_SCOTLAND] || $member['current_member'][HOUSE_TYPE_NI])) {
            $since .= ' whilst an MP';
        }
?>

<h3>How <?=$member['full_name']?> voted on key issues<?=$since?></h3>
<ul class="no-bullet" id="dreamcomparisons">
<?=$got_dream ?>
</ul>
<?php
    }

    // Links to full record at Guardian and Public Whip
    $record = array();
    if (isset($extra_info['guardian_howtheyvoted'])) {
        $record[] = '<a href="' . $extra_info['guardian_howtheyvoted'] . '" title="At The Guardian">well-known issues</a> <small>(from the Guardian)</small>';
    }
    if ((isset($extra_info['public_whip_division_attendance']) && $extra_info['public_whip_division_attendance'] != 'n/a')
      || (isset($extra_info['Lpublic_whip_division_attendance']) && $extra_info['Lpublic_whip_division_attendance'] != 'n/a')) {
        $record[] = '<a href="http://www.publicwhip.org.uk/mp.php?id=uk.org.publicwhip/member/' . $member['member_id'] . '&amp;showall=yes#divisions" title="At Public Whip">their full record</a>';
    }

    if (count($record) > 0) {
        $displayed_stuff = 1;
        ?>
        <p class="morelink">More on <?php echo implode(' &amp; ', $record); ?></p>
<?php
    }


    if (!$displayed_stuff) {
        print '<p>No data to display yet.</p>';
    }
}

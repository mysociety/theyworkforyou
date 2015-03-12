<?php

include_once INCLUDESPATH . 'easyparliament/member.php';
include_once dirname(__FILE__) . '/api_getPerson.php';

function api_getMP_front() {
?>
<p><big>Fetch a particular MP.</big></p>

<h4>Arguments</h4>
<dl>

<dt>postcode (optional)</dt>
<dd>Fetch the MP for a particular postcode (either the current one, or the most recent one, depending upon the setting of the always_return variable.
<em>This will only return their current/ most recent entry in the database, look up by ID to get full history of a person.</em>
</dd>

<dt>constituency (optional)</dt>
<dd>The name of a constituency; we will try and work it out from whatever you give us. :)
<em>This will only return their current/ most recent entry in the database, look up by ID to get full history of a person.</em>
</dd>

<dt>id (optional)</dt>
<dd>If you know the person ID for the member you want (returned from getMPs or elsewhere), this will return data for that person.
This will return all database entries for this person, so will include previous elections, party changes, etc.
<!-- <em>Also returns select committee membership and ministerial positions, past and present.</em> --></dd>

<dt>always_return (optional)</dt>
<dd>For the postcode and constituency options, sets whether to always try and return an MP, even if the seat is currently vacant
(due to e.g. the death of an MP, or the period before an election when there are no MPs).</dd>
<!--
<dt>extra (optional)</dt>
<dd>Returns extra data in one or more categories, separated by commas.</dd>
-->
</dl>

<h4>Example Response</h4>
<pre>
[{
  "member_id" : "1368",
  "house" : "1",
  "first_name" : "Hywel",
  "last_name" : "Francis",
  "constituency" : "Aberavon",
  "party" : "Labour",
  "entered_house" : "2005-05-05",
  "left_house" : "9999-12-31",
  "entered_reason" : "general_election",
  "left_reason" : "still_in_office",
  "person_id" : "10900",
  "title" : "",
  "lastupdate" : "2008-02-26 22:25:20",
  "full_name" : "Hywel Francis",
  "url" : "/mp/hywel_francis/aberavon",
  "image" : "/images/mps/10900.jpg",
  "image_height" : 59,
  "image_width" : 49,
  "office" : [{
  "moffice_id" : "4949210",
  "dept" : "Liaison Committee",
  "position" : "",
  "from_date" : "2005-11-01",
  "to_date" : "9999-12-31",
  "person" : "10900",
  "source" : "chgpages/selctee"
},
{
  "moffice_id" : "4949211",
  "dept" : "Welsh Affairs Committee",
  "position" : "Chairman",
  "from_date" : "2005-11-01",
  "to_date" : "9999-12-31",
  "person" : "10900",
  "source" : "chgpages/selctee"
}]
},
{
  "member_id" : "900",
  "house" : "1",
  "first_name" : "Hywel",
  "last_name" : "Francis",
  "constituency" : "Aberavon",
  "party" : "Labour",
  "entered_house" : "2001-06-07",
  "left_house" : "2005-04-11",
  "entered_reason" : "general_election",
  "left_reason" : "general_election_standing",
  "person_id" : "10900",
  "title" : "Mr",
  "lastupdate" : "2009-05-19 23:24:40",
  "full_name" : "Mr Hywel Francis",
  "url" : "/mp/mr_hywel_francis/aberavon",
  "image" : "/images/mps/10900.jpg",
  "image_height" : 59,
  "image_width" : 49
}]
</pre>

<?php
}

function api_getMP_id($id) {
    $db = new ParlDB;
    $q = $db->query("select * from member
        where house=1 and person_id = :id
        order by left_house desc", array(
          ':id' => $id
          ));
    if ($q->rows()) {
        _api_getPerson_output($q);
    } else {
        api_error('Unknown person ID');
    }
}

function api_getMP_postcode($pc) {
    $pc = preg_replace('#[^a-z0-9 ]#i', '', $pc);
    if (\MySociety\TheyWorkForYou\Utility\Validation::validatePostcode($pc)) {
        $constituency = MySociety\TheyWorkForYou\Utility\Postcode::postcodeToConstituency($pc);
        if ($constituency == 'CONNECTION_TIMED_OUT') {
            api_error('Connection timed out');
        } elseif ($constituency) {
            $person = _api_getMP_constituency($constituency);
            $output = $person;
            api_output($output, isset($output['lastupdate']) ? strtotime($output['lastupdate']) : null);
        } else {
            api_error('Unknown postcode');
        }
    } else {
        api_error('Invalid postcode');
    }
}

function api_getMP_constituency($constituency) {
    $person = _api_getMP_constituency($constituency);
    if ($person) {
        $output = $person;
        api_output($output, strtotime($output['lastupdate']));
    } else {
        api_error('Unknown constituency, or no MP for that constituency');
    }
}

# Very similary to MEMBER's constituency_to_person_id
# Should all be abstracted properly :-/
function _api_getMP_constituency($constituency) {
    $db = new ParlDB;

    if ($constituency == '')
        return array();

    if ($constituency == 'Orkney ')
        $constituency = 'Orkney &amp; Shetland';

    $normalised = MySociety\TheyWorkForYou\Utility\Constituencies::normaliseConstituencyName($constituency);
    if ($normalised) $constituency = $normalised;

    $q = $db->query("SELECT * FROM member
        WHERE constituency = :constituency
        AND left_reason = 'still_in_office' AND house=1", array(
          ':constituency' => $constituency
          ));
    if ($q->rows > 0)
        return _api_getPerson_row($q->row(0), true);

    if (get_http_var('always_return')) {
        $q = $db->query("SELECT * FROM member
            WHERE house=1 AND constituency = :constituency
            ORDER BY left_house DESC LIMIT 1", array(
              ':constituency' => $constituency
              ));
        if ($q->rows > 0)
            return _api_getPerson_row($q->row(0), true);
    }

    return array();
}

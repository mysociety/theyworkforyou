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
  "given_name" : "Hywel",
  "family_name" : "Francis",
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
  "given_name" : "Hywel",
  "family_name" : "Francis",
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
    return api_getPerson_id($id, HOUSE_TYPE_COMMONS);
}

function api_getMP_postcode($pc) {
    api_getPerson_postcode($pc, HOUSE_TYPE_COMMONS);
}

function api_getMP_constituency($constituency) {
    api_getPerson_constituency($constituency, HOUSE_TYPE_COMMONS);
}

<?php

include_once dirname(__FILE__) . '/api_getMembers.php';

function api_getMLAs_front() {
    ?>
<p><big>Fetch a list of MLAs.</big></p>

<h4>Arguments</h4>
<dl>
<dt>date (optional)</dt>
<dd>Fetch the list of MLAs as it was on this date.</dd>
<dt>party (optional)</dt>
<dd>Fetch the list of MLAs from the given party.</dd>
<dt>search (optional)</dt>
<dd>Fetch the list of MLAs that match this search string in their name.</dd>
</dl>

<h4>Example Response, serialised PHP</h4>
<pre>a:646:{
    i:0; a:5:{
        s:9:"member_id"; s:4:"1368";
        s:9:"person_id"; s:5:"10900";
        s:4:"name"; s:13:"Hywel Francis";
        s:5:"party"; s:6:"Labour";
        s:12:"constituency"; s:8:"Aberavon";
    }
    i:1; ...
</pre>

<?php
}

/* See api_getMembers.php for these shared functions */
function api_getMLAs_party($s) {
    api_getMembers_party(3, $s);
}
function api_getMLAs_search($s) {
    api_getMembers_search(3, $s);
}
function api_getMLAs_date($date) {
    api_getMembers_date(3, $date);
}
function api_getMLAs($date = 'now()') {
    api_getMembers(3, $date);
}

?>

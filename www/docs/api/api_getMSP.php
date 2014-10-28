<?php

include_once dirname(__FILE__) . '/api_getPerson.php';

function api_getMSP_front() {
?>
<p><big>Fetch a particular MSP.</big></p>

<h4>Arguments</h4>
<dl>
<dt>postcode (optional)</dt>
<dd>Fetch the MSPs for a particular postcode.</dd>
<dt>constituency (optional)</dt>
<dd>The name of a constituency.</dd>
<dt>id (optional)</dt>
<dd>If you know the person ID for the member you want (returned from getMSPs or elsewhere), this will return data for that person.</dd>
</dl>

<h4>Example Response</h4>
<pre>&lt;twfy&gt;
  &lt;/twfy&gt;
</pre>

<?php
}

function api_getMSP_id($id) {
    $db = new \MySociety\TheyWorkForYou\ParlDb;
    $q = $db->query("select * from member
        where house=4 and person_id = :id
        order by left_house desc", array(
            ':id' => $id
            ));
    if ($q->rows()) {
        _api_getPerson_output($q);
    } else {
        api_error('Unknown person ID');
    }
}

function api_getMSP_postcode($pc) {
    $pc = preg_replace('#[^a-z0-9 ]#i', '', $pc);
    if (validate_postcode($pc)) {
        $constituencies = \MySociety\TheyWorkForYou\Utility\Postcode::postcodeToConstituencies($pc, true);
        if ($constituencies == 'CONNECTION_TIMED_OUT') {
            api_error('Connection timed out');
        } elseif (isset($constituencies['SPC'])) {
            _api_getMSP_constituency(array($constituencies['SPC'], $constituencies['SPE']));
        } elseif (isset($constituencies['WMC'])) {
            api_error('Non-Scottish postcode');
        } else {
            api_error('Unknown postcode');
        }
    } else {
        api_error('Invalid postcode');
    }
}

function api_getMSP_constituency($constituency) {
    $output = _api_getMSP_constituency(array($constituency));
    if (!$output)
        api_error('Unknown constituency, or no MSP for that constituency');
}

# Very similary to MEMBER's constituency_to_person_id
# Should all be abstracted properly :-/
function _api_getMSP_constituency($constituencies) {
    $db = new \MySociety\TheyWorkForYou\ParlDb;

    $cons = array();
    foreach ($constituencies as $constituency) {
        if ($constituency == '') continue;
        if ($constituency == 'Orkney ')
            $constituency = 'Orkney & Shetland';
        $cons[] = $constituency;
    }

    $cons_params = array();
    $params = array();
    foreach ($cons as $key => $constituency) {
        $cons_params[] = ':constituency' . $key;
        $params[':constituency' . $key] = $constituency;
    }

    $q = $db->query("SELECT * FROM member
        WHERE constituency in (" . join(",", $cons_params) . ")
        AND left_reason = 'still_in_office' AND house=4", $params);
    if ($q->rows > 0) {
        _api_getPerson_output($q);
        return true;
    }

    return false;
}

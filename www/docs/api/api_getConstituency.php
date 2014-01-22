<?

include_once INCLUDESPATH . '../../commonlib/phplib/mapit.php';

function api_getConstituency_front() {
?>
<p><big>Fetch a UK Parliament constituency.</big></p>

<h4>Arguments</h4>
<dl>
<dt>name</dt>
<dd>Fetch the data associated to the constituency with this name.</dd>
<dt>postcode</dt>
<dd>Fetch the constituency with associated information for a given postcode.</dd>
</dl>

<h4>Example Response</h4>
<pre>{ "name" : "Manchester, Gorton" }</pre>

<?
}

function api_getConstituency_postcode($pc) {
    $pc = preg_replace('#[^a-z0-9 ]#i', '', $pc);

    if (!validate_postcode($pc)) {
        api_error('Invalid postcode');
        return;
    }

    $constituency = postcode_to_constituency($pc, true);
    if ($constituency == 'CONNECTION_TIMED_OUT') {
        api_error('Connection timed out');
        return;
    }
    if (!$constituency) {
        api_error('Unknown postcode');
        return;
    }

    return _api_getConstituency_name($constituency);
}

function api_getConstituency_name($constituency) {
    $constituency = normalise_constituency_name($constituency);
    if (!$constituency) {
        api_error('Could not find anything with that name');
        return;
    }
    return _api_getConstituency_name($constituency);
}

function _api_getConstituency_name($constituency) {
    $db = new ParlDB;
    $q = $db->query("select constituency, data_key, data_value from consinfo
                     where constituency = '" . mysql_real_escape_string($constituency) . "'");
    if ($q->rows()) {
        for ($i=0; $i<$q->rows(); $i++) {
            $data_key = $q->field($i, 'data_key');
            $output[$data_key] = $q->field($i, 'data_value');
        }
        ksort($output);
    }
    $output['name'] = $constituency;
    api_output($output);

}

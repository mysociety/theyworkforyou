<?php

include_once dirname(__FILE__) . '/api_getMPsInfo.php';

function api_getMPInfo_front() {
    ?>
<p><big>Fetch extra information for a particular person.</big></p>

<h4>Arguments</h4>
<dl>
<dt>id</dt>
<dd>The person ID.</dd>
<dt>fields (optional)</dt>
<dd>Which fields you want to return, comma separated (leave blank for all).</dd>
</dl>

<?php
}

function api_getMPinfo_id($id) {
    if (!ctype_digit($id)) {
        api_error('Unknown person ID');
        return;
    }

    $output = _api_getMPsInfo_id($id);
    if ($output) {
        if ($output[0]) {
            api_output($output[0][$id], $output[1]);
        } else {
            api_error('Unknown field');
        }
    } else {
        api_error('Unknown person ID');
    }
}

function api_getMPinfo_fields($f) {
    api_error('You must supply a person ID');
}

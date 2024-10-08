<?php

function api_getQuota_front() {
    ?>
<p><big>Returns your current API usage and quota limit.</big></p>

<h4>Arguments</h4>
<p>None.</p>

<h4>Example Response</h4>
<pre>{
    "quota": {
        "limit": 1000,
        "current": 584
    }
}</pre>

<?php
}

function api_getQuota() {
    api_output(['error' => "getQuota is implemented by redis, not here."]);
}

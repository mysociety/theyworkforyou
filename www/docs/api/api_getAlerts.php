<?php

function api_getAlerts_start_date($start_date) {
    $args =  ['start_date' => $start_date, 'end_date' => get_http_var('end_date')];
    $alert = new ALERT();
    $data = $alert->fetch_between($confirmed = 1, $deleted = 0, $args['start_date'], $args['end_date']);
    api_output($data);
}

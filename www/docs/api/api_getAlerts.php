<?php

function api_getAlerts_start_date($start_date) {
  $args = array ('start_date' => $start_date, 'end_date' => get_http_var('end_date'));
  $alert = new \MySociety\TheyWorkForYou\Alert();
  $data = $alert->fetch_between($confirmed=1, $deleted=0, $args['start_date'], $args['end_date']);
  api_output($data);
}

?>

<?php

include_once '../../includes/easyparliament/init.php';
include_once INCLUDESPATH."easyparliament/people.php";

if (get_http_var('msp')) {
    $type = 'msps';
    $template = 'msp_index';
} elseif (get_http_var('mla')) {
    $type = 'mlas';
    $template = 'mla_index';
} elseif (get_http_var('peer')) {
    $type = 'peers';
    $template = 'peer_index';
} else {
    $type = 'mps';
    $template = 'mp_index';
}
    $template = 'mp_index';


$people = new MySociety\TheyWorkForYou\People($type);
$args = $people->getArgs();
$people->setMetaData($args);
$data = $people->getData($args);
if ( isset($args['f']) && $args['f'] == 'csv' ) {
    $people->sendAsCSV($data);
} else {
    MySociety\TheyWorkForYou\Renderer::output("people/$template", $data);
}

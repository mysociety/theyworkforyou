<?php

include_once '../../includes/easyparliament/init.php';
include_once INCLUDESPATH."easyparliament/people.php";

if (get_http_var('msp')) {
    $type = 'MSPs';
} elseif (get_http_var('mla')) {
    $type = 'MLAs';
} elseif (get_http_var('peer')) {
    $type = 'Peers';
} else {
    $type = 'MPs';
}

$class = "MySociety\TheyWorkForYou\People\\$type";
$people = new $class();
$args = $people->getArgs();
$people->setMetaData($args);
$data = $people->getData($args);
if ( isset($args['f']) && $args['f'] == 'csv' ) {
    $people->sendAsCSV($data);
} else {
    MySociety\TheyWorkForYou\Renderer::output("people/index", $data);
}

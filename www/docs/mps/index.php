<?php

include_once '../../includes/easyparliament/init.php';

switch (get_http_var('representative_type')) {
    case 'peer':
        $people = new MySociety\TheyWorkForYou\People\Peers();
        break;
    case 'mla':
        $people = new MySociety\TheyWorkForYou\People\MLAs();
        break;
    case 'msp':
        $people = new MySociety\TheyWorkForYou\People\MSPs();
        break;
    case 'ms':
        $people = new MySociety\TheyWorkForYou\People\MSs();
        break;
    case 'london-assembly-member':
        $people = new MySociety\TheyWorkForYou\People\LondonAssemblyMembers();
        break;
    default:
        $people = new MySociety\TheyWorkForYou\People\MPs();
        break;
}

$args = $people->getArgs();
$people->setMetaData($args);
$data = $people->getData($args);
if (isset($args['f']) && $args['f'] == 'csv') {
    $people->sendAsCSV($data);
} else {
    MySociety\TheyWorkForYou\Renderer::output("people/index", $data);
}

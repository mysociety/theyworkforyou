<?php

$this_page = "highlighted_2024";

include_once '../../includes/easyparliament/init.php';


$json_source = RAWDATA . "scrapedjson/universal_format_regmem/misc/highlighted_interests.json";

$content = file_get_contents($json_source);
$register = MySociety\TheyWorkForYou\DataClass\Regmem\Register::fromJson($content);

MySociety\TheyWorkForYou\Renderer::output('misc/highlighted_2024', ["register" => $register]);

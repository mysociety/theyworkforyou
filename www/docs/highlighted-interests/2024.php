<?php

$this_page = "highlighted_2024";

include_once '../../includes/easyparliament/init.php';

$register = MySociety\TheyWorkForYou\DataClass\Regmem\Register::getMisc("highlighted_interests.json");


$category_ids = $array = [
    '2'   => 'Donations and other support (including loans) for activities as an MP',
    '1.2' => 'Employment and earnings - Ongoing paid employment',
    '1.1' => 'Employment and earnings - Ad hoc payments',
    '4'   => 'Visits outside the UK',
    '3'   => 'Gifts, benefits and hospitality from UK sources',
    '8'   => 'Miscellaneous',
];

MySociety\TheyWorkForYou\Renderer::output('misc/highlighted_2024', ["register" => $register, "category_ids" => $category_ids]);

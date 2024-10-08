<?php

include_once INCLUDESPATH . '../../commonlib/phplib/mapit.php';
include_once dirname(__FILE__) . '/api_getGeometry.php';

function api_getBoundary_front() {
    ?>
<p><big>Returns KML file for a UK Parliament constituency.</big></p>

<p>Returns the bounding polygon of the constituency, in KML format (see <a
href="https://mapit.mysociety.org/">mapit.mysociety.org</a> for other formats,
past constituency boundaries, and so on).</p>

<h4>Arguments</h4>
<dl>
<dt>name</dt>
<dd>Name of the constituency.
</dl>

<?php
}

function api_getBoundary_name($name) {
    $name = MySociety\TheyWorkForYou\Utility\Constituencies::normaliseConstituencyName($name);
    if (!$name) {
        api_error('Name not recognised');
        return;
    }

    $areas_info = _api_cacheCheck('areas', 'WMC');
    $id = null;
    foreach ($areas_info as $k => $v) {
        if (MySociety\TheyWorkForYou\Utility\Constituencies::normaliseConstituencyName($v['name']) == $name) {
            $id = $k;
        }
    }
    if (!$id) {
        api_error('No data found for name');
        return;
    }
    header("Location: https://mapit.mysociety.org/area/4326/$id.kml");
    exit;
}

<?php

include_once INCLUDESPATH . '../../commonlib/phplib/mapit.php';

function api_getGeometry_front() {
?>
<p><big>Returns geometry information for constituencies.</big></p>

<p>This currently includes, for Great Britain, the latitude and longitude of
the centre point of the bounding box of the constituency, its area in square
metres, the bounding box itself and the number of parts in the polygon that
makes up the constituency.  For Northern Ireland, as we don't have any better
data, it only returns an approximate (estimated by eye) latitude and longitude
for the constituency's centroid.
</p>

<h4>Arguments</h4>
<dl>
<dt>name</dt>
<dd>Name of the constituency.
</dl>

<h4>Example Response</h4>
<pre>
&lt;twfy&gt;
    &lt;centre_lat&gt;52.204105461821&lt;/centre_lat&gt;
    &lt;centre_lon&gt;0.12659823548615&lt;/centre_lon&gt;
    &lt;area&gt;28775860&lt;/area&gt;
    &lt;min_lat&gt;52.171543612943&lt;/min_lat&gt;
    &lt;max_lat&gt;52.236635567414&lt;/max_lat&gt;
    &lt;min_lon&gt;0.066543287124126&lt;/min_lon&gt;
    &lt;max_lon&gt;0.18674084465674&lt;/max_lon&gt;
    &lt;parts&gt;1&lt;/parts&gt;
&lt;/twfy&gt;
</pre>
<?php
}

function api_getGeometry_name($name) {
    $out = _api_getGeometry_name($name);
    if ($out) api_output($out);
    else api_error('Name not recognised');
}

function _api_getGeometry_name($name) {
    if (!defined('OPTION_MAPIT_URL') || !OPTION_MAPIT_URL)
        return null;

    $name = normalise_constituency_name($name);

    $areas_info = _api_cacheCheck('areas', 'WMC');
    $ni_geometry = _api_ni_centroids();
    foreach ($areas_info as $area_id => $area) {
        if ($name == $area['name']) {
            if (isset($ni_geometry[$area_id])) {
                $out = $ni_geometry[$area_id];
            } else {
                $out = mapit_call('area/geometry', $area_id);
            }
            $out['name'] = $name;

            return $out;
        }
    }

    return null;
}

function _api_cacheCheck($fn, $arg='') {
    $cache = INCLUDESPATH . '../docs/api/cache/' . $fn;
    if (is_file($cache))
        return unserialize(file_get_contents($cache));
    $out = mapit_call($fn, $arg);
    $fp = fopen($cache, 'w');
    if ($fp) {
        fwrite($fp, serialize($out));
        fclose($fp);
    }

    return $out;
}

function _api_ni_centroids() {
    return array(
        # East Londonderry
        66129 => array('centre_lat' => 54.980766, 'centre_lon' => -6.904907 ),
        14276 => array('centre_lat' => 54.980766, 'centre_lon' => -6.904907 ),
        # Foyle
        66131 => array('centre_lat' => 54.933453, 'centre_lon' => -7.267456 ),
        14273 => array('centre_lat' => 54.933453, 'centre_lon' => -7.267456 ),
        # West Tyrone
        66141 => array('centre_lat' => 54.619797, 'centre_lon' => -7.410278 ),
        14292 => array('centre_lat' => 54.619797, 'centre_lon' => -7.410278 ),
        # Fermanagh &amp; South Tyrone
        14296 => array('centre_lat' => 54.354958, 'centre_lon' => -7.443237 ),
        66130 => array('centre_lat' => 54.354958, 'centre_lon' => -7.443237 ),
        # Newry &amp; Armagh
        14303 => array('centre_lat' => 54.297295, 'centre_lon' => -6.613770 ),
        66134 => array('centre_lat' => 54.297295, 'centre_lon' => -6.613770 ),
        # Upper Bann
        14300 => array('centre_lat' => 54.399750, 'centre_lon' => -6.350098 ),
        66140 => array('centre_lat' => 54.399750, 'centre_lon' => -6.350098 ),
        # South Down
        14306 => array('centre_lat' => 54.213860, 'centre_lon' => -6.152344 ),
        66138 => array('centre_lat' => 54.213860, 'centre_lon' => -6.152344 ),
        # Lagan Valley
        14309 => array('centre_lat' => 54.441296, 'centre_lon' => -6.108398 ),
        66132 => array('centre_lat' => 54.441296, 'centre_lon' => -6.108398 ),
        # Strangford
        14312 => array('centre_lat' => 54.514706, 'centre_lon' => -5.751343 ),
        66139 => array('centre_lat' => 54.514706, 'centre_lon' => -5.751343 ),
        # North Down
        14325 => array('centre_lat' => 54.651592, 'centre_lon' => -5.718384 ),
        66136 => array('centre_lat' => 54.651592, 'centre_lon' => -5.718384 ),
        # South Antrim
        14318 => array('centre_lat' => 54.699234, 'centre_lon' => -6.102905 ),
        66137 => array('centre_lat' => 54.699234, 'centre_lon' => -6.102905 ),
        # East Antrim
        66128 => array('centre_lat' => 54.832336, 'centre_lon' => -5.883179 ),
        14284 => array('centre_lat' => 54.832336, 'centre_lon' => -5.883179 ),
        # North Antrim
        66135 => array('centre_lat' => 54.993374, 'centre_lon' => -6.328125 ),
        14280 => array('centre_lat' => 54.993374, 'centre_lon' => -6.328125 ),
        # Mid Ulster
        66133 => array('centre_lat' => 54.721447, 'centre_lon' => -6.795044 ),
        14288 => array('centre_lat' => 54.721447, 'centre_lon' => -6.795044 ),
        # Belfast North
        14321 => array('centre_lat' => 54.618607, 'centre_lon' => -5.917511 ),
        66125 => array('centre_lat' => 54.618607, 'centre_lon' => -5.917511 ),
        # Belfast East
        14329 => array('centre_lat' => 54.598324, 'centre_lon' => -5.892792 ),
        66124 => array('centre_lat' => 54.598324, 'centre_lon' => -5.892792 ),
        # Belfast South
        14331 => array('centre_lat' => 54.582409, 'centre_lon' => -5.925064 ),
        66126 => array('centre_lat' => 54.582409, 'centre_lon' => -5.925064 ),
        # Belfast West
        14315 => array('centre_lat' => 54.606277, 'centre_lon' => -5.956650 ),
        66127 => array('centre_lat' => 54.606277, 'centre_lon' => -5.956650 ),
    );
}

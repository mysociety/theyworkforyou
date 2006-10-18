<?

include_once '../../../../phplib/mapit.php';

function api_getGeometry_front() {
?>
<p><big>Returns geometry information for constituencies.</big></p>

<p>This currently includes the latitude and longitude of the centre point of
the bounding box of the constituency, its area in square metres, the bounding
box itself and the number of parts in the polygon that makes up the constituency.</p>

<h4>Arguments</h4>
<dl>
<dt>name (optional)</dt>
<dd>Limit returned data to constituency <kbd>name</kbd>. If you wish to fetch
geometry for a number of constituencies, better to just call getGeometry with
no arguments and work through the list.
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
<?	
}

function api_getGeometry() {
	$geometry = _api_getGeometry();
	api_output($geometry);
}

function api_getGeometry_name($name) {
	$out = _api_getGeometry_name($name);
	if ($out) api_output($out);
	else api_error('Name not recognised');
}
function _api_getGeometry_name($name) {
	$geometry = _api_getGeometry();
	$name = html_entity_decode(normalise_constituency_name($name)); # XXX
	$out = array();
	foreach ($geometry['data'] as $n => $data) {
		if ($n == $name)
			return $data;
	}
	return null;
}

function _api_cacheCheck($fn, $arg) {
	$cache = INCLUDESPATH . '../docs/api/cache/' . $fn;
	if (is_file($cache))
		return unserialize(file_get_contents($cache));
	$out = call_user_func($fn, $arg);
	$fp = fopen($cache, 'w');
	if ($fp) {
		fwrite($fp, serialize($out));
		fclose($fp);
	}
	return $out;
}

function _api_getGeometry() {
	$areas = _api_cacheCheck('mapit_get_areas_by_type', 'WMC');
	$areas_geometry = _api_cacheCheck('mapit_get_voting_areas_geometry', $areas);
	$areas_info = _api_cacheCheck('mapit_get_voting_areas_info', $areas);
	$areas_out = array('date' => date('Y-m-d'), 'data' => array());
	$names = array();
	foreach (array_keys($areas_info) as $area_id) {
		$names[$area_id] = $areas_info[$area_id]['name'];
	}
	$names = normalise_constituency_names($names);
	foreach (array_keys($areas_info) as $area_id) {
		$out = array();
		$name = $names[$area_id];
		if (count($areas_geometry[$area_id])) {
			$out['name'] = $name;
			$out['centre_lat'] = $areas_geometry[$area_id]['centre_lat'];
			$out['centre_lon'] = $areas_geometry[$area_id]['centre_lon'];
			$out['area'] = $areas_geometry[$area_id]['area'];
			$out['min_lat'] = $areas_geometry[$area_id]['min_lat'];
			$out['max_lat'] = $areas_geometry[$area_id]['max_lat'];
			$out['min_lon'] = $areas_geometry[$area_id]['min_lon'];
			$out['max_lon'] = $areas_geometry[$area_id]['max_lon'];
			$out['parts'] = $areas_geometry[$area_id]['parts'];
		}
		$areas_out['data'][$name] = $out;
	}
	return $areas_out;
}

?>

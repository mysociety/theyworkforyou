<?

include_once '../../../../phplib/mapit.php';

function api_getGeometry_front() {
?>
<p><big>Returns geometry information for constituencies.</big></p>

<p>This currently includes the latitude and longitude of the centre point of
the bounding box of the constituency, and its area in square metres.</p>

<h4>Arguments</h4>
<dl>
<dt>name (optional)</dt>
<dd>Limit returned data to constituency <kbd>name</kbd>. If you wish to fetch
geometry for a number of constituencies, better to just call getGeometry with
no arguments and work through the list.
</dl>

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
			$out['centre_lat'] = $areas_geometry[$area_id]['centre_lat'];
			$out['centre_lon'] = $areas_geometry[$area_id]['centre_lon'];
			$out['area'] = $areas_geometry[$area_id]['area'];
		}
		$areas_out['data'][$name] = $out;
	}
	return $areas_out;
}

?>

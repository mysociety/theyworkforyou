<?

include_once '../../../../phplib/mapit.php';
include_once 'api_getConstituencies.php';

function api_getGeometry_front() {
?>
<p><big>Returns geometry information for constituencies.</big></p>

<p>This currently includes the latitude and longitude of the centre point of
the bounding box of the constituency, and its area.</p>

<h4>Arguments</h4>
<dl>
<dt>name (optional)</dt>
<dd>Limit returned data to constituencies matching <kbd>name</kbd>.
</dl>

<?	
}

function api_getGeometry() {
	$geometry = _api_getGeometry();
	api_output($geometry);
}

function api_getGeometry_name($name) {
	$out = _api_getGeometry_name($name);
	api_output($out);
}
function _api_getGeometry_name($name) {
	$geometry = _api_getGeometry();
	$consts = _api_getConstituencies_search($name);
	$names = array();
	foreach ($consts as $data) {
		$names[] = $data['name'];
	}
	$out = array();
	foreach ($geometry['data'] as $data) {
		if (in_array($data['name'], $names)) {
			$out[] = $data;
		}
	}
	return $out;
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
		$out['name'] = $names[$area_id];
		if (count($areas_geometry[$area_id])) {
			$out['centre_lat'] = $areas_geometry[$area_id]['centre_lat'];
			$out['centre_lon'] = $areas_geometry[$area_id]['centre_lon'];
			$out['area'] = $areas_geometry[$area_id]['area'];
		}
		$areas_out['data'][] = $out;
	}
	return $areas_out;
}

?>

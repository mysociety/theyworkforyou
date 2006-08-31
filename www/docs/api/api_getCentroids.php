<?

include_once '../../../../phplib/mapit.php';

function api_getCentroids_front() {
?>
<p><big>Returns centroid latitude and longitudes for all constituencies.</big></p>

<h4>Arguments</h4>
<p>None</p>

<?	
}

function api_getCentroids() {
	$centroids = _api_getCentroids();
	api_output($centroids);
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

function _api_getCentroids() {
	$areas = _api_cacheCheck('mapit_get_areas_by_type', 'WMC');
	$areas_geometry = _api_cacheCheck('mapit_get_voting_areas_geometry', $areas);
	$areas_info = _api_cacheCheck('mapit_get_voting_areas_info', $areas);
	$areas_out = array('date' => date('Y-m-d'));
	foreach (array_keys($areas_info) as $area_id) {
		$areas_out[$area_id] = array();
		$areas_out[$area_id]['name'] = $areas_info[$area_id]['name'];
		if (count($areas_geometry[$area_id])) {
			$areas_out[$area_id]['centre_lat'] = $areas_geometry[$area_id]['centre_lat'];
			$areas_out[$area_id]['centre_lon'] = $areas_geometry[$area_id]['centre_lon'];
		}
	}
	return $areas_out;
}

?>

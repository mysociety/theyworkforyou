<?

include_once 'api_getGeometry.php';

function api_getConstituencies_front() {
?>
<p><big>Fetch a list of constituencies.</big></p>

<h4>Arguments</h4>
<dl>
<dt>date (optional)</dt>
<dd>Fetch the list of constituencies as it was on this date.</dd>
<dt>search (optional)</dt>
<dd>Fetch the list of constituencies that match this search string.</dd>
<dt>latitude, longitude, distance (optional, all together)</dt>
<dd>Fetches (vaguely) constituency or constituencies within <kbd>distance</kbd> km of (<kbd>latitude</kbd>,<kbd>longitude</kbd>)</dd>
</dl>

<h4>Example Response</h4>
<pre>[
	{ name : "Aberavon" },
	{ name : "Aldershot" },
	{ name : "Aldridge-Brownhills" },
	...
]</pre>

<?	
}

function api_getConstituencies_search($s) {
	$output = _api_getConstituencies_search($s);
	api_output($output);
}
function _api_getConstituencies_search($s) {
	$db = new ParlDB;
	$q = $db->query('select c_main.name from constituency, constituency as c_main
		where constituency.cons_id = c_main.cons_id
		and c_main.main_name and constituency.name like "%' . mysql_escape_string($s) .
		'%" and constituency.from_date <= date(now())
		and date(now()) <= constituency.to_date');
	$output = array();
	$done = array();
	for ($i=0; $i<$q->rows(); $i++) {
		$name = html_entity_decode($q->field($i, 'name'));
		if (!in_array($name, $done)) {
			$output[] = array(
				# 'id' => $q->field($i, 'cons_id'),
				'name' => $name
			);
			$done[] = $name;
		}
	}
	return $output;
}

function api_getConstituencies_date($date) {
	if ($date = parse_date($date)) {
		api_getConstituencies('"' . $date['iso'] . '"');
	} else {
		api_error('Invalid date format');
	}
}

function api_getConstituencies($date = 'now()') {
	$db = new ParlDB;
	$q = $db->query('select cons_id, name from constituency
		where main_name and from_date <= date('.$date.') and date('.$date.') <= to_date');
	$output = array();
	for ($i=0; $i<$q->rows(); $i++) {
		$output[] = array(
			# 'id' => $q->field($i, 'cons_id'),
			'name' => html_entity_decode($q->field($i, 'name'))
		);
	}
	api_output($output);
}

/* R_e
 * Radius of the earth, in km. This is something like 6372.8 km:
 *  http://en.wikipedia.org/wiki/Earth_radius
 */
define('R_e', 6372.8);

function api_getConstituencies_latitude($lat) {
	$lon = get_http_var('longitude') + 0;
	$d = get_http_var('distance') + 0;
	if (!$lat) {
		api_error('You must supply a latitude and longitude');
		return;
	}
	$out = _api_getConstituencies_latitude($lat, $lon, $d);
	api_output($out);
}

function _api_getConstituencies_latitude($lat, $lon, $d) {
	$geometry = _api_getGeometry();
	$out = array();
	foreach ($geometry['data'] as $name => $data) {
		if (!isset($data['centre_lat']) || !isset($data['centre_lon'])) continue;
		$distance = R_e * acos(
			sin(deg2rad($lat)) * sin(deg2rad($data['centre_lat']))
			+ cos(deg2rad($lat)) * cos(deg2rad($data['centre_lat']))
			* cos(deg2rad($lon - $data['centre_lon'])));
		if (deg2rad($data['centre_lat']) > deg2rad($lat) - ($d / R_e)
			&& deg2rad($data['centre_lat']) < deg2rad($lat) + ($d / R_e)
			&& (abs(deg2rad($lat)) + ($d / R_e) > M_PI_2 # case where search pt is near pole
				|| _api_angle_between(deg2rad($data['centre_lon']), deg2rad($lon))
					< $d / (R_e * cos(deg2rad($lat + $d / R_e))))
			&& $distance < $d) {
				$out[] = array_merge($data,
					array('distance' => $distance, 'name' => $name)
				);
		}
	}
	usort($out, create_function('$a,$b', "
		if (\$a['distance'] > \$b['distance']) return 1;
		if (\$a['distance'] < \$b['distance']) return -1;
		return 0;"));
	return $out;
}

function api_getConstituencies_longitude($lon) {
	api_error('You must supply a latitude');
}

function api_getConstituencies_distance($d) {
	api_error('You must supply a latitude and longitude');
}

/* _api_angle_between A1 A2
 * Given two angles A1 and A2 on a circle expressed in radians, return the
 * smallest angle between them.
 */
function _api_angle_between($a1, $a2) {
	if (abs($a1 - $a2) > M_PI) return 2*M_PI - abs($a1 - $a2);
	return abs($a1 - $a2);
}

?>

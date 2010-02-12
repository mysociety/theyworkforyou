<?

include_once '../../../commonlib/phplib/mapit.php';

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
<dt>name (optional)</dt>
<dd>Limit returned data to constituency <kbd>name</kbd>. If you wish to fetch
geometry for a number of constituencies, better to just call getGeometry with
no arguments and work through the list.
<dt>future (optional)</dt>
<dd>If set to anything, return details for boundaries at the 2010 general election.
This is a temporary feature. The area column is empty.</dd>
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

/* Due to how the API functions are called, this is called if future is given
 * and name isn't. Treat as normal. */
function api_getGeometry_future($future) {
	api_getGeometry();
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
	if (!get_http_var('future')) {
	    $name = normalise_constituency_name($name);
	}
	$out = array();
	foreach ($geometry['data'] as $n => $data) {
		if ($n == $name)
			return $data;
	}
	return null;
}

function _api_cacheCheck($fn, $arg='') {
	$cache = INCLUDESPATH . '../docs/api/cache/' . $fn;
	if (is_array($arg)) $cache .= '_' . count($arg);
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
	if (!defined('OPTION_MAPIT_URL') || !OPTION_MAPIT_URL)
		return array('data'=>array());

	if (get_http_var('future')) {
		$areas = _api_cacheCheck('_api_futureAreaIds');
	} else {
		$areas = _api_cacheCheck('mapit_get_areas_by_type', 'WMC');
	}
	$areas_geometry = _api_cacheCheck('mapit_get_voting_areas_geometry', $areas);
    $ni_geometry = _api_ni_centroids();
	$areas_info = _api_cacheCheck('mapit_get_voting_areas_info', $areas);
	$areas_out = array('date' => date('Y-m-d'), 'data' => array());
	$names = array();
	foreach (array_keys($areas_info) as $area_id) {
		$names[$area_id] = $areas_info[$area_id]['name'];
	}
	if (!get_http_var('future')) {
	    $names = normalise_constituency_names($names);
    }
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
		} elseif ($ni_geometry[$area_id]) {
			$out['name'] = $name;
			$out['centre_lat'] = $ni_geometry[$area_id]['centre_lat'];
			$out['centre_lon'] = $ni_geometry[$area_id]['centre_lon'];
        }
		$areas_out['data'][$name] = $out;
	}
	return $areas_out;
}

function _api_futureAreaIds() {
    return array(
        14398, 14399, 14400, 14401, 14402, 14403, 14404, 14405, 14406, 14407, 14408, 14409, 14410,
        14411, 14412, 14413, 14414, 14415, 14416, 14417, 14418, 14419, 14420, 14421, 14422, 14423,
        14424, 14425, 14426, 14427, 14428, 14429, 14430, 14431, 14432, 14433, 14434, 14435, 14436,
        14437, 14438, 14439, 14440, 14441, 14442, 14443, 14444, 14445, 14446, 14447, 14448, 14449,
        14450, 14451, 14452, 14453, 14454, 14455, 14456, 65549, 65550, 65551, 65552, 65553, 65554,
        65555, 65556, 65557, 65558, 65559, 65560, 65561, 65562, 65563, 65564, 65565, 65566, 65567,
        65568, 65569, 65570, 65571, 65572, 65573, 65574, 65575, 65576, 65577, 65578, 65579, 65580,
        65581, 65582, 65583, 65584, 65585, 65586, 65587, 65588, 65589, 65590, 65591, 65592, 65593,
        65594, 65595, 65596, 65597, 65598, 65599, 65600, 65601, 65602, 65603, 65604, 65605, 65606,
        65607, 65608, 65609, 65610, 65611, 65612, 65613, 65614, 65615, 65616, 65617, 65618, 65619,
        65620, 65621, 65622, 65623, 65624, 65625, 65626, 65627, 65628, 65629, 65630, 65631, 65632,
        65633, 65634, 65635, 65636, 65637, 65638, 65639, 65640, 65641, 65642, 65643, 65644, 65645,
        65646, 65647, 65648, 65649, 65650, 65651, 65652, 65653, 65654, 65655, 65656, 65657, 65658,
        65659, 65660, 65661, 65662, 65663, 65664, 65665, 65666, 65667, 65668, 65669, 65670, 65671,
        65672, 65673, 65674, 65675, 65676, 65677, 65678, 65679, 65680, 65681, 65682, 65683, 65684,
        65685, 65686, 65687, 65688, 65689, 65690, 65691, 65692, 65693, 65694, 65695, 65696, 65697,
        65698, 65699, 65700, 65701, 65702, 65703, 65704, 65705, 65706, 65707, 65708, 65709, 65710,
        65711, 65712, 65713, 65714, 65715, 65716, 65717, 65718, 65719, 65720, 65721, 65722, 65723,
        65724, 65725, 65726, 65727, 65728, 65729, 65730, 65731, 65732, 65733, 65734, 65735, 65736,
        65737, 65738, 65739, 65740, 65741, 65742, 65743, 65744, 65745, 65746, 65747, 65748, 65749,
        65750, 65751, 65752, 65753, 65754, 65755, 65756, 65757, 65758, 65759, 65760, 65761, 65762,
        65763, 65764, 65765, 65766, 65767, 65768, 65769, 65770, 65771, 65772, 65773, 65774, 65775,
        65776, 65777, 65778, 65779, 65780, 65781, 65782, 65783, 65784, 65785, 65786, 65787, 65788,
        65789, 65790, 65791, 65792, 65793, 65794, 65795, 65796, 65797, 65798, 65799, 65800, 65801,
        65802, 65803, 65804, 65805, 65806, 65807, 65808, 65809, 65810, 65811, 65812, 65813, 65814,
        65815, 65816, 65817, 65818, 65819, 65820, 65821, 65822, 65823, 65824, 65825, 65826, 65827,
        65828, 65829, 65830, 65831, 65832, 65833, 65834, 65835, 65836, 65837, 65838, 65839, 65840,
        65841, 65842, 65843, 65844, 65845, 65846, 65847, 65848, 65849, 65850, 65851, 65852, 65853,
        65854, 65855, 65856, 65857, 65858, 65859, 65860, 65861, 65862, 65863, 65864, 65865, 65866,
        65867, 65868, 65869, 65870, 65871, 65872, 65873, 65874, 65875, 65876, 65877, 65878, 65879,
        65880, 65881, 65882, 65883, 65884, 65885, 65886, 65887, 65888, 65889, 65890, 65891, 65892,
        65893, 65894, 65895, 65896, 65897, 65898, 65899, 65900, 65901, 65902, 65903, 65904, 65905,
        65906, 65907, 65908, 65909, 65910, 65911, 65912, 65913, 65914, 65915, 65916, 65917, 65918,
        65919, 65920, 65921, 65922, 65923, 65924, 65925, 65926, 65927, 65928, 65929, 65930, 65931,
        65932, 65933, 65934, 65935, 65936, 65937, 65938, 65939, 65940, 65941, 65942, 65943, 65944,
        65945, 65946, 65947, 65948, 65949, 65950, 65951, 65952, 65953, 65954, 65955, 65956, 65957,
        65958, 65959, 65960, 65961, 65962, 65963, 65964, 65965, 65966, 65967, 65968, 65969, 65970,
        65971, 65972, 65973, 65974, 65975, 65976, 65977, 65978, 65979, 65980, 65981, 65982, 65983,
        65984, 65985, 65986, 65987, 65988, 65989, 65990, 65991, 65992, 65993, 65994, 65995, 65996,
        65997, 65998, 65999, 66000, 66001, 66002, 66003, 66004, 66005, 66006, 66007, 66008, 66009,
        66010, 66011, 66012, 66013, 66014, 66015, 66016, 66017, 66018, 66019, 66020, 66021, 66022,
        66023, 66024, 66025, 66026, 66027, 66028, 66029, 66030, 66031, 66032, 66033, 66034, 66035,
        66036, 66037, 66038, 66039, 66040, 66041, 66042, 66043, 66044, 66045, 66046, 66047, 66048,
        66049, 66050, 66051, 66052, 66053, 66054, 66055, 66056, 66057, 66058, 66059, 66060, 66061,
        66062, 66063, 66064, 66065, 66066, 66067, 66068, 66069, 66070, 66071, 66072, 66073, 66074,
        66075, 66076, 66077, 66078, 66079, 66080, 66081, 66082, 66083, 66084, 66085, 66086, 66087,
        66088, 66089, 66090, 66091, 66092, 66093, 66094, 66095, 66096, 66097, 66098, 66099, 66100,
        66101, 66102, 66103, 66104, 66105, 66106, 66107, 66108, 66109, 66110, 66111, 66112, 66113,
        66114, 66115, 66116, 66117, 66118, 66119, 66120, 66121, 66124, 66125, 66126, 66127, 66128,
        66129, 66130, 66131, 66132, 66133, 66134, 66135, 66136, 66137, 66138, 66139, 66140, 66141, 
    );
}

function _api_ni_centroids() {
    return array(
        # East Londonderry
        66129 => array('centre_lat' => 4.980766, 'centre_lon' => -6.904907 ),
        14276 => array('centre_lat' => 4.980766, 'centre_lon' => -6.904907 ),
        # Foyle
        66131 => array('centre_lat' => 4.933453, 'centre_lon' => -7.267456 ),
        14273 => array('centre_lat' => 4.933453, 'centre_lon' => -7.267456 ),
        # West Tyrone
        66141 => array('centre_lat' => 4.619797, 'centre_lon' => -7.410278 ),
        14292 => array('centre_lat' => 4.619797, 'centre_lon' => -7.410278 ),
        # Fermanagh &amp; South Tyrone
        14296 => array('centre_lat' => 4.354958, 'centre_lon' => -7.443237 ),
        66130 => array('centre_lat' => 4.354958, 'centre_lon' => -7.443237 ),
        # Newry &amp; Armagh
        14303 => array('centre_lat' => 4.297295, 'centre_lon' => -6.613770 ),
        66134 => array('centre_lat' => 4.297295, 'centre_lon' => -6.613770 ),
        # Upper Bann
        14300 => array('centre_lat' => 4.399750, 'centre_lon' => -6.350098 ),
        66140 => array('centre_lat' => 4.399750, 'centre_lon' => -6.350098 ),
        # South Down
        14306 => array('centre_lat' => 4.213860, 'centre_lon' => -6.152344 ),
        66138 => array('centre_lat' => 4.213860, 'centre_lon' => -6.152344 ),
        # Lagan Valley
        14309 => array('centre_lat' => 4.441296, 'centre_lon' => -6.108398 ),
        66132 => array('centre_lat' => 4.441296, 'centre_lon' => -6.108398 ),
        # Strangford
        14312 => array('centre_lat' => 4.514706, 'centre_lon' => -5.751343 ),
        66139 => array('centre_lat' => 4.514706, 'centre_lon' => -5.751343 ),
        # North Down
        14325 => array('centre_lat' => 4.651592, 'centre_lon' => -5.718384 ),
        66136 => array('centre_lat' => 4.651592, 'centre_lon' => -5.718384 ),
        # South Antrim
        14318 => array('centre_lat' => 4.699234, 'centre_lon' => -6.102905 ),
        66137 => array('centre_lat' => 4.699234, 'centre_lon' => -6.102905 ),
        # East Antrim
        66128 => array('centre_lat' => 4.832336, 'centre_lon' => -5.883179 ),
        14284 => array('centre_lat' => 4.832336, 'centre_lon' => -5.883179 ),
        # North Antrim
        66135 => array('centre_lat' => 4.993374, 'centre_lon' => -6.328125 ),
        14280 => array('centre_lat' => 4.993374, 'centre_lon' => -6.328125 ),
        # Mid Ulster
        66133 => array('centre_lat' => 4.721447, 'centre_lon' => -6.795044 ),
        14288 => array('centre_lat' => 4.721447, 'centre_lon' => -6.795044 ),
        # Belfast North
        14321 => array('centre_lat' => 4.618607, 'centre_lon' => -5.917511 ),
        66125 => array('centre_lat' => 4.618607, 'centre_lon' => -5.917511 ),
        # Belfast East
        14329 => array('centre_lat' => 4.598324, 'centre_lon' => -5.892792 ),
        66124 => array('centre_lat' => 4.598324, 'centre_lon' => -5.892792 ),
        # Belfast South
        14331 => array('centre_lat' => 4.582409, 'centre_lon' => -5.925064 ),
        66126 => array('centre_lat' => 4.582409, 'centre_lon' => -5.925064 ),
        # Belfast West
        14315 => array('centre_lat' => 4.606277, 'centre_lon' => -5.956650 ),
        66127 => array('centre_lat' => 4.606277, 'centre_lon' => -5.956650 ),
    );
}

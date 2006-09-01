<?

include_once '../../../../phplib/mapit.php';
include_once 'api_getGeometry.php';

function api_getBoundary_front() {
?>
<p><big>Returns boundary information for a constituency.</big></p>

<p>Returns the bounding polygon of the constituency, in latitude and longitude
coordinates (WGS84). Note that some constituencies have multiple parts to their
polygon, including holes in polygons. The 'sense' field gives the sense
direction of that part of the polygon.</p>

<h4>Arguments</h4>
<dl>
<dt>name</dt>
<dd>Name of the constituency.
</dl>

<?	
}

function api_getBoundary() {
	api_error('Name is a required option');
}

function api_getBoundary_name($name) {
	$areas_out = array('date' => date('Y-m-d'), 'data' => array());

	$name = html_entity_decode(normalise_constituency_name($name)); # XXX
	if (!$name) {
		api_error('Name not recognised');
		return;
	}

	$out = array();
	$areas = _api_cacheCheck('mapit_get_areas_by_type', 'WMC');
	$areas_info = _api_cacheCheck('mapit_get_voting_areas_info', $areas);
	$id = null;
	foreach ($areas_info as $k => $v) {
		if (html_entity_decode(normalise_constituency_name($v['name'])) == $name) {
			$id = $k;
		}
	}
	if (!$id) {
		api_error('No data found for name');
		return;
	}
	$out = mapit_get_voting_area_geometry($id, 'wgs84');

	$areas_out['data'] = $out['polygon'];
	api_output($areas_out);
}


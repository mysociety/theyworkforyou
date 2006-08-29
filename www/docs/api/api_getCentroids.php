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
	$areas = mapit_get_areas_by_type('WMC');
	$result = mapit_get_voting_areas_geometry($areas);
	print_r($result);
}

?>

<?php

include_once './easyparliament/init.php';
include_once INCLUDESPATH . '../../commonlib/phplib/mapit.php';
include_once INCLUDESPATH . '../docs/api/api_getGeometry.php';

/*
get_distance_from_westminster_in_miles_by_name
acceps constituency name as a parameter and uses constituency centroid
e.g: get_distance_from_westminster_in_miles_by_name('Ross, Skye and Lochaber');
returns false on failure
*/
function get_distance_from_westminster_in_miles_by_name($name) {
    
    //hardcode lat/lon for westminster
    $westminsterLat = 51.4998402617;
    $westminsterLon = -0.124662731546;
    
    $distance = false;
    
    $api_result = _api_getGeometry_name($name);
    if ($api_result !== null) {
        $pcLat = $api_result['centre_lat'];
        $pcLon = $api_result['centre_lon'];
        
        $distance = _get_distance_in_miles($pcLat,$pcLon,$westminsterLat,$westminsterLon);
        $distance = round($distance,0);
    }
    
    return $distance;
}


/*
get_distance_from_westminster_in_miles_by_pc
acceps postcode as a parameter and uses postcode centroid
e.g: get_distance_from_westminster_in_miles_by_pc('M1 2AB');
returns false on failure
*/
function get_distance_from_westminster_in_miles_by_pc($pc) {
    
    //hardcode lat/lon for westminster
    $westminsterLat = 51.4998402617;
    $westminsterLon = -0.124662731546;
    
    $distance = false;
    
    //look up latlons for postcodes
    $mapit_postcode_result = mapit_call('postcode', urlencode($pc));
    if (is_array($mapit_postcode_result)) {
        $pcLat = $mapit_postcode_result['wgs84_lat'];
        $pcLon = $mapit_postcode_result['wgs84_lon'];
        
        $distance = _get_distance_in_miles($pcLat,$pcLon,$westminsterLat,$westminsterLon);
        $distance = round($distance,0);
    }
    
    return $distance;
}



function _get_distance_in_miles($lat1,$lon1,$lat2,$lon2) {
    $R = 3958.75587; // Mean radius of the earth in miles
    $dLat = deg2rad($lat2-$lat1);
    $dLon = deg2rad($lon2-$lon1);
    
    $a = sin($dLat/2) * sin($dLat/2) +
    cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * 
    sin($dLon/2) * sin($dLon/2);
    
    $angle = 2 * atan2(sqrt($a), sqrt(1-$a));
     
    $distance = $R * $angle;
    
    return $distance;
}
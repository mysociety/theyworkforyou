<?php
/*
getKMLAndNeighbours.php

Accepts
cons_id/$_GET['c'] OR
postcode/$_GET['pc']
as a parameter to identify an area AND
$_GET['neighbours'] (1 or 0)
to specify whether to serve up touching areas as well


Serves up a single KML file containing the boundary of
- our target constituency
- any neighbouring constituencies
with each polygon formatted in appropriate party colours
by applying custom styling to KML files fetched from mapit API

Also includes a description with basic MP Info

author: Chris Shaw
*/

include_once '../../includes/easyparliament/init.php';
include_once '../api/api_functions.php';
include_once '../api/api_getMP.php';
include_once INCLUDESPATH . '../../commonlib/phplib/mapit.php';


/*define some suitable colours to represent our political parties
remember KML uses OBGR notation.*/
$colours =
array(
    "Alliance" =>                               "999999",
    "Conservative" =>                           "993333",
    "DUP" =>                                    "0033CC",
    "Green" =>                                  "009933",
    "Independent" =>                            "999999",
    "Labour" =>                                 "0000CC",
    "Liberal Democrat" =>                       "0099FF",
    "Plaid Cymru" =>                            "006600",
    "Respect" =>                                "000099",
    "Scottish National Party" =>                "00CCFF",
    "Sinn Fein" =>                              "003300",
    "Social Democratic and Labour Party" =>     "669966",
    "Speaker" =>                                "999999",
    "Unknown" =>                                "999999"
);

//fetch KML file from mapit API
function get_kml($area) {
    //Get KML file from mapit - using simplification for performance reasons
    $kml_url = OPTION_MAPIT_URL.'area/'.urlencode($area).'.kml';
    $kml_url .= '?simplify_tolerance='._get_simplify_tolerance($area);
    
    $mapit_ch = curl_init( $kml_url );
    
    curl_setopt($mapit_ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($mapit_ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
    curl_setopt($mapit_ch, CURLOPT_USERAGENT, 'PHP MaPit client');
    
    
    //deal with errors
    if (!($kml = curl_exec($mapit_ch))) {
        return rabx_error(RABX_ERROR_TRANSPORT,
               curl_error($mapit_ch) . " calling $url");
    }
    $C = curl_getinfo($mapit_ch, CURLINFO_HTTP_CODE);
    
    $out = $kml;
    
    if ($C == 404 && isset($errors['404'])) {
        return rabx_error($errors['404'], $out['error']);
    } elseif ($C == 400 && isset($errors['400'])) {
        return rabx_error($errors['400'], $out['error']);
    } elseif ($C != 200) {
        return rabx_error(RABX_ERROR_TRANSPORT, "HTTP error $C calling $url");
    } else {
        return $out;
    }
}//get_kml


/*
This returns a simplify tolerance value which should ensure small areas
are simplified less and large areas are simplified more in an attempt to
strike a decent balance between detail and performance.

I have arrived at these thresholds based on
the number of points in each unsimplified boundary.
The simplify tolerance values are entirely arbitrary:

boundries with 2,000 or les points   => 0.0009
boundries with 15,000 or more points => 0.0120
everywhere else                      => 0.0030

This is a difficult issue to solve well because:
- Not using any simplification at all generates the most aesthetically pleasing
  boundaries, but areas with many points which touch other areas with many
  points take far too long to load.
- Its very difficult to choose a constant simplify_tolerance value that would
  be appropriate for both 'Cities of London and Westminster' and
  'Ross, Skye and Lochaber' (for example).
- When you use different simplify_tolerance values for different areas,
  small areas have enough detail and large areas load faster. However,
  small areas that touch large areas suffer badly from gaps and overlaps because
  the same curve is shown with 2 different levels of detail.
As such, there is scope to tweak this to emphasise performance or appearance.
*/
function _get_simplify_tolerance($mapit_id) {
    
    $leastpoints =
    array(
    65882=> 1, 65818=> 1, 65851=> 1, 65937=> 1, 14421=> 1, 65939=> 1, 65825=> 1,
    65853=> 1, 14425=> 1, 14428=> 1, 66045=> 1, 65554=> 1, 14429=> 1, 66090=> 1,
    14419=> 1, 66014=> 1, 65731=> 1, 65650=> 1, 66042=> 1, 65887=> 1, 14427=> 1,
    65876=> 1, 65821=> 1, 65808=> 1, 65550=> 1, 65836=> 1, 65559=> 1, 65752=> 1,
    65913=> 1, 65689=> 1, 14431=> 1, 65824=> 1, 65759=> 1, 65883=> 1, 65917=> 1,
    65691=> 1, 66072=> 1, 65679=> 1, 65707=> 1, 65581=> 1, 14426=> 1, 65585=> 1,
    65574=> 1, 65630=> 1, 65594=> 1, 65662=> 1, 66023=> 1, 65570=> 1, 65721=> 1,
    65755=> 1, 65616=> 1, 14430=> 1, 65984=> 1, 66059=> 1, 66013=> 1, 66040=> 1,
    65794=> 1, 66058=> 1, 65867=> 1, 66028=> 1, 65604=> 1, 65595=> 1, 65827=> 1,
    66074=> 1, 65618=> 1, 65816=> 1, 65927=> 1, 65807=> 1, 65667=> 1, 65573=> 1,
    65597=> 1, 66016=> 1, 65576=> 1, 65941=> 1, 65834=> 1, 66055=> 1, 66066=> 1,
    65890=> 1, 65832=> 1, 66038=> 1, 65636=> 1, 66041=> 1, 65773=> 1, 65701=> 1,
    65598=> 1, 65644=> 1, 65837=> 1, 65972=> 1, 66079=> 1, 65587=> 1, 65600=> 1,
    65803=> 1, 14420=> 1, 65989=> 1, 65709=> 1, 14398=> 1, 65680=> 1, 65705=> 1,
    65787=> 1, 65777=> 1, 65819=> 1, 65778=> 1, 65603=> 1, 65580=> 1, 66077=> 1,
    65796=> 1, 65948=> 1, 65653=> 1, 65643=> 1, 66065=> 1, 65651=> 1, 66080=> 1,
    65626=> 1, 65655=> 1, 65567=> 1, 65593=> 1, 65879=> 1, 65688=> 1, 65996=> 1,
    66060=> 1, 66050=> 1, 65992=> 1, 65923=> 1, 65926=> 1, 65954=> 1, 65914=> 1,
    65782=> 1, 65983=> 1, 65970=> 1, 65611=> 1, 65562=> 1, 65938=> 1, 65704=> 1,
    65583=> 1, 65925=> 1, 65945=> 1, 65575=> 1, 65577=> 1, 65780=> 1, 65613=> 1,
    65860=> 1, 66022=> 1, 65623=> 1, 65551=> 1, 65910=> 1, 65877=> 1, 65566=> 1,
    65933=> 1, 65728=> 1, 65561=> 1, 65864=> 1, 65911=> 1, 65768=> 1, 66093=> 1,
    65720=> 1, 66062=> 1, 65781=> 1, 65982=> 1, 66120=> 1, 65725=> 1, 14449=> 1,
    66091=> 1, 14422=> 1, 66031=> 1, 65958=> 1, 65804=> 1, 65770=> 1, 65766=> 1,
    66048=> 1, 65985=> 1, 65915=> 1, 14408=> 1, 65839=> 1, 66092=> 1, 65897=> 1,
    65639=> 1, 65757=> 1, 66114=> 1, 65981=> 1, 66118=> 1, 65699=> 1, 65649=> 1,
    65863=> 1, 66033=> 1, 66026=> 1, 14453=> 1, 65798=> 1, 65965=> 1, 65973=> 1,
    65847=> 1, 65750=> 1, 65673=> 1, 65715=> 1, 65694=> 1, 65966=> 1, 65998=> 1,
    14443=> 1, 65647=> 1, 65886=> 1, 65928=> 1, 65870=> 1, 65802=> 1, 65703=> 1,
    65931=> 1, 65903=> 1, 65994=> 1, 65783=> 1, 14415=> 1, 65942=> 1, 65844=> 1,
    65754=> 1, 65589=> 1, 66069=> 1, 65946=> 1, 65993=> 1, 14413=> 1, 65898=> 1,
    65664=> 1, 65797=> 1, 65710=> 1, 66049=> 1, 66054=> 1, 65669=> 1, 65806=> 1,
    65790=> 1, 14399=> 1, 65765=> 1, 65609=> 1, 65912=> 1, 66070=> 1
    );
    $leastpoints[65719]= 1;
    //also add South Shields - it has more than 2000 points,
    //but simplify_tolerance=0.003 makes it simplify to a single line
    
    
    $mostpoints =
    array(
    65684=> 1, 65658=> 1, 65891=> 1, 14411=> 1, 65758=> 1, 14446=> 1, 65749=> 1,
    66109=> 1, 65789=> 1, 66088=> 1, 66111=> 1, 66110=> 1, 65642=> 1, 14403=> 1,
    65690=> 1, 66115=> 1, 14405=> 1, 65810=> 1, 14445=> 1, 66096=> 1, 66081=> 1,
    65726=> 1, 14455=> 1, 65602=> 1, 14404=> 1, 65904=> 1, 66102=> 1, 14410=> 1,
    65854=> 1, 14406=> 1
    );
    
    
    if (isset($leastpoints[$mapit_id])) {
        return 0.0009;
    } elseif (isset($mostpoints[$mapit_id])) {
        return 0.012;
    } else {
        return 0.003;
    }
}//_get_simplify_tolerance

//format MP info as HTML description
function _process_mp_info($mp_info) {
    
    $description = '';
    
    $description .= '<div style="width:240px; height:120px;">';
    $description .= '<table border="0" cellspacing="0" cellpadding="2">';
    $description .= '<tr><td colspan="2" class="title">';
    $description .= $mp_info['constituency'].'</td></tr>';
    $description .= '<tr><td>MP:</td><td>'.$mp_info['full_name'].'</td></tr>';
    $description .= '<tr><td>Party:</td><td>'.$mp_info['party'].'</td></tr>';
    
    $description .= '<tr><td colspan="2">';
    $description .= '<a href="';
    $description .= 'http://www.theyworkforyou.com/mp/'.$mp_info['person_id'];
    $description .= '" target="_blank">more information</a>';
    
    $description .= '</td></tr>';
    
    $description .= '</table>';
    $description .= '</div>';
    
    
    return $description;
}//_process_mp_info

/*
Get MP details for constituency $name
returns array() on failure
*/
function _get_mp_info($name) {
    
    $output = array();
    
    $result_getMP_constituency = _api_getMP_constituency($name);
    
    //attach party and description if possible
    if ($result_getMP_constituency == array()) {
        $output =
        array(
            'party' => 'Unknown',
            'description' => ''
        );
    } else {
        $description = _process_mp_info($result_getMP_constituency);
        
        $output =
        array(
            'party' => $result_getMP_constituency['party'],
            'description' => $description
        );
    }
    
    return $output;
}//_get_mp_info

/*
Get info (id and party)
for the target constituency and any neighbours
returns array() on failure
*/
function get_constituencies_info($mapit_id, $neighbours) {
    
    $output = array();
    
    //get constituency for target postcode
    $mapit_area_result = mapit_call('area', urlencode($mapit_id));
    
    if (is_array($mapit_area_result)) {
        if (isset($mapit_area_result['name'])) {
            
            $constituency_name = $mapit_area_result['name'];
            
            if ($neighbours) {
                
                //find any neighbouring constituencies
                $mapit_touches_result = mapit_call('area/touches',
                                        urlencode($mapit_id));
                
                //do the touching constituencies first
                if (is_array($mapit_touches_result)) {
                    foreach ($mapit_touches_result as $touching_area) {
                        if ($touching_area['type'] == 'WMC') {
                            
                            $result = _get_mp_info($touching_area['name']);
                            $output[] =
                            array(
                                'id' => $touching_area['id'],
                                'party' => $result['party'],
                                'description' => $result['description'],
                                'type' => 'context'
                            );
                        }
                    }//foreach
                }
            
            }//if ($neighbours)
            
            //and then the focussed constituency
            $result = _get_mp_info($constituency_name);
            $output[] =
            array(
                'id' => $mapit_id,
                'party' => $result['party'],
                'description' => $result['description'],
                'type' => 'main'
            );
            /* working in this order gives our focussed area
            the 'top' z index, which saves us re-sorting later */
            
        }
    }
    
    return $output;
}//get_constituencies_info

function _pc_to_mapit_id($postcode) {
    $output = false;
    $mapit_postcode_result = mapit_call('postcode', urlencode($postcode));
    if (is_array($mapit_postcode_result)) {
        if (isset($mapit_postcode_result['shortcuts']['WMC'])) {
            $output = $mapit_postcode_result['shortcuts']['WMC'];
        }
    }
    return $output;
}//_pc_to_mapit_id

function _cons_id_to_mapit_id($cons_id) {
    
    $output = false;
    
    $db = new ParlDB;
    $q = $db->query(
    'SELECT name FROM constituency WHERE main_name=1 AND cons_id='
    . mysql_real_escape_string($cons_id));
    
    /* Here I have replicated the method used in
    _api_getGeometry_name and api_getBoundary_name for consistency
    I'm not sure if this will deal with
    historic constituencies correctly though */
    $areas_info = mapit_call('areas', 'WMC');
    foreach ($areas_info as $k => $v) {
        if (normalise_constituency_name($v['name']) == $q->field(0, 'name')) {
            $output = $k;
            break;
        }
    }
    
    return $output;
    
}//_cons_id_to_mapit_id

//apply custom styling to KML files based on party
function format_kml($kmls, $colours) {
    
    $out_kml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>
    <kml xmlns=\"http://www.opengis.net/kml/2.2\">
    <Document>\n";
    
    //define new style block
    foreach ($colours as $party => $colour) {
        $out_kml .= "            <Style id=\"$party\">
            <LineStyle>
                <color>70$colour</color>
                <width>2</width>
            </LineStyle>
            <PolyStyle>
                <color>3d$colour</color>
            </PolyStyle>
        </Style>\n";
    }
    
    $placemarks = '';
    foreach ($kmls as $area => $kml) {
        
        try {
            $kml_parsed = new SimpleXMLElement($kml['kml']);
        } catch (Exception $e) {
            return '';
        }
        
        //Grab the bit of the KML file we need
        $bitwewant = $kml_parsed->Document->Placemark;
        $bitwewant->description = $kml['description'];
        
        //apply styling
        if ($kml['type'] == 'main') {
            /* special style for the focussed area
            this picks out the target area with a white border */
            $out_kml .= "            <Style id=\"main\">
                <LineStyle>
                    <color>FFFFFFFF</color>
                    <width>4</width>
                </LineStyle>
                <PolyStyle>
                    <color>3d{$colours[$kml['party']]}</color>
                </PolyStyle>
            </Style>\n";
            $bitwewant->styleUrl = '#main';
        } else {
            $bitwewant->styleUrl = '#'.$kml['party'];
        }
        
        $placemarks .= $bitwewant->asXML();
    }
    
    $out_kml .= $placemarks;
    $out_kml .= '</Document>
    </kml>';
    
    return $out_kml;
    
}//format_kml






//deal with input parameters
$postcode = get_http_var('pc');
$cons_id = get_http_var('c');
$neighbours = get_http_var('neighbours', 0);
if ($neighbours != 0) {
    $neighbours = 1;
}


if (is_numeric($cons_id)) {
    //we've got a constituency id
    $mapit_id = _cons_id_to_mapit_id($cons_id);
} else {
    //assume we've got a postcode
    $mapit_id = _pc_to_mapit_id($postcode);
}

if (!$mapit_id) {
    header('HTTP/1.0 404 Not Found');
    die();
}


//grab the MP and party details
$areas = get_constituencies_info($mapit_id, $neighbours);
if (!isset($areas[0]['id']) || !isset($areas[0]['party'])) {
    header('HTTP/1.0 404 Not Found');
    die();
}


/*grab the boundaries
attach the MP and party details to them
as header data and styling info*/
$kmls = array();
foreach ($areas as $area) {
    //if there are any parties that aren't in our array, then deal with that
    if (!array_key_exists($area['party'], $colours)) {
        $kmls[$area['id']]['party'] = 'Unknown';
    } else {
        $kmls[$area['id']]['party'] = $area['party'];
    }
    $kmls[$area['id']]['kml'] = get_kml($area['id']);
    $kmls[$area['id']]['description'] = $area['description'];
    $kmls[$area['id']]['type'] = $area['type'];
}


if ($kmls == array()) {
    header('HTTP/1.0 404 Not Found');
    die();
} else {
    $out_kml = format_kml($kmls, $colours);
    if ($out_kml == '') {
        header('HTTP/1.0 404 Not Found');
        die();
    }
    
    //set the KML Content-type header
    header('Content-type: application/vnd.google-earth.kml+xml');
    echo $out_kml;
}


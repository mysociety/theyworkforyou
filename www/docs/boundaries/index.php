<?php

$this_page = 'boundaries';
include_once '../../includes/easyparliament/init.php';
include_once INCLUDESPATH . '../../commonlib/phplib/mapit.php';

$PAGE->page_start();
?>
<script type="text/javascript" src="http://maps.google.com/maps/api/js?sensor=false"></script>
<h1>Scottish and Northern Irish election constituency boundaries</h1>

<div id="boundaries">
<?php

$pc = get_http_var('pc');
$pc = ''; # No form submissions, please
if ($pc && !validate_postcode($pc)) {
    print '<p class="error">Sorry, that doesn&rsquo;t appear to be a valid postcode.</p>';
    $pc = '';
}
if ($pc) {
    # current will have WMC key. If Scottish, has SPC and SPE too. If NI, has NIE.
    $mapit = mapit_call('postcode', $pc);
    if (is_object($mapit)) { # RABX error returns an object
        print '<p class="error">Afraid we couldn&rsquo;t find that postcode.</p>';
        $pc = '';
    }
}
if ($pc) {
    $current = array(); $current_id = array();
    foreach ($mapit['areas'] as $id => $val) {
        $current[$val['type']] = $val['name'];
        $current_id[$val['type']] = $id;
    }
    if (!array_key_exists('SPC', $current) && !array_key_exists('NIE', $current)) {
        print '<p class="error">That doesn&rsquo;t appear to be a Scottish or Northern Irish postcode.</p>';
        $pc = '';
    }
}

if ($pc) {
    if (array_key_exists('SPC', $current)) {
        $a = array($current['SPC'], $current['SPE']);
        $country = 'S';
    } else {
        $a = array($current['NIE']);
        $country = 'N';
    }
}

if (!$pc || $country == 'N') { ?>

<div class="informational">
This page was for before the 2011 Scottish and Northern Irish elections.
It does not currently function.
</div>

<div class="picture">
<a href="http://www.flickr.com/photos/markybon/138214000/" title="Boundaries by MarkyBon, on Flickr"><img src="http://farm1.static.flickr.com/51/138214000_80327fe675.jpg" width="358" height="500" alt="Boundaries"></a>
<br><small>Boundaries by MarkyBon</small>
</div>
<?php
}

if ($pc) {
    $db = new ParlDB;
    # Just left politicians
    $q = $db->query("SELECT person_id, first_name, last_name, constituency, house FROM member
        WHERE constituency IN ('" . join("','", $a) . "')
        AND ( ( house = 3 and left_house = '2011-03-24' ) or ( house = 4 and left_house = '2011-03-23') )");
    $mreg = array();
    for ($i=0; $i<$q->rows(); $i++) {
        $cons = $q->field($i, 'constituency');
        $house = $q->field($i, 'house');
        if (($house == 4 && $cons == $current['SPC']) || ($house == 3 && $cons == $current['NIE'])) {
            $name = $q->field($i, 'first_name') . ' ' . $q->field($i, 'last_name');
        } elseif ($house == 4 && $cons == $current['SPE']) {
            $mreg[] = $q->field($i, 'first_name') . ' ' . $q->field($i, 'last_name');
        }
    }

    # If Scottish, has SPC and SPE. Otherwise, empty.
    $mapit = mapit_call('postcode', $pc, array('generation' => 15)); # Magic number 15
    $new = array(); $new_id = array();
    foreach ($mapit['areas'] as $id => $val) {
        $new[$val['type']] = $val['name'];
        $new_id[$val['type']] = $id;
    }

    if ($country == 'S') {
?>

<div id="maps">
<h3>Maps</h3>
<p class='desc'>New <?=$new['SPC']?> constituency for May 5th:</p>
<div id="map_next" style="width:400px;height:400px"></div>
<p class="desc">Former constituency of <?=$current['SPC']?>:</p>
<div id="map_now" style="width:400px;height:400px"></div>
<p class="footer"><small>Uses <a href="http://www.ordnancesurvey.co.uk/">Ordnance Survey</a> data &copy; Crown copyright and database right 2010, via <a href="http://mapit.mysociety.org/">MaPit</a>.</small></p>

</div>

<script>
$(function () {
    var opt = {
        zoom: 5,
        center: new google.maps.LatLng(55, -3),
        mapTypeId: google.maps.MapTypeId.ROADMAP
    };
    var map = new google.maps.Map(document.getElementById('map_next'), opt);
    var boundary = new google.maps.KmlLayer('http://mapit.mysociety.org/area/<?=$new_id['SPC']?>.kml');
    boundary.setMap(map);
    var map = new google.maps.Map(document.getElementById('map_now'), opt);
    var boundary = new google.maps.KmlLayer('http://mapit.mysociety.org/area/<?=$current_id['SPC']?>.kml');
    boundary.setMap(map);
});
</script>
<?php
    }
?>
    <ul class="results">
<?php
    if (count($new)) {
        print "<li>For the Parliament election, you are in the <strong>$new[SPC]</strong> constituency, in the <strong>$new[SPE]</strong> region.";
    } elseif ($country == 'N') {
        print "<li>For the Assembly election, you are in the <strong>$current[WMC]</strong> constituency.";
    } else {
        print '<li>We cannot look up the constituency for the election for some reason, sorry.';
    }

    if ($country == 'S') {
        $mp_url = '/msp/' . make_member_url($name, '', 4);
?>
<li>You were in the <strong><?=$current['SPC']?></strong> constituency, in the <strong><?=$current['SPE']?></strong> region; your constituency MSP was <a href='<?=$mp_url?>'><?=$name?></a>, and your regional MSPs were <?php
        foreach ($mreg as $k => $n) {
            print "<a href='/msp/" . make_member_url($n, '', 4) . "'>$n</a>";
            if ($k < count($mreg)-2) print ', ';
            elseif ($k == count($mreg)-2) print ' and ';
        }
        echo '.</li>';
    } elseif ($country == 'N') {
        $mp_url = '/mla/' . make_member_url($name, '', 3);
?>
<li>You were in the <strong><?=$current['NIE']?></strong> constituency; your constituency MLA was <a href='<?=$mp_url?>'><?=$name?></a>.</li>
<?php
    }
    echo '</ul>';

    if (count($new) && $current['SPC'] == $new['SPC']) {
        print '<p>The constituency may have kept the same name but altered its boundaries &ndash; do check the maps on the right.</p>';
    }
    if ($country == 'N' && $current['NIE'] == $current['WMC']) {
        print '<p>The constituency may have kept the same name but altered its boundaries &ndash; see the <a href="http://www.boundarycommission.org.uk/pics/big_map_1.jpg">summary map</a>.</p>';
    }
    if ($country == 'S') {
        print '<p class="informational"><a href="/scotland/">View TheyWorkForYou&rsquo;s coverage of the Scottish Parliament</a></p>';
    }
    if ($country == 'N') {
        print '<p class="informational"><a href="/ni/">View TheyWorkForYou&rsquo;s coverage of the Northern Ireland Assembly</a></p>';
    }
}

?>

<p class="intro">Constituency boundaries are <strong>changing</strong> for the
2011 Scottish and Northern Irish elections. Enter your postcode here to find out what constituency
you were in,
and what constituency you are now voting in at the election, along
with maps of before and after for Scotland (Northern Irish people will have to make
do with this <a href="http://www.boundarycommission.org.uk/pics/big_map_1.jpg">overall summary map</a> from the Boundary Commission).
</p>

<form method="get">
<p><label for="pc">Enter your Scottish or Northern Irish postcode:</label>
<input disabled type="text" id="pc" name="pc" value="<?=_htmlspecialchars(get_http_var('pc'))?>" size="7">
<input disabled type="submit" value="Look up">
</p>
</form>

<p>This service should work anywhere in Scotland or Nothern Ireland, errors and omissions excepted.</p>

<p>This service is also available through our web service <a href="http://mapit.mysociety.org/">MaPit</a>,
which can provide programmatic access to the constituency for a particular postcode.</p>

<ul class="results">
<li><a href="/scotland/">TheyWorkForYou&rsquo;s coverage of the Scottish Parliament</a>
<li><a href="/ni/">TheyWorkForYou&rsquo;s coverage of the Northern Ireland Assembly</a>
</ul>

</div>
<?php

$PAGE->page_end();

<?php

$this_page = 'boundaries';
include_once "../../includes/easyparliament/init.php";
include_once '../../../commonlib/phplib/mapit.php';

$PAGE->page_start();
?>
<script type="text/javascript" src="http://maps.google.com/maps/api/js?sensor=false"></script>
<h2>Scottish election constituency boundaries</h2>

<div id="boundaries">
<?

$pc = get_http_var('pc');
if ($pc && !validate_postcode($pc)) {
    print '<p class="error">Sorry, that doesn&rsquo;t appear to be a valid postcode.</p>';
    $pc = '';
}
if ($pc) {
    # current will have WMC key. If Scottish, has SPC and SPE too.
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
    if (!array_key_exists('SPC', $current)) {
        print '<p class="error">That doesn&rsquo;t appear to be a Scottish postcode.</p>';
        $pc = '';
    }
}
if ($pc) {
    $a = array($current['SPC'], $current['SPE']);
    $db = new ParlDB;
    $q = $db->query("SELECT person_id, first_name, last_name, constituency, house FROM member
        WHERE house=4 AND constituency IN ('" . join("','", $a) . "')
        AND left_reason = 'still_in_office'");
    $mreg = array();
    for ($i=0; $i<$q->rows(); $i++) {
        $cons = $q->field($i, 'constituency');
        if ($cons == $current['SPC']) {
            $name = $q->field($i, 'first_name') . ' ' . $q->field($i, 'last_name');
        } elseif ($cons == $current['SPE']) {
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
$(function(){
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

    <ul class="results">
<?
    $mp_url = '/msp/' . make_member_url($name, '', 4);
    if (isset($new)) {
        print "<li>For the general election, you <!-- are -->will be in the <strong>$new[SPC]</strong> constituency, in the <strong>$new[SPE]</strong> region.";
    } else {
        print '<li>We cannot look up the constituency for the election for some reason, sorry.';
    }
?>
<li>You <!-- were -->are in the <strong><?=$current['SPC']?></strong> constituency, in the <strong><?=$current['SPE']?></strong> region; your constituency MSP <!-- was -->is <a href='<?=$mp_url?>'><?=$name?></a>, and your regional MSPs <!-- were -->are <?
    foreach ($mreg as $k => $n) {
        print "<a href='/msp/" . make_member_url($n, '', 4) . "'>$n</a>";
        if ($k < count($mreg)-2) print ', ';
        elseif ($k == count($mreg)-2) print ' and ';
    }
?>.</p>

<?
    echo '</ul>';
    if (isset($new) && $current['SPC'] == $new['SPC']) {
        print '<p>The constituency may have kept the same name but altered its boundaries &ndash; do check the maps on the right.</p>';
    }
}

if (!$pc) { ?>
<div class="picture">
<a href="http://www.flickr.com/photos/markybon/138214000/" title="Boundaries by MarkyBon, on Flickr"><img src="http://farm1.static.flickr.com/51/138214000_80327fe675.jpg" width="358" height="500" alt="Boundaries"></a>
<br><small>Boundaries by MarkyBon</small>
</div>
<?
}
?>

<p class="intro">Constituency boundaries are <strong>changing</strong> for the
2011 Scottish election. Enter your postcode here to find out what constituency
you are currently <!-- were --> in,
and what constituency you will be <!-- are now --> voting in at the election, along
with maps of before and after.
</p>

<form method="get">
<p><label for="pc">Enter your Scottish postcode:</label>
<input type="text" id="pc" name="pc" value="<?=htmlspecialchars(get_http_var('pc'))?>" size="7">
<input type="submit" value="Look up">
</p>
</form>

<p>This service should work anywhere in Scotland, errors and omissions excepted.</p>

<p>This service is also available through our web service <a href="http://mapit.mysociety.org/">MaPit</a>.</p>

</div>
<?

$PAGE->page_end();


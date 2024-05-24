<link rel="stylesheet" href="https://mapit.mysociety.org/static/mapit/leaflet/leaflet.5b09190de2e8.css" />
<script src="https://mapit.mysociety.org/static/mapit/js/reqwest.min.c949fe855720.js"></script>
<script src="https://mapit.mysociety.org/static/mapit/leaflet/leaflet.f1cc0a70c78b.js"></script>

<h1>General election</h1>

<p><a href='#current'>See your current representatives</a>

<p>There is a UK general election on <strong>4th July 2024</strong>
<?php
$date = strtotime("2024-07-04");
$datediff = ceil(($date - time()) / 86400);
if ($datediff > 1) {
    echo "($datediff days away)";
} elseif ($datediff > 0) {
    echo '(tomorrow!)';
} elseif ($datediff > -86400) {
    echo '(today!)';
}
?>.

<p>For this election, you will be in the
<strong><?= $ballot->post_name ?></strong>
constituency.
You can see statistics and information for your new constituency at the
<a href="https://www.localintelligencehub.com/area/WMC23/<?= rawurlencode($ballot->post_name) ?>">Local Intelligence Hub</a>.

<p>
The people standing in your constituency
<?php
if (!$ballot->candidates_verified) { echo '(not yet finalised or verified)'; }
?> are:

<ul>
<?php foreach ($ballot->candidates as $candidate) {
    echo '<li>';
    #echo '<a href="' . $candidate->person->absolute_url . '">';
    echo $candidate->person->name;
    #echo '</a>';
    echo ' (' . $candidate->party->party_name . ')'; # photo_url
    echo '</li>';
}
?>
</ul>

<p>
<a href="https://democracyclub.org.uk/"><img width=150 align="right" src="https://static.democracyclub.org.uk/static/dc_theme/images/logo-with-text.png" alt="Democracy Club"></a>
For more information visit <a href="<?= $ballot->wcivf_url ?>">WhoCanIVoteFor</a>.
This data has been provided by <a href="https://democracyclub.org.uk/">Democracy Club</a>, thanks to them.

<p>Many constituency boundaries have changed for this election.
Here's a map of your new constituency (pink) with your
existing constituency in grey:

<div id="map" style="max-width: 400px; margin-bottom: 2em;">
<div id="leaflet" style=" position: relative; width: 100%; height: 0; padding-top: 100%;">
</div>
</div>
<script>
    var map = new L.Map("leaflet");
    map.attributionControl.setPrefix('');
    var osm = new L.TileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: 'Map © <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
        maxZoom: 18
    });
    map.addLayer(osm);

    var mapit = mapit || {};
    mapit.area_loaded = function(data) {
        var area = new L.GeoJSON(data);
        area.on('dblclick', function(e){
            var z = map.getZoom() + (e.originalEvent.shiftKey ? -1 : 1);
            map.setZoomAround(e.containerPoint, z);
        });
        area.setStyle({ color: this.mapit.colour });
        mapit.areas.addLayer(area);
        if (this.mapit.type == 'new') area.bringToFront();
        if (this.mapit.type == 'old') area.bringToBack();
        map.fitBounds(mapit.areas.getBounds());
    };
    mapit.areas = L.featureGroup().addTo(map);
</script>



<script>
    reqwest({
        url: 'https://mapit.mysociety.org/area/<?= $mapit_ids['new'] ?>.geojson?simplify_tolerance=0.0001',
        type: 'json',
        mapit: {
            colour: '#f0f',
            type: 'new'
        },
        crossOrigin: true,
        success: mapit.area_loaded
    });
    reqwest({
        url: 'https://mapit.mysociety.org/area/<?= $mapit_ids['old'] ?>.geojson?simplify_tolerance=0.0001',
        type: 'json',
        mapit: {
            colour: '#666',
            type: 'old'
        },
        crossOrigin: true,
        success: mapit.area_loaded
    });
</script>

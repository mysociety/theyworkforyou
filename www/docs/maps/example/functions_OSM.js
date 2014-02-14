function init() {
    
    //create map
    map = new L.Map('map-canvas').setView([55,-4], 5);
    
    L.tileLayer('http://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© <a href="http://openstreetmap.org">OpenStreetMap</a> contributors',
        maxZoom: 18
    }).addTo(map);
    
    
    //register events
    $('#submit_postcode').click(function() {
        getBoundariesByPostcode($('#postcode').val());
    });
    
}

function getBoundariesByPostcode(postcode) {
    $('#status').text('loading boundaries...');
    
    plotKML(postcode);
}

function plotKML(postcode) {
    
    for (var i = 0; i<layers.length; i++) {
        map.removeLayer(layers[i]);
    }
    layers = [];
    
    //load the main area first so the user sees something as quick as possible
    var url = '../getKMLAndNeighbours.php';
    url += '?pc='+encodeURIComponent(postcode);
    url += '&neighbours=0';
    
    layers[0] = new L.KML(url, {async: true});
    layers[0].on("loaded", function(e) {
        
        map.fitBounds(e.target.getBounds());
        
        //then load the neighbouring areas in the background
        //it doesn't matter if these take a little longer
        var url = '../getKMLAndNeighbours.php';
        url += '?pc='+encodeURIComponent(postcode);
        url += '&neighbours=1';
        
        layers[1] = new L.KML(url, {async: true});
        layers[1].on("loaded", function(e) { 
            map.removeLayer(layers[0]);
            $('#status').html('&nbsp;');
        });
        map.addLayer(layers[1]);
    });
    map.addLayer(layers[0]);
    
}
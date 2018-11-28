// init maps and set to ghent
var map = L.map('map');

// locate the user and set view to users location
map.locate({setView: true, maxZoom: 16});
// If location found, set popup
map.on('locationfound', onLocationFound);
// Else, let the user know we didn't find him
map.on('locationerror', onLocationError);

// Add layer with copyright and title
 L.tileLayer('https://{s}.tile.osm.org/{z}/{x}/{y}.png', {
    attribution: '&copy; <a href="http://osm.org/copyright">OpenStreetMap</a> contributors'
  }).addTo(map);

// Layer to add GeoJSON to
var poly = {
  "color": "#ff7800",
  "weight": 5,
  "opacity": 0.65
};
var node = {
  "iconSize": [10, 50],
}
// Load in the GeoJSON for the bike parkings
var parkJSON = [];
jQuery.getJSON("json/fietsparkingen-gent.geojson").done(
  function (json){
    L.geoJSON(json,{
      style: function(feature){
        var obj = feature.id.split("/");
        switch (obj[0]){
          case "way": return poly;
          case "node": return node;
        }
      }
    }).addTo(map);
  });

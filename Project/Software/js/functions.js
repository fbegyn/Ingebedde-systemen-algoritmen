function onLocationFound(e) {
	var radius = e.accuracy / 2;
  L.circle(e.latlng, radius).addTo(map);
}

function onLocationError(e) {
  alert(e.message);
}

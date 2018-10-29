<!DOCTYPE html>
<html>
<head><?php
require_once 'config.php';
require_once 'func.php';
if(isset($_GET['input'])) $input = $_GET['input']; else $input = "https://datatank.stad.gent/4/mobiliteit/bezettingparkingsrealtime.json";
$mysqli = initialize_mysql_connection(); ?>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.2.0/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.2.0/dist/leaflet.js"></script>

<script type="text/javascript">
// available parkings
var availableParkings = [];

// coord object
class Coord {
  constructor(latitude,longitude){
  this.lat = latitude;
  this.lon = longitude;
  }
}

var convexhull = new function() {
	// Returns a new array of points representing the convex hull from an array of points
	this.makeHull = function(points) {
		var newPoints = points.slice();
    // sort the array based on the COORD_COMPARATOR function from below
		newPoints.sort(this.COORD_COMPARATOR);
		return this.makeHullPresorted(newPoints);
	};
  // -1: a more south/west then b
  // +1: a more north/east then b
	this.COORD_COMPARATOR = function(a, b) {
    // lon comparisson
		if (a.lon < b.lon)
			return -1;
		else if (a.lon > b.lon)
			return +1;
    // lat comparisson
		if (a.lat < b.lat)
			return -1;
		else if (a.lat > b.lat)
			return +1;
		else
			return 0;
	};
	// Returns the convex hull, assuming that each points[i] <= points[i + 1]. Runs in O(n) time.
	this.makeHullPresorted = function(points) {
		if (points.length <= 1)
			return points.slice();
		// Andrew's monotone chain algorithm. Positive y coordinates correspond to "up"
		// as per the mathematical convention, instead of "down" as per the computer
		// graphics convention. This doesn't affect the correctness of the result.
		var upperHull = [];
		for (var i = 0; i < points.length; i++) {
			var p = points[i];
			while (upperHull.length >= 2) {
				var q = upperHull[upperHull.length - 1];
				var r = upperHull[upperHull.length - 2];
				if ((q.lon - r.lon) * (p.lat - r.lat) >= (q.lat - r.lat) * (p.lon - r.lon))
					upperHull.pop();
				else
					break;
			}
			upperHull.push(p);
		}
		upperHull.pop();
		var lowerHull = [];
		for (var i = points.length - 1; i >= 0; i--) {
			var p = points[i];
			while (lowerHull.length >= 2) {
				var q = lowerHull[lowerHull.length - 1];
				var r = lowerHull[lowerHull.length - 2];
				if ((q.lon - r.lon) * (p.lat - r.lat) >= (q.lat - r.lat) * (p.lon - r.lon))
					lowerHull.pop();
				else
					break;
			}
			lowerHull.push(p);
		}
		lowerHull.pop();
		if (upperHull.length == 1 && lowerHull.length == 1 && upperHull[0].lon == lowerHull[0].lon && upperHull[0].lat == lowerHull[0].lat)
			return upperHull;
		else
			return upperHull.concat(lowerHull);
	};
};

// adapt this to test if the input file is valid, make sure you check if the required fields are there for your algorithm to work.
// Throw Exceptions if this is not the case.
function validJSONRealtimeParking(json){
    try{
      if !(json.hasOwnProperty("latitude")){
        throw "Latitude is missing from the JSOn object.";
      }
      if !(json.hasOwnProperty("longitude")){
        throw "Longitude is missing from the JSOn object.";
      }
      if !(json.hasOwnProperty("availableCapacity")){
        throw "AvailableCapacity is missing from the JSOn object.";
      }
      if !(json.hasOwnProperty("totalCapacity")){
        throw "TotalCapacity is missing from the JSOn object.";
      }
    	return true;
    }
    catch(err){
      alert(err.message);
      return false;
    }
}

function loadParkingData(){
  var xhttp = new XMLHttpRequest();
  xhttp.onreadystatechange = function() {
    if (this.readyState == 4 && this.status == 200) {
      jsonparking = JSON.parse(this.responseText);

      // Draw all parkings on the map
      // green => available (parkingStatus -> open == true and availableCapacity/totalCapacity >= 25%)
      // orange => available, but very crowded (parkingStatus -> open == true and availableCapacity/totalCapacity < 25%)
      // red => unavailable

      // Iterate over all the parkings
      for(var i = 0; i < jsonparking.length; i++){
        var parking = jsonparking[i];
        // print out the corrdinates of the parkings
        console.log(parking.latitude.toString() + "," + parking.longitude.toString());
        // print out the parking available capacity
	      console.log(parking.parkingStatus.availableCapacity)
        // print out the parking total capacity
	      console.log(parking.parkingStatus.totalCapacity)
        // Status: green - parking open and above 25% capacity
	      if(parking.parkingStatus.open && ((1.0 * parking.parkingStatus.availableCapacity/parking.parkingStatus.totalCapacity) >= 0.25)){
	        var circle = L.circle([parking.latitude, parking.longitude], {
	            color: 'green',
	            fillColor: 'green',
	            fillOpacity: 0.5,
	            radius: 50
	        }).addTo(mymap);
          availableParkings.push(parking);
	      }
        // status: orange - parking open and below 25% capacity
	      else if(parking.parkingStatus.open && ((1.0 * parking.parkingStatus.availableCapacity/parking.parkingStatus.totalCapacity) < 0.25)){
	        var circle = L.circle([parking.latitude, parking.longitude], {
	            color: 'orange',
	            fillColor: 'orange',
	            fillOpacity: 0.5,
	            radius: 50
	        }).addTo(mymap);
          availableParkings.push(parking);
	      }
        // status: red - parking closed or other edge cases
        else{
	        var circle = L.circle([parking.latitude, parking.longitude], {
	            color: 'red',
	            fillColor: 'red',
	            fillOpacity: 0.5,
	            radius: 50
	        }).addTo(mymap);
	      }
      }

      // Object with all the locations with available parkings
      var points = [];
      for(var i = 0; i < availableParkings.length; i++){
				points.push(new Coord(availableParkings[i].latitude,availableParkings[i].longitude));
      }

			// Draw the convex hull over points
      var hull = convexhull.makeHull(points);
      var path = [];
      for(var i = 0;i<hull.length;i++){
          path.push([hull[i].lat,hull[i].lon])
      }
      path.push([hull[0].lat,hull[0].lon])
      var blue_line = L.polyline(path, {color: 'blue'}).addTo(mymap);
    }
  };

  xhttp.open("GET", "<?php echo $input;?>", true);
  xhttp.send();
}
</script>

</head>
<body>
  <div id="map" style="width: 800px; height: 800px"></div>
  <script>
    var mymap = L.map('map').setView([51.04972991,3.7229769], 14);
    L.tileLayer('https://api.tiles.mapbox.com/v4/{id}/{z}/{x}/{y}.png?access_token={accessToken}', {
    	attribution: '',
	    maxZoom: 18,
	    id: 'mapbox.streets',
	    accessToken: 'pk.eyJ1IjoibXNsZW1icm8iLCJhIjoiY2l0anp0Z2FkMDAzcjN4bGlxemFzczcwNyJ9.0P3QRRthdL8Jf2pGdWWI3g'
    }).addTo(mymap);

    loadParkingData();
</script>
</body>
</html>
<?php
close_mysql_connection();
?>

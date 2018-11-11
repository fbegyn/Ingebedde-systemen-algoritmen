<?php  
  require_once 'config.php';
  require_once 'func.php';

  $mysqli = initialize_mysql_connection();

  try{
    if(isset($_GET['from_lat'])) $from_lat = $_GET['from_lat']; else throw new Exception("Input Error: from_lat not set", 1);
    if(isset($_GET['from_lon'])) $from_lon = $_GET['from_lon']; else throw new Exception("Input Error: from_lon not set", 2);
    if(isset($_GET['to_lat'])) $to_lat = $_GET['to_lat']; else throw new Exception("Input Error: to_lat not set", 3);
    if(isset($_GET['to_lon'])) $to_lon = $_GET['to_lon']; else throw new Exception("Input Error: to_lon not set", 4);
    if(isset($_GET['transport'])) $transport = $_GET['transport']; else throw new Exception("Input Error: transport not set", 5);

    checkLonLat($from_lat, $from_lon, $to_lat, $to_lon); // check for out of bound

    $data = json_dijkstra($_GET['from_lat'], $_GET['from_lon'], $_GET['to_lat'], $_GET['to_lon'], $_GET['transport']);
  }
  catch(Exception $e){
    echo "<p>".$e->getMessage()."</p>";
    exit();
  }

  $dijkstra = json_decode($data);

  $pathlength = count($dijkstra->path);
  $lats = array();
  $lons = array();

  function getLonLat($node_id){
    global $mysqli;
    $sql = "SELECT lat, lon
            FROM `osm_nodes`
            WHERE `id` = '$node_id'";
    $retval = $mysqli->query($sql);
    $lonlat = $retval->fetch_assoc();
    return $lonlat;
  }  

  for ($i = 0; $i < $pathlength; $i++) {
    $lonlat = getLonLat($dijkstra->path[$i]);
    array_push($lats, $lonlat['lat']);
    array_push($lons, $lonlat['lon']);
  }
?>
<!DOCTYPE html>
<html>
<head>
	<title>Labo 5 plotted</title>
	<meta charset="utf-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<link rel="shortcut icon" type="image/x-icon" href="docs/images/favicon.ico" />
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.3.4/dist/leaflet.css" integrity="sha512-puBpdR0798OZvTTbP4A8Ix/l+A4dHDD0DGqYW6RQ+9jxkRFclaxxQb/SJAWZfWAkuyeQUytO7+7N4QKrDh+drA==" crossorigin=""/>
  <script src="https://unpkg.com/leaflet@1.3.4/dist/leaflet.js" integrity="sha512-nMMmRyTVoLYqjP9hrbed9S+FzjZHW5gY1TWCHA5ckwXZBadntCNs8kEqAWdrb9O7rxbCaA4lKTIWjDXZxflOcA==" crossorigin=""></script>

  <script>
  function initialize(){
    // Get search params
    var url = window.location.href;
    var hash = url.substring(url.indexOf("?")+1);
    var params = new URLSearchParams(hash);
    // Make a map
    var map = L.map('mapid').setView([51.0493022, 3.7174243], 14);
    L.tileLayer('https://api.tiles.mapbox.com/v4/{id}/{z}/{x}/{y}.png?access_token=pk.eyJ1IjoibWFwYm94IiwiYSI6ImNpejY4NXVycTA2emYycXBndHRqcmZ3N3gifQ.rJcFIG214AriISLbB6B5aw', {
      maxZoom: 20,
      attribution: 'Map data &copy; <a href="https://www.openstreetmap.org/">OpenStreetMap</a> contributors, ' +
        '<a href="https://creativecommons.org/licenses/by-sa/2.0/">CC-BY-SA</a>, ' +
        'Imagery © <a href="https://www.mapbox.com/">Mapbox</a>',
      id: 'mapbox.streets'
    }).addTo(map);
    
    // Get the coordinates
    var PathCoordinates = [];
    <?php
      for ($i = 0; $i < $pathlength; $i++) {
        echo "PathCoordinates.push([";
        echo $lats[$i];
        echo ",";
        echo $lons[$i];
        echo "]);\n\t";
      }
    ?>  
    // Draw the path
    L.polyline(PathCoordinates).addTo(map);
  }
  </script>

</head>
<body>
  <div id="mapid" style="width: 900px; height: 700px;">
  <script>
  initialize();
  map.on('click', function(e) {
    alert("Lat, Lon : " + e.latlng.lat + ", " + e.latlng.lng)
  });
  </script>
  </div>
</body>
</html>



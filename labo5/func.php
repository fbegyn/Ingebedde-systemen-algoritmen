<?php

/*
	author Maarten Slembrouck <maarten.slembrouck@gmail.com>
*/

function initialize_mysql_connection(){
	global $servername;
	global $username;
	global $password;
	global $dbname;

	// Create connection
	$mysqli = new mysqli($servername, $username, $password, $dbname);

	if ($mysqli->connect_errno) {
		echo "Sorry, this website is experiencing problems.";
		echo "Error: Failed to make a MySQL connection, here is why: \n";
		echo "Errno: " . $mysqli->connect_errno . "\n";
		echo "Error: " . $mysqli->connect_error . "\n";
		exit;
	}
        return $mysqli;
}

function close_mysql_connection(){
	global $mysqli;
	$mysqli->close();
}

function getMinMaxLatLon(){
  global $mysqli;
  $sql = "SELECT MIN( lat ) lat_min, MAX( lat ) lat_max, MIN( lon ) lon_min, MAX( lon ) lon_max FROM  `osm_nodes`";
  $retval = $mysqli->query($sql);
  if($retval && $row = $retval->fetch_assoc()){
    return array($row['lat_min'], $row['lat_max'], $row['lon_min'], $row['lon_max']);
  }
  else{
    return null;
  }
}

function checkLonLat($from_lat, $from_lon, $to_lat, $to_lon){
  $latlonbounds = getMinMaxLatLon();
  if($from_lat < $latlonbounds[0] || $from_lat > $latlonbounds[1]){
    throw new Exception("Input Error: from_lat out of bound", 6);
  }
  else if($from_lon < $latlonbounds[2] || $from_lon > $latlonbounds[3]){
    throw new Exception("Input Error: from_lon out of bound", 7);
  }
  if($to_lat < $latlonbounds[0] || $to_lat > $latlonbounds[1]){
    throw new Exception("Input Error: to_lat out of bound", 8);
  }
  else if($to_lon < $latlonbounds[2] || $to_lon > $latlonbounds[3]){
    throw new Exception("Input Error: to_lon out of bound", 9);
  }
}

function distance($lat1,$lon1,$lat2,$lon2){
  $delta_lat = $lat2-$lat1;
  $delta_lon = $lon2-$lon1;
  return (($delta_lat * $delta_lat) + ($delta_lon * $delta_lon));
}

class Node{
  public $id;
  function getNeighs(){
    $this->neighs = getNeighbours($this->id);
  }
}

function getCoord($path){
  $coords = [];
	foreach($path as $step){
    array_push($coords,getNode($step)); 
  }
  return $coords;
}

function getNode($id){
  global $mysqli;
  $sql = "SELECT id,lat,lon,name FROM cities
          WHERE id=$id";
  $result = $mysqli->query($sql);
  while ($result && $row = $result->fetch_assoc()) {
    $node = new Node;
    $node->lat = (float)$row['lat'];
    $node->lon = (float)$row['lon'];
    $node->id = $row['id'];
  //  $node->name = $row['name'];
  }
  return $node;
}

function getNodeId($from_lat, $from_lon, $transport){
  // find the closest node_id to ($from_lat, $from_lon) on a way
  global $mysqli;
  $offset = 0.5;
  switch ($transport){
  case "foot":
    $sql = "SELECT * FROM city_connections AS a
            RIGHT JOIN cities AS b
            ON a.node_id = b.id
            WHERE access_walk = 1
            AND lat < $from_lat+$offset AND lat > $from_lat-$offset
            AND lon < $from_lon+$offset AND lon > $from_lon-$offset";
  case "bicycle":
    $sql = "SELECT * FROM city_connections AS a
            RIGHT JOIN cities AS b
            ON a.node_id = b.id
            WHERE access_bike = 1
            AND lat < $from_lat+$offset AND lat > $from_lat-$offset
            AND lon < $from_lon+$offset AND lon > $from_lon-$offset";
  case "car":
    $sql = "SELECT * FROM city_connections AS a
            RIGHT JOIN cities AS b
            ON a.node_id = b.id
            WHERE access_drive = 1
            AND lat < $from_lat+$offset AND lat > $from_lat-$offset
            AND lon < $from_lon+$offset AND lon > $from_lon-$offset";
  }
  $result = $mysqli->query($sql);
  $closest = INF;
  $closeNode = new Node;
  $nodes = [];
  while ($result && $row = $result->fetch_assoc()) {
    $node = new Node;
    $node->id = $row['id'];
    $node->lat = (float)$row['lat'];
    $node->lon = (float)$row['lon'];
//    $node->name = $row['name'];
    array_push($nodes,$node);

    $dist= distance($from_lat,$from_lon,$node->lat,$node->lon);
    if ($dist < $closest) {
      $closest = $dist;
      $closeNode = $node;
    }
  }
  return $closeNode->id;
}

// Get all neighbour nodes for current node
function getNeighbours($nodeId, $transport){
  global $mysqli;
  // Fetch all neighbours from the database with matching transport
  switch ($transport){
  case "foot":
    $sql = "SELECT node_id, neighbour_id, distance FROM city_connections
            WHERE node_id = $nodeId
            AND access_walk = 1";
  case "bicycle":
    $sql = "SELECT node_id, neighbour_id, distance FROM city_connections
            WHERE node_id = $nodeId
            AND access_bike = 1";
  case "car":
    $sql = "SELECT node_id, neighbour_id, distance FROM city_connections
            WHERE node_id = $nodeId
            AND access_drive = 1";
  }
  $result = $mysqli->query($sql);
  $nodes= [];
  while ($result && $row = $result->fetch_assoc()) {
    $node = new Node;
    $node->to = $row['neighbour_id'];
    $node->id = $row['node_id'];
    $node->distance = $row['distance'];
    array_push($nodes,$node);
  }
  return $nodes;
}

function getVerteces($from_node, $transport){
  global $mysqli;
  // Starting node
  $node = getNode($from_node);
  // Fetch all neighbours from the database
  $offset = 0.2;
  switch ($transport){
  case "foot":
    $sql = "SELECT DISTINCT node_id FROM city_connections AS a
            RIGHT JOIN cities AS b
            ON a.node_id = b.id
            WHERE access_walk = 1
            AND lat < $node->lat+$offset AND lat > $node->lat-$offset
            AND lon < $node->lon+$offset AND lon > $node->lon-$offset";
  case "bicycle":
    $sql = "SELECT DISTINCT node_id FROM city_connections AS a
            RIGHT JOIN cities AS b
            ON a.node_id = b.id
            WHERE access_bike = 1
            AND lat < $node->lat+$offset AND lat > $node->lat-$offset
            AND lon < $node->lon+$offset AND lon > $node->lon-$offset";
  case "car":
    $sql = "SELECT DISTINCT node_id FROM city_connections AS a
            RIGHT JOIN cities AS b
            ON a.node_id = b.id
            WHERE access_drive = 1
            AND lat < $node->lat+$offset AND lat > $node->lat-$offset
            AND lon < $node->lon+$offset AND lon > $node->lon-$offset";
  }
  $result = $mysqli->query($sql);
  $nodes = [];
  while ($result && $row = $result->fetch_assoc()) {
    $node = new Node;
    $node->id = $row['node_id'];
    array_push($nodes,$node);
  }
  return $nodes;
}

function getShortestPathDijkstra($from_node, $to_node, $transport){
  global $mysqli;
  // Variable initialisation
  $dist = [];
  $prev = [];
  $visited = [];
  $path = [];
  $verteces = getVerteces($from_node, $transport); // vertex aka node

  // Build cost matrix and set distances from source to $vertex to INF
  foreach($verteces as $vertex){
    $dist[$vertex->id] = INF;
  }
  // Set source -> source to 0 cost
  $dist[$from_node] = 0;
  
  // Searching for the paths
  while (empty($verteces) == false){
    // Find minimal distance in subset of verteces
    $u;
    $min = INF;
    foreach ($verteces as $node){
      if ($dist[$node->id] < $min){
        $u = $node->id;
      }
    }

    // unset vertex matched from u
    for($i = 0; $i < count($verteces); $i++){
      if ($verteces[$i]->id == $u){
        array_push($visited,$verteces[$i]);
        array_splice($verteces,$i,1);
        break;
      }
    }

    // We only care about source -> target
    if ($u == $to_node){
      $u = $to_node;
      while(isset($prev[$u]) or ($u <> $from_node)){
        array_push($path,$u);
        $u = $prev[$u];
      }
      break;
    }

    // Fetch neighbours
    $neighs = getNeighbours($u,$transport);
    // Start looking at the neighbours of the node
    foreach ($neighs as $neigh){
      $alt = $dist[$u] + $neigh->distance;

      

      if (!isset($dist[$neigh->to])){
        $new = getNode($neigh->id);
        $dist[$neigh->to] = INF;
        print_r($new);
      //  $new = array_udiff($new, $visited,"compareNodes");
        array_push($verteces,$new);
      //  foreach($new as $vertex){
      //    $dist[$vertex->id] = INF;
      //  }
      }

      if ($alt < $dist[$neigh->to]){
        $dist[$neigh->to] = $alt;
        $prev[$neigh->to] = $u;
      }
    }
  }
  print_r($dist);
  array_push($path,$from_node);
  $path = array_reverse($path);

  return array($dist[$to_node], $path);
}

function compareNodes($a, $b){
  return $a === $b ? 0 : -1;
}

function json_dijkstra($from_lat, $from_lon, $to_lat, $to_lon, $transport){
  $from_node = getNodeId($from_lat, $from_lon, $transport); // complete implementation in func.php
  $to_node = getNodeId($to_lat, $to_lon, $transport);

  // To think about: what if there is no path between from_node and to_node?
  // add a piece of code here (after you have a working Dijkstra implementation)
  // which throws an error if no path could be found -> avoid that your algorithm visits all nodes in the database

  list($distance,$path) = getShortestPathDijkstra($from_node, $to_node, $transport); // complete implementation in func.php
  $coords = getCoord($path);

  // throw new Exception("Error: ...");

  $output = array(
      "from_node" => $from_node,
      "to_node" => $to_node,
      "path" => $path,
      "loc" => $coords,
      "distance" => $distance
  );

  return json_encode($output);
}

?>

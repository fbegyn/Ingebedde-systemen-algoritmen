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
  return (($delta_lat * $delta_lat) - ($delta_lon * $delta_lon));
}

class Node{
  public $id;
  public $passed = 0;
}

function getNodeId($from_lat, $from_lon){
  // find the closest node_id to ($from_lat, $from_lon) on a way
  global $mysqli;
  $offset = 0.1;
  $sql = "SELECT id,lat,lon,name FROM cities
          WHERE lat < $from_lat+$offset AND lat > $from_lat-$offset
          AND lon < $from_lon+$offset AND lon > $from_lon-$offset";
  $result = $mysqli->query($sql);
  $closest = 10000000;
  $closeNode = new Node;
  $nodes = [];
  while ($result && $row = $result->fetch_assoc()) {
    $node = new Node;
    $node->lat = $row['lat'];
    $node->lon = $row['lon'];
    $node->id = $row['id'];
    $node->name = $row['name'];
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
function getNeighbours($nodeId){
  global $mysqli;
  // Fetch all neighbours from the database
  $sql = "SELECT * FROM city_connections
          WHERE node_id = $nodeId";
  $result = $mysqli->query($sql);
  $nodes = [];
  while ($result && $row = $result->fetch_assoc()) {
    $node = new Node;
    $node->id = $row['neighbour_id'];
    $node->distance = $row['distance'];
    $node->access_walk = $row['access_walk'];
    $node->access_bike = $row['access_bike'];
    $node->access_drive = $row['access_drive'];
    array_push($nodes,$node);
  }
  return $nodes;
}

function getShortestPathDijkstra($from_node, $to_node, $transport){
  global $mysqli;
  // Variable initialisation
  $matrix = array(array());
  $curNode = new Node;
  $curNode->id = $from_node;
  $curNode->distance = 0;
  $close = new Node;
  $close->distance = 10000000;
  // Path variable
  $path = [];
  array_push($path,$from_node);
  // Search untill we reach the to_node
  while($curNode->id <> $to_node){
    $neighs = getNeighbours($curNode->id);
    foreach( $neighs as $neigh ){
      if ($neigh->id == $curNode->id){
        break;
      }
      $matrix[$curNode->id][$neigh->id] = new Node;
      $matrix[$curNode->id][$neigh->id]->distance = $curNode->distance + $neigh->distance;
      $matrix[$curNode->id][$neigh->id]->id = $neigh->id;
    }
    foreach($matrix as $a){
      foreach($a as $b){
        if (($b->distance < $close->distance) && ($b->passed==0) && ($b->id <> $from_node)){
          $close = $b;
        }
      }
    }
    $curNode = $close;
    array_push($path,$curNode->id);
    //if (end($path) == $from_node){
    //  $temp = end($path);
    //  $path = [];
    //  array_push($path,$temp);
    //}
    $close->passed=1;
    $close = clone($close);
    $close->distance=100000;
  }
  print_r($matrix);

  $distance = $curNode->distance;
  return array($distance, $path);
}

function json_dijkstra($from_lat, $from_lon, $to_lat, $to_lon, $transport){
  $from_node = getNodeId($from_lat, $from_lon); // complete implementation in func.php
  $to_node = getNodeId($to_lat, $to_lon);

  // To think about: what if there is no path between from_node and to_node?
  // add a piece of code here (after you have a working Dijkstra implementation)
  // which throws an error if no path could be found -> avoid that your algorithm visits all nodes in the database

  list($distance,$path) = getShortestPathDijkstra($from_node, $to_node, $transport); // complete implementation in func.php

  // throw new Exception("Error: ...");

  $output = array(
      "from_node" => $from_node,
      "to_node" => $to_node,
      "path" => $path,
      "distance" => $distance
  );

  return json_encode($output);
}

?>

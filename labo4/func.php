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

function getNodeId($from_lat, $from_lon){
  // find the closest node_id to ($from_lat, $from_lon) on a way
  return 31813790;
}

function getShortestPathDijkstra($from_node, $to_node, $transport){
  // find the shortest path between the two given nodes, using osm_node_neighbours
  $path = array($from_node);
  // fill in the $path variable
  // also return the $distance variable
  $path[] = 31813803;
  $path[] = 31813811;
  $path[] = 31813815;
  $path[] = 31813827;
  //$path[] = $to_node;
  $distance = 3246.146;
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

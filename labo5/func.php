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

function getNodeId($from_lat, $from_lon, $transport){
  // find the closest node_id to ($from_lat, $from_lon) on a way
  global $mysqli;
  $offset = 0.5;
  switch ($transport){
  case "foot":
    $sql = "SELECT id, (lat-{$from_lat})*(lat-{$from_lat}) + (lon - {$from_lon})*(lon - {$from_lon}) AS x FROM osm_nodes
           	WHERE lat < $from_lat+$offset AND lat > $from_lat-$offset
            AND lon < $from_lon+$offset AND lon > $from_lon-$offset
						ORDER BY x ASC LIMIT 1";
  case "bicycle":
    $sql = "SELECT id, (lat-{$from_lat})*(lat-{$from_lat}) + (lon - {$from_lon})*(lon - {$from_lon}) AS x FROM osm_nodes
            WHERE lat < $from_lat+$offset AND lat > $from_lat-$offset
            AND lon < $from_lon+$offset AND lon > $from_lon-$offset
						ORDER BY x ASC LIMIT 1";
  case "car":
    $sql = "SELECT id, (lat-{$from_lat})*(lat-{$from_lat}) + (lon - {$from_lon})*(lon - {$from_lon}) AS x FROM osm_nodes
            WHERE lat < $from_lat+$offset AND lat > $from_lat-$offset
            AND lon < $from_lon+$offset AND lon > $from_lon-$offset
						ORDER BY x ASC LIMIT 1";
  }
	$arr = array();
  $result =  $mysqli->query($sql);
  while($result && $row = $result->fetch_assoc()){
    $arr[] = $row;
  }
  return $arr[0]['id'];
}

function costMatrix($transport){
  $mysqli = initialize_mysql_connection();
  $costM = array();
	switch ($transport){
	case "foot":
    $distance_info = $mysqli->query("SELECT DISTINCT a.node_id, a.neighbour_id, a.distance from osm_node_neighbours_walk AS a");
	case "bicycle":
    $distance_info = $mysqli->query("SELECT DISTINCT a.node_id, a.neighbour_id, a.distance from osm_node_neighbours_cycle AS a");
  case "car":
    $distance_info = $mysqli->query("SELECT DISTINCT a.node_id, a.neighbour_id, a.distance from osm_node_neighbours_drive AS a");
  }
  while($row = $distance_info->fetch_assoc()){
    $costM[$row['node_id']][$row['neighbour_id']] = $row['distance'];
  }
  return $costM;
}

function getShortestPathDijkstra($a, $b, $transport){
	// Create costMatrix
  $_distArr = costMatrix($transport);
	//initialize the array for storing
	$S = array();//the nearest path with its parent and weight
	$Q = array();//the left nodes without the nearest path
	foreach(array_keys($_distArr) as $val) $Q[$val] = INF;
	$Q[$a] = 0;

	if (!array_key_exists($b, $S)) {
    echo "Found no way.";
    return;
	}

	//start calculating
	while(!empty($Q)){
			$min = array_search(min($Q), $Q);//the most min weight
			if($min == $b) break;
			foreach($_distArr[$min] as $key=>$val) if(!empty($Q[$key]) && $Q[$min] + $val < $Q[$key]) {
					$Q[$key] = $Q[$min] + $val;
					$S[$key] = array($min, $Q[$key]);
			}
			unset($Q[$min]);
	}

	//list the path
	$path = array();
	$pos = $b;
	while($pos != $a){
			$path[] = $pos;
			$pos = $S[$pos][0];
	}
	$path[] = $a;
	$path = array_reverse($path);
	
	return array($S[$to_node][1],$path);
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

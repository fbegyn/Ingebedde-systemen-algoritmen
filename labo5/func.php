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
  $offset = 0.00069;
  switch ($transport){
  case "foot":
    $sql = "SELECT id, (lat-{$from_lat})*(lat-{$from_lat}) + (lon - {$from_lon})*(lon - {$from_lon}) AS x FROM osm_road_nodes
      WHERE lat < $from_lat+$offset AND lat > $from_lat-$offset
      AND lon < $from_lon+$offset AND lon > $from_lon-$offset
      ORDER BY x ASC LIMIT 1";
  case "bicycle":
    $sql = "SELECT id, (lat-{$from_lat})*(lat-{$from_lat}) + (lon - {$from_lon})*(lon - {$from_lon}) AS x FROM osm_road_nodes
      WHERE lat < $from_lat+$offset AND lat > $from_lat-$offset
      AND lon < $from_lon+$offset AND lon > $from_lon-$offset
      ORDER BY x ASC LIMIT 1";
  case "car":
    $sql = "SELECT id, (lat-{$from_lat})*(lat-{$from_lat}) + (lon - {$from_lon})*(lon - {$from_lon}) AS x FROM osm_road_nodes
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
    $res = $mysqli->query("SELECT DISTINCT a.node_id, a.neighbour_id, a.distance from osm_node_neighbours_walk AS a");
    break;
  case "bicycle":
    $res = $mysqli->query("SELECT DISTINCT a.node_id, a.neighbour_id, a.distance from osm_node_neighbours_bike AS a");
    break;
  case "car":
    $res = $mysqli->query("SELECT DISTINCT a.node_id, a.neighbour_id, a.distance from osm_node_neighbours_drive AS a");
    break;
  default:
    print_r("Unknown transport mode.\n");
  }
  while($res && $row = $res->fetch_assoc()){
    $costM[$row['node_id']][$row['neighbour_id']] = $row['distance'];
  }
  return $costM;
}

function getShortestPathDijkstra($a, $b, $transport){
  // Create costMatrix
  $_distArr = costMatrix($transport);
  //initialize the array for storing
  $Trail = array();     // Trail with shortest distances to node
  $Cost = array();      // cost of start -> node
  $GoalCost = array();  // cost of start -> goal

  $closed = array();
  $opened = array();

  foreach(array_keys($_distArr) as $val){
    $Cost[$val] = INF;
    $GoalCost[$val] = INF;
  }
  $Cost[$a] = 0;

  // No path exists between nodes, stop
  if (!array_key_exists($b, $Cost)) {
    return;
  }

  $closed = array();

  //start calculating
  while(!empty($Cost)){
    $min = array_search(min($Cost), $Cost); // the most min weight
    if($min == $b) break;                   // If next node is target, stop

    $closed[] = $min;

    foreach($_distArr[$min] as $key=>$val) if(!empty($Cost[$key]) && $Cost[$min] + $val < $Cost[$key]) {
      if (array_key_exists($val,$closed)) continue;
      $tentative_Cost = $Cost[$min] + $val; 
      if (!array_key_exists($val,$closed)) print_r("fetch new node");
      else if ($tentative_Cost >= $Cost[$key]){
        $Cost[$key] = $Cost[$min] + $val;
        $Trail[$key] = array($min, $Cost[$key]);
        $GoalCost[$key] = $Cost[$key] + 1;
      }
    }  
    unset($Cost[$min]);

    // We found the target, time to stop
    if (array_key_exists($b,$Trail)){
      break;
    }
  }

  // No path exists between nodes, stop
  if (!array_key_exists($b, $Trail)) {
    return;
  }

  //list the path
  $path = array();
  $pos = $b;
  while($pos != $a){
    $path[] = $pos;
    $pos = $Trail[$pos][0];
  }
  $path[] = $a;
  $path = array_reverse($path);

  return array($Trail[$b][1],$path);
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

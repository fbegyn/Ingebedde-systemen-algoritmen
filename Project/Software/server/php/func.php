<?php

/*
  author Maarten Slembrouck <maarten.slembrouck@gmail.com>
 */

// Open connection the mysql DB
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

function getPathNodes($path){
  global $mysqli;
  $nodes = array();
  foreach ($path as $n) {
    $node = getNode($n);
    $nodes[] = [getNode($n)['lat'],$node['lon']];
  }
  return $nodes;
}

// Graduately close the DB connection
function close_mysql_connection(){
  global $mysqli;
  $mysqli->close();
}

// Get the bounding box of the map
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

// check if the location is in the  bounding box
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

// Calculate the distance between 2 locations
function distance($lat1,$lon1,$lat2,$lon2){
  $delta_lat = $lat2-$lat1;
  $delta_lon = $lon2-$lon1;
  return (($delta_lat * $delta_lat) + ($delta_lon * $delta_lon));
}

// Fetch the node fro the database
function getNode($nodeId){
  global $mysqli;
  $sql = "SELECT id, lat, lon FROM osm_nodes
          WHERE id = $nodeId
          LIMIT 1";
  $result =  $mysqli->query($sql);
  while($result && $row = $result->fetch_assoc()){
    $n = $row;
  }
  return $n ?? null;
}

function getNodes($nodeId){
  global $mysqli;
  $offset = 0.001;
  $n = getNode($nodeId);
  $from_lat = $n['lat'];
  $from_lon = $n['lon'];
  $nodes = array();
  $sql = "SELECT id, lat, lon FROM osm_nodes
          WHERE lat < $from_lat+$offset AND lat > $from_lat-$offset
          AND lon < $from_lon+$offset AND lon > $from_lon-$offset";
  $result =  $mysqli->query($sql);
  while($result && $row = $result->fetch_assoc()){
    $nodes[] = $row;
  }
  return $nodes;
}

function getNodeCache($id, $cache){
  foreach ($cache as $n){
    if ($n['id'] == $id) {
      return $n;
    }
  }
}

// Calculate the distance between 2 nodes
function nodeDist($node1, $node2, $cache){
  $n1 = getNodeCache($node1, $cache);
  $n2 = getNodeCache($node2, $cache);
  return 3*distance($n1['lat'],$n1['lon'],$n2['lat'],$n2['lon']);
}

// Compare 2 nodes
function compareNodes($a, $b){
  return $a === $b ? 0 : -1;
}

// Get the closest node to the lcoation matching th eminrent transport
function getNodeId($from_lat, $from_lon, $transport){
  // find the closest node_id to ($from_lat, $from_lon) on a way
  global $mysqli;
  $offset = 0.00069;
  switch ($transport){
  case "foot":
    $sql = "SELECT id, (lat-{$from_lat})*(lat-{$from_lat}) + (lon - {$from_lon})*(lon - {$from_lon}) AS x FROM osm_node_neighbours_2
      WHERE lat < $from_lat+$offset AND lat > $from_lat-$offset
      AND lon < $from_lon+$offset AND lon > $from_lon-$offset
      AND access_walk = 1
      ORDER BY x ASC LIMIT 1";
      break;
  case "bicycle":
    $sql = "SELECT id, (lat-{$from_lat})*(lat-{$from_lat}) + (lon - {$from_lon})*(lon - {$from_lon}) AS x FROM osm_node_neighbours_2
      WHERE lat < $from_lat+$offset AND lat > $from_lat-$offset
      AND lon < $from_lon+$offset AND lon > $from_lon-$offset
      AND access_bike = 1
      ORDER BY x ASC LIMIT 1";
      break;
  case "car":
    $sql = "SELECT id, (lat-{$from_lat})*(lat-{$from_lat}) + (lon - {$from_lon})*(lon - {$from_lon}) AS x FROM osm_node_neighbours_2
      WHERE lat < $from_lat+$offset AND lat > $from_lat-$offset
      AND lon < $from_lon+$offset AND lon > $from_lon-$offset
      AND access_drive = 1
      ORDER BY x ASC LIMIT 1";
      break;
  }
  $arr = array();
  $result =  $mysqli->query($sql);
  while($result && $row = $result->fetch_assoc()){
    $arr[] = $row;
  }
  return (int) $arr[0]['id'];
}

// Get the neighbours of the node
function getNeigh($nodeId, $transport){
  global $mysqli;
  $costM = array();
  switch ($transport){
  case "foot":
    $sql = "SELECT DISTINCT neighbour_id, distance FROM osm_node_neighbours_walk WHERE node_id = $nodeId";
    break;
  case "bicycle":
    $sql = "SELECT DISTINCT neighbour_id, distance FROM osm_node_neighbours_cycle WHERE node_id = $nodeId";
    break;
  case "car":
    $sql = "SELECT DISTINCT neighbour_id, distance FROM osm_node_neighbours_drive WHERE node_id = $nodeId";
    break;
  }
  $res = $mysqli->query($sql);
  while($res && $row = $res->fetch_assoc()){
    $costM[$row['neighbour_id']] = $row['distance'];
  }
  return $costM ?? null;
}

// Build path the min node based on cameFrom
function buildPath($cameFrom, $min){
  $path = array();
  $path[] = $min;
  while(in_array($min,array_keys($cameFrom))){
    $min = $cameFrom[$min];
    $path[] = $min;
  }
  return array_reverse($path);
}

// Path finding algorithm
function getAStar($start, $end, $transport){
  $cache = getNodes($start);

  $endNode = getNode($end);
  $closed = array(); // evaluated nodes
  $open = array(); // discovererd unevaluated nodes
  $open[] = $start;

  $G = array(); // cost of getting from start to $Key
  $G[$start] = 0;
  $F = array(); // cost of getting from start to end while passing $key node
  $F[$start] = nodeDist($start, $end, $cache);

  $cameFrom = array(); // array that has the most efficient path to the $key

  while(!empty($open)){
    $min = array_values($open)[0];
    foreach($open as $v){
      if($F[$v] < $F[$min]){
        $min = $v;
      }
    }
    $closed[] = $min; // Add node to visited set
    // Remove visted node from open set
    $k = array_search($min,$open);
    unset($open[$k]);

    // If min is the end node, reconstruct the path
    if ($min == $end) break;
    // Loop for neighbours
    $neighs = getNeigh($min,$transport);
    if ($neighs == null){
      break;
    }
    foreach($neighs as $neigh => $dist){
			//if (!in_array($neigh,$cache)){
      //  $cache = getNodes($neigh);
			//}

      if (in_array($neigh,$closed)){
        continue;
      }
      // distance from start to neigh
      $tentG = $G[$min] + $dist;
      if (!in_array($neigh, $open)) $open[] = $neigh;
      else if ($tentG >= $G[$neigh]) continue;

      $cameFrom[$neigh] = $min;
      $G[$neigh] = $tentG;
      $F[$neigh] = $G[$neigh] + (nodeDist($neigh,$end, $cache)*100);
    }
  }

  $path = buildPath($cameFrom,$end);

  return array(round($G[$end],4),$path);
}

function json_routing($from_lat, $from_lon, $to_lat, $to_lon, $transport){
  $from_node = getNodeId($from_lat, $from_lon, $transport); // complete implementation in func.php
  $to_node = getNodeId($to_lat, $to_lon, $transport);

  // To think about: what if there is no path between from_node and to_node?
  // add a piece of code here (after you have a working Dijkstra implementation)
  // which throws an error if no path could be found -> avoid that your algorithm visits all nodes in the database

  //list($distance,$path) = getShortestPathDijkstra($from_node, $to_node, $transport); // complete implementation in func.php
  list($distance,$path) = getAStar($from_node, $to_node, $transport); // complete implementation in func.php
  $nodes = getPathNodes($path);
  // throw new Exception("Error: ...");

  $output = array(
    "from_node" => $from_node,
    "to_node" => $to_node,
    "path" => $path,
    "nodes" => $nodes,
    "distance" => $distance
  );
  return json_encode($output);
}
?>

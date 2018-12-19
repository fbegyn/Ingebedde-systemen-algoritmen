<?php
/*
  author Maarten Slembrouck <maarten.slembrouck@ugent.be>
 */

include 'config.php'; // don't forget to fill in your the right values in this file!
include 'func.php';

//header("Content-Type: text/plain");

$create_way_node_classification_table = true;

echo "Script started\n";

$mysqli = initialize_mysql_connection();

function add_row_conditional($node_id,$neighbour_id, $access_walk, $access_bike, $access_drive){
  global $mysqli;

  // construct a query to add the row, keep in mind that the row might already exist, so check this before you add the new one. If it exist, update the existing query accordingly
  $sql = "INSERT INTO node_neighbours (node_id, neighbour_id, access_walk, access_bike, access_drive)
    VALUES ($node_id,$neighbour_id, $access_walk, $access_bike, $access_drive)";

  if ($mysqli->query($sql) === TRUE) {
    echo "New record created successfully\n";
  } else {
    echo "Error: " . $sql . "<br>" . $mysqli->error . "\n";
  }
}

function calculate_distance($long1, $long2, $lati1, $lati2){
  $dLon = deg2rad($long2 - $long1);
  $dLat = deg2rad($lati2 - $lati1);
  $lat1 = deg2rad($lati1);
  $lat2 = deg2rad($lati2);
  $lon1 = deg2rad($long1);
  $lon2 = deg2rad($long2);
  $a = sin($dLat/2) * sin($dLat/2) + sin($dLon/2) * sin($dLon/2) * cos($lat1) * cos($lat2);
  return 6371*2000*atan(sqrt($a)/sqrt(1-$a));
}


if($create_way_node_classification_table){
  echo "Creating 'node_neighbours' ...\n";
  $sql = "DROP TABLE node_neighbours";
  if(!$mysqli->query($sql)){
    echo "Unable to execute '$sql'\n";
  }
  // create new table
  $sql = "CREATE TABLE node_neighbours (
    node_id BIGINT,
    neighbour_id BIGINT,
    access_walk INT(1),
    access_bike INT(1),
    access_drive INT(1),
    distance FLOAT
  )";
  if(!$mysqli->query($sql)){
    echo "Unable to execute '$sql'\n";
  }
  echo "finished\n";
}

$sql = 'SELECT way_id, node_id, highway, bicycle, oneway FROM temp_ways';
$res = $mysqli->query($sql);

/*
Pedestrian access is always considered to be two way (if not on motorway), so when oneway is set to true, we add the neighbour node_id also in the opposite direction and put access to 001
 */
$current_way_id = -1;
$current_way_type = "";
$access_walk = 0;
$access_bike = 0;
$access_drive = 0;
$current_way_oneway = false;
$current_way_nodes = array();
while($res && $row = $res->fetch_assoc()){
  print_r($row);
  if($row['way_id'] != -1){
    // Drive access determination
    if(!in_array($row['highway'],array("pedestrian","footway","steps","path","cycleway"))){
      $access_drive=1;
    } else {
      $acces_drive = 0;
    }
    // Bike  access determination
    if(in_array($row['highway'],array("cycleway"))){
      $access_bike=1;
    } else if(!in_array($row['highway'],array("motorway","trunk","primary"))){
      $access_bike=1;
    } else {
      $acces_bike= 0;
    }
    // Walk access determination
    if(!in_array($row['highway'],array("motorway"))){
      $access_walk=1;
    } else {
      $access_walk=0;
    }
    for($i = 1; $i < count($current_way_nodes); $i++){
      add_row_conditional($current_way_nodes[$i-1],$current_way_nodes[$i],$access_walk,$access_bike,$access_drive);
    }
    //nog dubbelchecken
    $current_way_id = $row['way_id'];
    $current_way_type = $row['highway'];
    $current_way_bicycle_allowed = !in_array($current_way_type, array("motorway","motorway_link")) && (in_array($current_way_type,array("cycleway")) || (!is_null($row['bicycle']) && $row['bicycle']=="yes"));
    $current_way_oneway = !is_null($row['highway']) && ($row['oneway'] == "yes");
    $current_way_nodes = array($row['node_id']);
  }
  else{
    $current_way_nodes[] = $row['node_id'];
  }
}


echo "finished".PHP_EOL;
echo "Script finished";
close_mysql_connection();

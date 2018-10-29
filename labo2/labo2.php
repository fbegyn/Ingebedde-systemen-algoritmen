<?php
/*
	author Maarten Slembrouck <maarten.slembrouck@ugent.be>
*/

include 'config.php'; // don't forget to fill in your the right values in this file!
include 'func.php';

header("Content-Type: text/plain");

$create_tmp_table = false;
$create_way_node_classification_table = true;

echo "Script started".PHP_EOL;

$mysqli = initialize_mysql_connection();

function add_row_conditional($node_id,$neighbour_id, $access_walk, $access_bike, $access_drive){
    global $mysqli;

    // construct a query to add the row, keep in mind that the row might already exist, so check this before you add the new one. If it exist, update the existing query accordingly
    $sql = 'INSERT INTO osm_node_neighbours (node_id, neighbour_id, access_walk, assess_bike, access_drive)
            VALUES ($node_id,$neighbour_id, $access_walk, $access_bike, $access_drive)
            ON DUPLICATE KEY UPDATE node_id = values($node_id)
                                    neighbour_id = values($neighbour_id)
                                    access_walk = values($access_walk)
                                    access_bike = values($access_bike)
                                    access_drive = values($access_drive)';

    if ($conn->query($sql) === TRUE) {
        echo "New record created successfully";
    } else {
        echo "Error: " . $sql . "<br>" . $conn->error;
    }
}


if($create_tmp_table){
    echo "Creating 'temp_way_node_classification' ...".PHP_EOL;
    echo "Dropping table ...";
    $sql = 'DROP TABLE temp_way_node_classification';
    if(!$mysqli->query($sql)){
        echo "Unable to execute '$sql'".PHP_EOL;
    }
    echo "Table dropped.";
    // complete the query to save the temporal query result, a table which can help you tremendously in creating the final result
    // tip: work with a subquery, which look like this: SELECT * FRON (SELECT * FROM )
    // tip: the finale column names are already given
    // use JOIN operations to couple osm_way_tags where you can test on k="highway", k="bicycle", k="oneway"
    echo "Creating ...";
    $sql = 'CREATE TABLE temp_way_node_classification
            SELECT wn.way_id way_id, wn.node_id node_id, highway, coalesce(bicycle,"no") bicycle, coalesce(oneway,"no") oneway, seq
            FROM osm_ways w
            JOIN osm_way_nodes wn ON w.id=wn.way_id
            INNER JOIN (SELECT way_id, v highway FROM osm_way_tags owt1 WHERE owt1.k="highway") wt ON w.id = wt.way_id
            INNER JOIN (SELECT way_id, v bicycle FROM osm_way_tags owt1 WHERE owt1.k="bicycle") wt1 ON w.id = wt1.way_id
            INNER JOIN (SELECT way_id, v oneway FROM osm_way_tags owt1 WHERE owt1.k="oneway") wt2 ON w.id = wt2.way_id';

    if(!$mysqli->query($sql)){
        echo "Unable to execute '$sql'".PHP_EOL;
    }
    echo "finished".PHP_EOL;
}

if($create_way_node_classification_table){
    echo "Creating 'osm_node_neighbours' ...".PHP_EOL;
    $sql = "DROP TABLE osm_node_neighbours";
    if(!$mysqli->query($sql)){
        echo "Unable to execute '$sql'".PHP_EOL;
    }
    // create new table
    $sql = "CREATE TABLE osm_node_neighbours (
        id BIGINT AUTO_INCREMENT PRIMARY KEY,
        node_id BIGINT,
        neighbour_id BIGINT,
        access_walk INT(1),
        access_bike INT(1),
        access_drive INT(1),
        distance FLOAT
    )";
    if(!$mysqli->query($sql)){
        echo "Unable to execute '$sql'".PHP_EOL;
    }
    echo "finished".PHP_EOL;
}

$sql = 'SELECT way_id way_id, highway, bicycle, oneway, node_id, seq FROM temp_way_node_classification';
$retval = $mysqli->query($sql);


/*
Pedestrian access is always considered to be two way (if not on motorway), so when oneway is set to true, we add the neighbour node_id also in the opposite direction and put access to 001
*/

$current_way_id = -1;
$current_way_type = "";
$current_way_bicycle_allowed = false;
$current_way_oneway = false;
$current_way_nodes = array();

echo "Filling up 'temp_way_node_classification' ...".PHP_EOL;
while($retval && $row = $retval->fetch_assoc()){
    if($row['way_id'] != $current_way_id){
    // construct add_row_conditional based on the columns in the current row, don't forget to add a two way connection in both directions (connection from node A to B, but also from node B to A)
	// initialize the new way
        $current_way_id = $row['way_id'];
        $current_way_type = $row['highway'];
        $current_way_bicycle_allowed = !in_array($current_way_type, array("motorway","motorway_link")) && (in_array($current_way_type,array("cycleway")) || (!is_null($row['bicycle']) && $row['bicycle']=="yes"));
        $current_way_oneway = !is_null($row['bicycle']) && ($row['bicycle'] == "yes");
        $current_way_nodes = array($row['oneway']);
        add_row_conditional($current_way_nodes[1],$current_way_nodes[2],true,$current_way_bicycle_allowed, $current_way_oneway);
    }
    else{
        $current_way_nodes[] = $row['oneway'];
    }
}
echo "finished".PHP_EOL;

echo "Script finished";
close_mysql_connection();

#!/bin/bash

FILE="./isa2017.sqlite"
COMMAND="sqlite3 $FILE"

printf "Amount of nodes in the file:\n"
$COMMAND "select COUNT(*) from osm_nodes"

printf "\nList of names with jan:\n"
$COMMAND 'select v from osm_way_tags where k == "name" and v like "%jan%"'

printf "\nList of nodes in Valentin Vaernewyck:\n"
$COMMAND 'select node_id from osm_way_nodes t1
          inner join osm_way_tags t2
          on t1.way_id == t2.way_id
          where t2.k == "name"
          and t2.v == "Valentin Vaerwyckweg"'

printf "\nAll the streets on the map:\n"
$COMMAND 'select distinct t1.v from osm_way_tags t1
          inner join osm_way_tags t2
          on t1.way_id == t2.way_id
          where t2.k == "highway"
            and t1.k=="name"'

printf "\nAll streets connected to Voskenslaan:\n"
STREETS=()
$COMMAND 'select distinct t2.v from osm_way_nodes t1
          inner join osm_way_tags t2
          on t1.way_id == t2.way_id
          where t1.node_id IN (select distinct node_id from osm_way_nodes t1
				                        inner join osm_way_tags t2
				                        on t1.way_id == t2.way_id
				                        where t2.k == "name"
				                        and t2.v == "Voskenslaan"
				 )
	        and t2.k == "name"
	        and t2.v != "Voskenslaan"'

printf "\nClosest nodes to GPS location example: 51.026,3.71):\n"
$COMMAND "select * from osm_nodes
order by ((lat-51.026)*(lat-51.026)+(lon-3.71)*(lon-3.71)) asc limit 10;"

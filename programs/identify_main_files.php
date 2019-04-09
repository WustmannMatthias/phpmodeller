<?php


	require_once "../functions/database_functions.php";
	require_once '../vendor/autoload.php';


	use GraphAware\Neo4j\Client\ClientBuilder;


	$timestamp_start = microtime(true); //Just to mesure running time

	//Connexion to database
	$client = ClientBuilder::create()
	    ->addConnection('bolt', 'bolt://neo4j:password@localhost:7687')
	    ->build();
	
	$query = "MATCH (f:File)	WHERE NOT (f)-[:IS_INCLUDED_IN]->() 
							   	AND NOT (f)-[:IS_REQUIRED_IN]->()
							   	AND NOT (f)-[:DECLARES]->(:Namespace) 
			  RETURN f.path as path";

	
	$result = runQuery($client, $query);

	echo "Here is the list of the main files of the programm. Thoses files aren't 
			included or required in any others, and must be annotated to tell which
			feature(s) they impact.";
	echo "\n\n";

	foreach ($result->records() as $record) {
		echo $record->value("path");
		echo "\n";
	}



	echo "\nDone.\n";


	echo "\n\n";
	echo 'Running time : ' .(microtime(true) - $timestamp_start). 's.';
?>
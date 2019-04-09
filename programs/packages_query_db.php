<?php
	
	session_start();

	include __DIR__.'/queries.php';

	require_once __DIR__."/../vendor/autoload.php";
	use GraphAware\Neo4j\Client\ClientBuilder;
	



	//Check parameters and build query
	if (sizeof($_GET) == 1 && isset($_GET['query_id'])) {
		$query_id = $_GET['query_id'];
	}
	else {
		echo "Query id missing. Cannot build query";
		exit();
	}

	$query = $queries[$query_id]['query'];
	foreach ($queries[$query_id]['params'] as $param => $type) {

		if (!array_key_exists($param, $_POST)) {
			echo "Parameters missing. Cannot build query";
			exit();
		}

		if ($type == 'string') {
			$query = str_replace('$'.$param, $_POST[$param], $query);
		}
		else if ($type == 'list') {
			$cypherList = "[";
			
			if (is_array($_POST[$param])) {
				foreach ($_POST[$param] as $item) {
					$cypherList.= "'".$item . "', ";
				}
				$cypherList = substr($cypherList, 0, strlen($cypherList) - 2);
			}
			else {
				$cypherList.= "'".$_POST[$param]."'";
			}

			$cypherList.= "]";
			$query = str_replace('$'.$param, $cypherList, $query);
		}
	}



	//Instanciate db driver
	$fullURL = "bolt://" . $_SESSION['DATABASE_URL'] . ":" . $_SESSION['DATABASE_PORT'];
	$client = ClientBuilder::create()
	    ->addConnection('bolt', $fullURL)
	    ->build();



	//Run query 
	$timestampQuery = microtime(TRUE);
	$result = $client->run($query);
	$queryRunningTime = microtime(TRUE) - $timestampQuery;


	//prepare output array
	$returns = $queries[$query_id]['return'];
	$json = array();
	$counter = 0;
	foreach ($result->records() as $record) {
		$json[$counter] = array();
		foreach ($returns as $return) {
			$json[$counter][$return] = $record->value($return);
		}
		
		$counter ++;
	}
	
	$output = array();
	$output['duration'] = $queryRunningTime;
	$output['result']	= $json;
	echo json_encode($output);
	exit();

?>
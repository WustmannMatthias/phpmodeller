<?php
	session_start();


	require_once __DIR__."/../vendor/autoload.php";
	use GraphAware\Neo4j\Client\ClientBuilder;

	function outputs($client, $node, $property) {
		$query = "MATCH (n:$node) RETURN DISTINCT n.$property AS n ORDER BY n ASC";

		$result = $client->run($query);

		$json = array();
		foreach ($result->records() as $record) {
			array_push($json, $record->value("n"));
		}
		echo json_encode($json);
	}
	
	if (count($_POST) > 1) {
		echo "Too many data in $_POST";
		exit();
	}


	$fullURL = "bolt://" . $_SESSION['DATABASE_URL'] . ":" . $_SESSION['DATABASE_PORT'];
	$client = ClientBuilder::create()
	    ->addConnection('bolt', $fullURL)
	    ->build();

	if (isset($_POST['property'])) {
		if ($_POST['property'] == 'package') {
			outputs($client, 'Package', 'name');
		}
		else if (in_array('project', $_POST)) {
			outputs($client, 'Project', 'name');
		}
	}
	

?>
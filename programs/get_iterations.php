<?php
	session_start();

	use GraphAware\Neo4j\Client\ClientBuilder;

	$databaseURL 	= $_SESSION['DATABASE_URL'];
	$databasePort 	= $_SESSION['DATABASE_PORT'];
	$username		= $_SESSION['USERNAME'];
	$password 		= $_SESSION['PASSWORD'];
	
	require_once "../vendor/autoload.php";

	require_once "../functions/database_functions.php";



	$fullURL = "bolt://".$username.":".$password."@".$databaseURL.":".$databasePort;
	$client = ClientBuilder::create()
		->addConnection('bolt', $fullURL)
		->build();

	if (isset($_POST['project'])) {
		$project = $_POST['project'];

		$query = "MATCH (i:Iteration)-[:IS_ITERATION_OF]->(p:Project)
					WHERE p.name = '$project'
					RETURN i.name as iteration";

		$result = runQuery($client, $query);

		$output = "<option value='none' selected>...</option>";

		foreach ($result->records() as $record) {
			$iteration = $record->value('iteration');
			$output.= "<option value='$iteration'>$iteration</option>";
		}

		echo $output;
	}
	else {
		echo "<option>ERROR</option>";
	}	
?>
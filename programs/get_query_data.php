<?php
	session_start();

	include __DIR__.'/queries.php';

	

	if (sizeof($_POST) == 1 && isset($_POST['query_id'])) {

		echo json_encode($queries[$_POST['query_id']]);
	}

	
	
?>
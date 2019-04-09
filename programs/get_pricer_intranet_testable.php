<?php

	$paths = explode("\n", file_get_contents(__DIR__.'/../logs/Pricer2016Q2_endfiles'));

	$regex = '/Pricer2016Q2\/intranet\/(?P<feature>[^\/]+\.php)$/';
	$features = array();

	foreach ($paths as $path) {
		if (preg_match($regex, $path, $matches)) {
			array_push($features, $matches['feature']);
		}
	}

	foreach ($features as $feature) {
		echo "$feature<br>";
	}


?>

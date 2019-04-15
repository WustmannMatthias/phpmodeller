<?php
	
	error_reporting(E_ALL);
	$timestamp_full = microtime(TRUE);




	require_once __DIR__.'/../objects/Crawler.php';
	require_once __DIR__.'/../objects/Date.php';
	require_once __DIR__.'/../functions/common_functions.php';

	require __DIR__.'/../vendor/autoload.php';




	
	chdir(__DIR__."/../data/projects");
	
	$dirs = scandir('.');
	print_r($dirs);
	
	$counter = 0;
	foreach ($dirs as $item) {
		if (!is_dir($item) || in_array($item, ['.', '..'])) continue;
		$counter ++;

		echo $item."\n";
		chdir($item);
		passthru('git pull', $output);
		chdir('..');
	}


	echo "\n\n\n";
	
	echo "Program successfully completed.\n";

?>

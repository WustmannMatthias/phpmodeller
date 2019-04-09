<?php

	/**
		This programm build a Crawler object with the settings found in /data/general_settings and in /data/projects_settings,
		and calls its method crawl().

		In other terms, this programm parse and model a repository in the DB.
	*/


	error_reporting(E_ALL);
	$timestamp_full = microtime(TRUE);


	require_once __DIR__.'/../objects/Crawler.php';



	//Get user settings
	$timestamp_parse_settings = microtime(TRUE);
	require __DIR__."/parse_settings.php";
	$timestamp_parse_settings = microtime(TRUE) - $timestamp_parse_settings;


	$crawler = new Crawler($repository, $repoName, $iterationName, $iterationBegin, $iterationEnd, $extensions, $noExtensionFiles, $featureSyntax,
							$subDirectoriesToIgnore, $filesToIgnore, $databaseURL, $databasePort, $username, $password);
	$crawler->crawl();





	$timestamp_full = microtime(TRUE) - $timestamp_full;

	echo "<br><br>Script full running time : ".number_format($timestamp_full, 4)."s<br>";

?>

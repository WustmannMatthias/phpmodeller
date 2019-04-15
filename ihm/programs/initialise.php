
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

		$project = $item;
		$repository = realpath(__DIR__."/../data/projects/$project");
		$repoName = getRepoName($repository);
		echo "$counter -> $repository : $repoName\n";
		$iterationName = 'initialisation';
		$iterationBegin = "";
		$iterationEnd 	= "";
		$extensions = array('php', 'inc');
		$noExtensionFiles = False;
		$featureSyntax = "@feature";
		$subDirectoriesToIgnore = array('.git');
		$filesToIgnore = array();

		$settings = parse_ini_file(__DIR__."/../data/general_settings/database", True, INI_SCANNER_NORMAL);
		$databaseURL = $settings['DATABASE_URL'];
		$databasePort = $settings['DATABASE_PORT'];
		$username = $settings['USERNAME'];
		$password = $settings['PASSWORD'];


		$crawler = new Crawler($repository, $repoName, $iterationName, $iterationBegin, $iterationEnd, $extensions, $noExtensionFiles, $featureSyntax,
								$subDirectoriesToIgnore, $filesToIgnore, $databaseURL, $databasePort, $username, $password);


		$crawler->crawl();
		echo "\n\n\n\n\n\n";

	}

	echo "\n\n\n";

	echo "Program successfully completed.\n";




?>

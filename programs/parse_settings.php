<?php
	/**
		§§ Parse Settings
	*/
	require_once __DIR__.'/../functions/common_functions.php';

	/**
		Just separate multiple parameters and returns an array with all trimed parameters
		@param parameters is the String with parameters in it
		@param separator is the caracter used to separate parameters
		@return is an array
	*/
	function parseParameters($parameters) {
		$output = array();
		$parametersArray = explode(',', $parameters);
		foreach ($parametersArray as $parameter) {
			array_push($output, trim($parameter));
		}
		return $output;
	}




	/******************* PARSE ITERATION FILE *********************/

	$iterationSettings = parse_ini_file(__DIR__."/../data/general_settings/iteration", true, INI_SCANNER_NORMAL);

	$project = $iterationSettings['REPOSITORY'];
	$repository = realpath(__DIR__."/../data/projects/$project");
	$repoName = getRepoName($repository);

	$iterationName 	= $iterationSettings['ITERATION_NAME'];
	$iterationBegin = $iterationSettings['ITERATION_BEGIN'];
	$iterationEnd 	= $iterationSettings['ITERATION_END'];






	/******************* PARSE PROJECT SETTINGS FILE *********************/

	$settings = parse_ini_file(__DIR__."/../data/projects_settings/$project", true,
								INI_SCANNER_NORMAL);

	$extensions = parseParameters($settings['EXTENSIONS']);

	$noExtensionFiles = $settings['NO_EXTENSION_FILES'];

	$featureSyntax = $settings['FEATURE_SYNTAX'];

	$subDirectoriesToIgnore = parseParameters($settings['SUB_DIRECTORIES']);
	array_push($subDirectoriesToIgnore, '.git');
	$subDirectoriesToIgnore = array_unique($subDirectoriesToIgnore);

	$filesToIgnore = parseParameters($settings['FILES']);





	/******************* PARSE DATABASE SETTINGS FILE *********************/
	unset($settings);

	$settings = parse_ini_file(__DIR__."/../data/general_settings/database", true,
								INI_SCANNER_NORMAL);
	$databaseURL = $settings['DATABASE_URL'];
	$databasePort = $settings['DATABASE_PORT'];
	$username = $settings['USERNAME'];
	$password = $settings['PASSWORD'];



	//displayArray($settings);


?>

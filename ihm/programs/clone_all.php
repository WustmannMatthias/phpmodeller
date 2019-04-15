<?php

	error_reporting(E_ALL);
	$timestamp_full = microtime(TRUE);




	require_once __DIR__.'/../objects/Crawler.php';
	require_once __DIR__.'/../objects/Date.php';
	require_once __DIR__.'/../functions/common_functions.php';

	require __DIR__.'/../vendor/autoload.php';

	use GuzzleHttp\Client;

	$client = new Client(['base_uri' => 'https://api.github.com']);



	$res1 = $client->request('GET', '/orgs/flash-global/repos?per_page=100', [
							'auth' => ['WustmannMatthias', $token],
							'Accept' => 'application/vnd.github.v3+json'
							]);
	$res2 = $client->request('GET', '/orgs/flash-global/repos?per_page=100&page=2', [
							'auth' => ['WustmannMatthias', $token],
							'Accept' => 'application/vnd.github.v3+json'
							]);
	$res3 = $client->request('GET', '/orgs/flash-global/repos?per_page=100&page=3', [
							'auth' => ['WustmannMatthias', $token],
							'Accept' => 'application/vnd.github.v3+json'
							]);

	if (!$res1->getStatusCode() == 200)  {
		echo "Error : Http request got status code ".$res1->getStatusCode();
		exit();
	}
	if (!$res2->getStatusCode() == 200)  {
		echo "Error : Http request got status code ".$res2->getStatusCode();
		exit();
	}
	if (!$res3->getStatusCode() == 200)  {
		echo "Error : Http request got status code ".$res3->getStatusCode();
		exit();
	}


	$data1 = json_decode($res1->getBody());
	$data2 = json_decode($res2->getBody());
	$data3 = json_decode($res3->getBody());

	$sshUrls = array();

	foreach ($data1 as $repo) {
		$sshUrls[$repo->name] = $repo->ssh_url;
	}

	foreach ($data2 as $repo) {
		$sshUrls[$repo->name] = $repo->ssh_url;
	}
	foreach ($data3 as $repo) {
		$sshUrls[$repo->name] = $repo->ssh_url;
	}
	print_r($sshUrls);





	chdir(__DIR__."/../data/projects");
	foreach ($sshUrls as $name => $url) {
		/**
		 *	Once we got the ssh urls, let's clone every repo and install dependencies
		 */
		passthru('git clone '.$url, $output);

		/*
		if (in_array('composer.json', scandir($name))) {
			chdir($name);
			passthru('composer install');
			chdir('..');
		}
		*/

	}


	echo "\n\n\n";

	echo "Program successfully completed.\n";

?>

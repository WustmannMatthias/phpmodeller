<?php
    /**
     *  This program takes ni entry a file containing paths, for each of thoses path, it search in the
     *  db which file dépends of the path, and write results in a file
     */

    $inputFile     = "/home/wustmann/Bureau/paths";
    $outputFile    = "/home/wustmann/dependencies";

    require_once __DIR__.'/queries.php';
    require_once __DIR__.'/../vendor/autoload.php';
    use GraphAware\Neo4j\Client\ClientBuilder;


    //Get paths and query
    $paths          = explode("\n", file_get_contents($inputFile));
    $query          = $queries['3']['query'];
    $returnField    = $queries['3']['return'][0];
    $output         = "";

    //Instanciate db driver
    $settings = parse_ini_file(__DIR__."/../data/general_settings/database", True, INI_SCANNER_NORMAL);
    $databaseURL = $settings['DATABASE_URL'];
    $databasePort = $settings['DATABASE_PORT'];
	$fullURL = "bolt://" . $databaseURL . ":" . $databasePort;
    echo $fullURL;
    echo "\n";
	$client = ClientBuilder::create()
	    ->addConnection('bolt', $fullURL)
	    ->build();


    foreach ($paths as $path) {
        $path = substr($path, 1, strlen($path) - 1);
        $repo = explode("/", $path)[0];
        $q = str_replace("\$repo", $repo, $query);
        $q = str_replace("\$path", $path, $q);
        $result = $client->run($q);

        $output.= "Following files depends of $path : \n";
        foreach ($result->records() as $record) {
            $file = $record->value($returnField);
            $output.= $file."\n";
        }

        $output.= "\n\n\n";
    }

    file_put_contents($outputFile, $output);

?>

<?php

	require_once __DIR__."/../vendor/autoload.php";

	require_once __DIR__."/../autoloader.php";

	use GraphAware\Neo4j\Client\ClientBuilder;

	require_once __DIR__."/../functions/common_functions.php";
	require_once __DIR__."/../functions/repo_scan_functions.php";
	require_once __DIR__."/../functions/database_functions.php";
	require_once __DIR__."/../functions/display_exceptions_functions.php";

	include_once __DIR__."/Node.php";




	/**
	 * Takes all parameters and settings of a repo, parse it, and upload it in DB.
	 * Outputs logs of the execution
	 */
	class Crawler {

		private $_repository;
		private $_repoName;
		private $_iterationName;
		private $_iterationBegin;
		private $_iterationEnd;
		private $_extensions;
		private $_noExtensionFiles;
		private $_featureSyntax;
		private $_subDirectoriesToIgnore;
		private $_filesToIgnore;
		private $_databaseURL;
		private $_databasePort;
		private $_username;
		private $_password;

		public function __construct($repository, $repoName, $iterationName, $iterationBegin, $iterationEnd, $extensions, $noExtensionFiles, $featureSyntax,
									$subDirectoriesToIgnore, $filesToIgnore, $databaseURL, $databasePort, $username, $password) {

			$this->_repository = $repository;
			$this->_repoName = $repoName;
			$this->_iterationName = $iterationName;
			$this->_iterationBegin = $iterationBegin;
			$this->_iterationEnd = $iterationEnd;
			$this->_extensions = $extensions;
			$this->_noExtensionFiles = $noExtensionFiles;
			$this->_featureSyntax = $featureSyntax;
			$this->_subDirectoriesToIgnore = $subDirectoriesToIgnore;
			$this->_filesToIgnore = $filesToIgnore;
			$this->_databaseURL = $databaseURL;
			$this->_databasePort = $databasePort;
			$this->_username = $username;
			$this->_password = $password;

		}



		public function crawl() {
			$repository = $this->_repository;
			$repoName = $this->_repoName;
			$iterationName = $this->_iterationName;
			$iterationBegin = $this->_iterationBegin;
			$iterationEnd = $this->_iterationEnd;
			$extensions = $this->_extensions;
			$noExtensionFiles = $this->_noExtensionFiles;
			$featureSyntax = $this->_featureSyntax;
			$subDirectoriesToIgnore = $this->_subDirectoriesToIgnore;
			$filesToIgnore = $this->_filesToIgnore;
			$databaseURL = $this->_databaseURL;
			$databasePort = $this->_databasePort;
			$username = $this->_username;
			$password = $this->_password;



			/*******************************************************************************
			********************************************************************************
			**************************** REPOSITORY * SCANNING *****************************
			********************************************************************************
			*******************************************************************************/
			echo "*************************************************";
			echo "\n         REPOSITORY : ".$repoName."\n";
			echo "*************************************************\n\n";
			//echo $this->toString()."\n\n";

			//Get array of every file in repo
			$timestamp_directory = microtime(TRUE);
			try {
				$files = getDirContent($repository, $repository, $subDirectoriesToIgnore, $filesToIgnore);
				$files = keepSpecificTypesOnly($files, $extensions, $noExtensionFiles);
				foreach ($files as $file) {
					if (endsWith($file, 'inc')) {
						echo "$file\n";
					}
				}
			}
			catch (RepositoryScanException $e) {
				echo $e->getMessage();
				echo "\n";
				echo "Can't scan repository. Program end.\n";
				exit();
			}

			$repoName = getRepoName($repository);
			$timestamp_directory = microtime(TRUE) - $timestamp_directory;






			/*******************************************************************************
			********************************************************************************
			*************************** DATABASE * INITIALISATION **************************
			********************************************************************************
			*******************************************************************************/
			/**
				Project layer :
				We want to keep relation between :File nodes and :Iteration nodes, to have
				a history of modification.
				-> Just remove everything that is not a File or an Iteration, and remove All
					relations bewteen files : thoses will be reanalysed and remodeled.
					Then Node class will take care of making updates and reupload dependencies,
					features, namespaces.

				Database Layer :
				We want to do the same, but only with the project we are analysing : every other
				repositories represented in the database have to be left alone
			*/
			$timestamp_database = microtime(TRUE);

			$fullURL = "bolt://".$username.":".$password."@".$databaseURL.":".$databasePort;
			$client = ClientBuilder::create()
			    ->addConnection('bolt', $fullURL)
			    ->build();
			/*
			runQuery($client, "MATCH (n)-[r:IS_INCLUDED_IN|:IS_REQUIRED_IN
										|:IMPACTS|:DECLARES|:IS_USED_BY]
										->(n2) DELETE r");
			runQuery($client, "MATCH (n:Namespace), (f:Feature) DELETE n, f");
			*/

			//echo $query."\n";

			runQuery($client, "MATCH (repoFiles:File {repository: '$repoName'}),
								(repoFiles)<-[repoUses:IS_USED_BY]-(:Namespace)
								DELETE repoUses");

			//NS from vendor are have no relation DECLARE in db
			runQuery($client, "MATCH (repoFiles:File {repository: '$repoName'}),
								(repoNS:Namespace)<-[repoNSDeclarations:DECLARES]-(repoFiles)
								WHERE repoNS.repository = '$repoName'
								DELETE repoNSDeclarations, repoNS");

			runQuery($client, "MATCH (repoFiles:File {repository: '$repoName'}),
								(repoFeatures:Feature)<-[repoImpacts:IMPACTS]-(repoFiles)
								DELETE repoImpacts, repoFeatures");

			runQuery($client, "MATCH (repoFiles:File {repository: '$repoName'}),
								(:File)-[repoInclusions:IS_REQUIRED_IN|:IS_INCLUDED_IN]
								->(repoFiles)
								DELETE repoInclusions");


			if ($iterationName != "initialisation") {
				// Store the list of the nodes already in db in the Node class (static)
				$filesInDB = array();
				$result = runQuery($client, "MATCH (n:File {repository: '$repoName'}) RETURN n.path as path");
				foreach ($result->records() as $record) {
					$path = $record->value('path');
					array_push($filesInDB, $path);
				}
				Node::setOldFileList($filesInDB);


				// Calculate and store the list of the files modified since the last iteration in the Node class (static)
				$filesModified = array();
				$listCommitCommand = "cd $repository && git log '$iterationBegin'..'$iterationEnd' --oneline | cut -d ' ' -f 1";

				exec($listCommitCommand, $commitList);
				print_r($commitList);

				$filesModifiedOutput = array();
				foreach ($commitList as $commit) {
					$listFileCommand = "cd $repository && git diff-tree --no-commit-id --name-only -r $commit";
					exec($listFileCommand, $filesModifiedOutput);
				}
				$filesModifiedOutput = array_unique($filesModifiedOutput);
				foreach ($filesModifiedOutput as $path) {
					array_push($filesModified, "$repoName/".$path);
				}
				print_r($filesModified);
				Node::setNewFileList($filesModified);
			}

			$timestamp_database = microtime(TRUE) - $timestamp_database;








			/*******************************************************************************
			********************************************************************************
			****************************** FIRST * ANALYSIS ********************************
			********************************************************************************
			*******************************************************************************/
			/**
				STEP 1 : Analyse every file, store analysis, and send node in database
				After this first step, every file, namespace, and feature will be represented
				in the modeling. However, links between files won't be.
			*/
			echo "############### STEP 1 ANALYSE ###############\n";
			echo "Files to analyse : ".sizeof($files);
			echo "\n\n";


			$timestamp_analyse = microtime(TRUE);
			$nodes = array();
			foreach ($files as $file) {
				//Create Node object for each file and analyse it
				try {
					$node = new Node($file, $repoName);

				}
				catch (WrongPathException $e) {
					printQueriesGenerationExceptionMessage($e, $node->getPath());
					continue;
				}

				try {
					try {
						$node->analyseFile();

					}
					catch (VariableDeclarationNotFoundException $e) {
						printAnalysisExceptionMessage($e, $node->getPath());
					}
					catch (UnunderstoodVariableDeclarationException $e) {
						printAnalysisExceptionMessage($e, $node->getPath());
					}
					catch (AbsolutePathReconstructionException $e) {
						printAnalysisExceptionMessage($e, $node->getPath());
					}
					catch (DependencyNotFoundException $e) {
						printAnalysisExceptionMessage($e, $node->getPath());
					}
					catch (WrongPathException $e) {
						printAnalysisExceptionMessage($e, $node->getPath());
					}
					catch (WrongDependencyTypeException $e) {
						printAnalysisExceptionMessage($e, $node->getPath());
					}
					catch (UnunderstoodNamespaceDeclarationException $e) {
						printAnalysisExceptionMessage($e, $node->getPath());
					}

					//Send node in database
					$uploadQuery = $node->generateUploadQuery();
					if ($uploadQuery) {
						runQuery($client, $uploadQuery);
					}

					//Save the object
					array_push($nodes, $node);
				}
				catch (FileNotFoundException $e) {
					printAnalysisExceptionMessage($e, $node->getPath());
				}

			}

			echo "\n\n\nDone.\n\n";
			$timestamp_analyse = microtime(TRUE) - $timestamp_analyse;






			/*******************************************************************************
			********************************************************************************
			****************** STORE * DEPENDENCIES * IN * DATABASE ************************
			********************************************************************************
			*******************************************************************************/
			/**
				STEP 2 : Read informations stored in every node, send relations in database.
			*/
			echo "############### STEP 2 UPLOAD DEPENDENCIES ###############\n\n";
			$timestamp_dependencies = microtime(TRUE);
			foreach ($nodes as $node) {
				try {
					//Send include/require relations in database
					$fileInclusionsQuery = $node->generateFileInclusionsRelationQuery();
					if ($fileInclusionsQuery) {
						runQuery($client, $fileInclusionsQuery);
					}

					//Send use relations in database
					$useQuery = $node->generateUseRelationQuery();
					if ($useQuery) {
						runQuery($client, $useQuery);
					}
				}
				catch (WrongPathException $e) {
					printQueriesGenerationExceptionMessage($e, $node->getPath());
				}
			}
			echo "\n\n\nDone.\n\n";
			$timestamp_dependencies = microtime(TRUE) - $timestamp_dependencies;









			/*******************************************************************************
			********************************************************************************
			***************** ADD * ITERATION * NODE * IN * DATABASE ***********************
			********************************************************************************
			*******************************************************************************/
			/**
				STEP 3 : Add iterations in database
			*/
			echo "############### STEP 3 ADD ITERATION ###############\n\n";
			$timestamp_iteration = microtime(TRUE);

			if (count($nodes) > 0) {
				echo "IN ITERATION CONDITION\n";
				$repoName = $nodes[0]->getRepoName(); // All nodes belongs to the same repo

				$query = "MATCH  (f:File)
						  WHERE  f.repository = '$repoName' ";
				if ($iterationName != 'initialisation') {
					$query .= " AND f.path IN ['".implode("', '", $filesModified)."'] ";
				}
				$query .= "MERGE  (p:Project {name: '$repoName'})
						  MERGE  (i:Iteration {name: '$iterationName'})-[:IS_ITERATION_OF]->(p)
						  ";
				if ($iterationName != 'initialisation') {
					$query .="
							SET
							i.begin = '$iterationBegin',
						  	i.end = '$iterationEnd' ";
				}
				$query .= "MERGE (f)-[:BELONGS_TO]->(i) ";

				//echo $query."\n\n";
				runQuery($client, $query);
			}

			echo "\n\n\nDone.\n\n";
			$timestamp_iteration = microtime(TRUE) - $timestamp_iteration;







			/*******************************************************************************
			********************************************************************************
			********************* ADD * SERVICES * IN * DATABASE ***************************
			********************************************************************************
			*******************************************************************************/
			/**
				STEP 4 : Parse composer.lock and add services in Database
			*/
			echo "############### STEP 4 ADD SERVICES ###############\n\n";
			$timestamp_services = microtime(TRUE);

			try {
				$composerLock = $repository.'/composer.lock';
				if (!file_exists($composerLock)) {
					throw new FileNotFoundException($composerLock);
				}
				$content = file_get_contents($composerLock);
				$json = json_decode($content);

				foreach ($json->packages as $package) {
					$serviceName = $package->name;
					$serviceVersion = $package->version;
					$serviceUrl = $package->source->url;


					$query = "	MATCH (p:Project {name: '$repoName'})
								MERGE (pck:Package {
									name: '$serviceName',
									url:  '$serviceUrl'
								})
								MERGE (p)-[rel:DEPENDS_ON]->(pck)
								SET rel.version = '$serviceVersion'
								";


					runQuery($client, $query);
				}


			}
			catch (FileNotFoundException $e) {
				print("Couldn't parse composer.lock : file doesn't exist.");
				file_put_contents('/home/wustmann/Bureau/no_composer', $repoName." \n", FILE_APPEND | LOCK_EX); //TEMPORARY
			}

			echo "\n\n\nDone.\n\n";
			$timestamp_services = microtime(TRUE) - $timestamp_services;








			/*******************************************************************************
			********************************************************************************
			*************************** DISPLAY * PERFORMANCES *****************************
			********************************************************************************
			*******************************************************************************/

			echo "############### PERFORMANCES ###############\n\n";
			echo "Time to load repository : "
				.number_format($timestamp_directory, 4)."s\n";
			echo "Time to prepare database : "
				.number_format($timestamp_database, 4)."s\n";
			echo "Time to analyse repository : "
				.number_format($timestamp_analyse, 4)."s\n";
			echo "Time to upload dependencies : "
				.number_format($timestamp_dependencies, 4)."s\n";
			echo "Time to add iteration : "
				.number_format($timestamp_iteration, 4)."s\n";
			echo "Time to add services : "
				.number_format($timestamp_services, 4)."s\n";

		}



		public function toString() {
			return
			"repository = ".$this->_repository."\n".
			"repoName = ".$this->_repoName."\n".
			"iterationName = ".$this->_iterationName."\n".
			"iterationBegin = ".$this->_iterationBegin->toString()."\n".
			"iterationEnd = ".$this->_iterationEnd->toString()."\n".
			"extensions = ".$this->arrayToString($this->_extensions)."\n".
			"noExtensionFiles = ".$this->_noExtensionFiles."\n".
			"featureSyntax = ".$this->_featureSyntax."\n".
			"subDirectoriesToIgnore = ".$this->arrayToString($this->_subDirectoriesToIgnore)."\n".
			"filesToIgnore = ".$this->arrayToString($this->_filesToIgnore)."\n".
			"databaseURL = ".$this->_databaseURL."\n".
			"databasePort = ".$this->_databasePort."\n".
			"username = ".$this->_username."\n".
			"password = ".$this->_password."\n";
		}

		private function arrayToString($arr) {
			$output = "[";
			foreach ($arr as $key => $value) {
				$output.= "$key => $value , ";
			}
			if ($output != "[") {
				$output = substr($output, 0, strlen($output) - 3);
			}
			$output .= "]";
			return $output;
		}

	}


?>

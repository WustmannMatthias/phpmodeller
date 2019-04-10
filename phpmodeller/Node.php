<?php

	/*
		§§ File scan
		§§ Query generation
	*/

	require_once __DIR__.'/Dependency.php';



	Class Node {

		/**
			This class represents a Node in the modelisation. There should be one for each file of a repo.
			Following attributes stores informations about the file.
		*/

		private $_path;
		private $_name;
		private $_size;
		private $_extension;
		private $_lastModified;
		private $_repoName;
		private $_inVendor;
		private $_loc;

		private $_features;

		private $_fileInclusions;
		private $_namespaces;
		private $_globalVariables;
		private $_uses;


		private static $oldFileList = array();
		private static $newFileList = array();



		/**
			Get values of all attributes of the instance juste from the accesspath
			@param path is a String -> absolute path to file
			@param repoName is a String -> name of the repo
		*/
		public function __construct($path, $repoName) {
			$this->_path			= $path;

			$this->_repoName		= $repoName;

			$this->_name 			= $this->pickUpName($path);
			$this->_extension		= $this->pickUpExtension($path);
			$this->_size 			= $this->pickUpSize($path);
			$this->_lastModified 	= $this->pickUpLastModified($path);
			$this->_inVendor		= Node::isInVendor($path, $repoName);

			$this->_features		= array();

			$this->_fileInclusions	= array();
			$this->_namespaces 		= array();
			$this->_uses 			= array();
		}


		/**
			Functions called by constructor to get infos about a file
			@param path is a String and represents absolute path to file.
		*/
		private function pickUpSize($path) {
			return filesize($path);
		}
		private function pickUpName($path) {
			return @end(explode('/', $path));
		}
		private function pickUpLastModified($path) {
			return Date::buildDateFromTimestamp(filemtime($path));
		}
		private function pickUpExtension($path) {
			return @pathinfo($path)['extension'];
		}


		public static function setOldFileList($oldFileList) {
			self::$oldFileList = $oldFileList;
		}
		public static function getOldFileList() {
			return self::$oldFileList;
		}

		public static function setNewFileList($newFileList) {
			self::$newFileList = $newFileList;
		}
		public static function getNewFileList() {
			return self::$newFileList;
		}





		/*******************************************************************************
		********************************************************************************
		**************************** FILE * STATIC * ANALYSIS **************************
		********************************************************************************
		*******************************************************************************/


		/**
			Main method of the class
			This method run one time through every line of the file, analyse it, and store following registred informations in attributes of the instance :
			- file included/required in this one
			- outside classes used
			- declared namespaces
			- declared features

			However, if the file belongs to the vendor directory, we care only about the
			declared namespaces, because we don't need to represent relations between
			dependency files.
		*/
		public function analyseFile() {
			if (!file_exists($this->_path)) {
				throw new FileNotFoundException($this->_path);
			}

			$inComment = FALSE;
			$lineCount = 0;

			$fileHandler = fopen($this->_path, 'r');
			while (!feof($fileHandler)) {
				$line = trim(fgets($fileHandler));
				$lineCount ++;

				if ($this->_inVendor) {
					$this->analyseNameSpaces($line, $lineCount);
				}
				else {
					$this->analyseFeatures($line);

					// Comments handling
					if (startsWith($line, "//")) continue;
					if (startsWith($line, "/*")) $inComment = TRUE;
					if ($inComment) {
						if (strpos($line, "*/") === FALSE) continue;
						else $inComment = FALSE;
					}

					$this->analyseNameSpaces($line, $lineCount);
					$this->analyseUses($line);
					$this->analyseFileInclusions($line, $lineCount);
				}
			}

			$this->_loc = $lineCount;
		}


		/**
			Tells if the file is in the vendor directory
			@return is a bool
		*/
		private static function isInVendor($path, $repoName) {
			$pathFromRepo = Node::getPathFromRepo($path, $repoName);
			return startsWith($pathFromRepo, $repoName.'/vendor');
		}

		/**
			Features are declared in code by developpers by a specific syntax in
			header of the file.
			This method analyse a line to find this syntax, and store the feature
			@param line is a String
		*/
		private function analyseFeatures($line) {
			global $featureSyntax;
			$regex = "/(".$featureSyntax."){1}\s.*$/";
			if (preg_match($regex, $line)) {
				$feature = $this->extractFeature($line);
				array_push($this->_features, $feature);
			}
		}



		/**
			Matches include/require statements and extract argument.
			If the argument is composed of variables, they will be replaced by their
			values. However, if the variable is defined in an other file, the value won't be found.
			@param line is the line to analyse (String)
			@param lineCount is the number of the line (int)
		*/
		private function analyseFileInclusions($line, $lineCount) {
			$matches = array();
			$regex = "/((require)|(include)){1}(_once)?\s+[-_ A-Za-z0-9\$\.\"'\/\s\[\]]+;/";
			if (preg_match($regex, $line, $matches)) {

				if ($subLine = $this->isRelPathInLine($line)) {
					$line = $this->replaceRelPath($line, $subLine);
				}
				if ($this->isVariableInLine($matches[0])) {
					$line = $this->replaceVariables($line, $lineCount);
				}
				if ($this->isMagicConstantInLine($line)) {
					$line = $this->replaceMagicConstant($line);
				}
				if ($subLine = $this->isShittyConstantInLine($line)) {
					$line = $this->replaceShittyConstant($line, $subLine);
				}
				$line = $this->removeUnnecessary($line);
				$line = $this->removeDoubleSlash($line);
				$path = $this->fillPath($line, $lineCount);

				if (!Node::isInVendor($path, $this->_repoName)) {
					if (strpos($matches[0], 'include_once') !== FALSE) {
						$type = 'include';
						$once = TRUE;
					}
					else if (strpos($matches[0], 'require_once') !== FALSE) {
						$type = 'require';
						$once = TRUE;
					}
					else if (strpos($matches[0], 'include') !== FALSE) {
						$type = 'include';
						$once = FALSE;
					}
					else if (strpos($matches[0], 'require') !== FALSE) {
						$type = 'require';
						$once = FALSE;
					}
					else {
						throw new WrongDependencyTypeException($this->_path,
									$line, $matches[0]);
					}

					$dependency = new Dependency($path, $type, $once,
												$this->_path, $lineCount);

					array_push($this->_fileInclusions, $dependency);
				}
			}
		}

		/**
			Matches namespace statements and extract argument.
			@param line is the line to analyse (String)
		*/
		private function analyseNameSpaces($line, $lineCount) {
			$regex = "/^namespace\s+[-_ A-Za-z0-9\\\]+/";
			if (preg_match($regex, $line)) {
				$namespace = $this->extractNamespace($line, $lineCount);
				array_push($this->_namespaces, $namespace);
			}
		}

		/**
			Matches use statements and extract argument.
			@param line is the line to analyse (String)
		*/
		private function analyseUses($line) {
			$regex = "/^use\s+[-_ A-Za-z0-9\\\]+/";
			if (preg_match($regex, $line)) {
				$use = $this->extractUses($line);
				if ($use) {
					array_push($this->_uses, $use);
				}
			}
		}



		/**
			Check if a variable is used in a code line.
			@param line (string) is the line to analyse
			@return is a boolean
		*/
		private function isVariableInLine($line) {
			if (strpos($line, '$') === FALSE) { //=== because '$' can be at index 0
				return FALSE;
			}
			return TRUE;
		}

		/**
			Check if a the magic constant __DIR__ is used in a code line.
			@param line (string) is the line to analyse
			@return is a boolean
		*/
		private function isMagicConstantInLine($line) {
			if (strpos($line, '__DIR__') === FALSE) { //=== because '$' can be at index 0
				return FALSE;
			}
			return TRUE;
		}


		/**
			Check if this weird and useless pattern is used in a code line :
			dirname(__FILE__)
			return it if found
			@param $line (string) is the line to analyse
			@return is the found pattern, or false
		*/
		private function isShittyConstantInLine($line) {
			$regex = "/dirname\s*\(\s*__FILE__\s*\)/";
			if (preg_match($regex, $line, $result)) {
				return $result;
			}
			return FALSE;
		}



		/**
			Check if the pattern $_SERVER['REL_PATH'] is used in a code line,
			and return it if found
			@param line is the line to analyse
			@return is the found pattern, or false
		*/
		private function isRelPathInLine($line) {

			$regex = '/\$_SERVER\s*\[\s*[\\]?[\"\']REL_PATH[\\]?[\"\']\s*\]/';
			if (preg_match($regex, $line, $result)) {
				return $result[0];
			}
			return FALSE;
		}


		/**
			Take a code line containing a variable, and replace it by her value
			@param line (string) is the line
			@param lineCount (int) is the number of the line
			@return (string) is the new line
		*/
		private function replaceVariables($line, $lineCount) {
			$variableNames 	= $this->identifyVariable($line);
			$variableDatas 	= $this->findVariableValue($variableNames, $lineCount);
			$line 			= $this->replaceVariableWithValue($line, $variableDatas);
			return $line;
		}

		/**
			This method allows to detect variable names in a line. The variable names
			(with the $) will be returned in an array.
			@param line (string) is the line
			@return is the array containing the founded variable names
		*/
		private function identifyVariable($line) {
			$output = array();
			$endVariableChar = array('.', ';', ' ', ')');
			$tab = str_split($line);
			$inVariable = FALSE;
			$variableName = "";
			foreach ($tab as $index => $character) {
				if ($character === "$") {
					$inVariable = TRUE;
				}
				if ($inVariable && in_array($character, $endVariableChar)) {
					array_push($output, $variableName);
					$inVariable = FALSE;
					$variableName = "";
				}
				if ($inVariable) {
					$variableName.= $character;
				}
			}
			//displayArray($output);
			return $output;
		}

		/**
			Find where given variables are declared in file, and return their values
			@param variableNames (string) an array containing the name of the variables
			@param maxLine (int) is the line where the variable are used in the include
				statement (the declaration is necessarily before)
			@return (string) is the value of the variable
		*/
		private function findVariableValue($variableNames, $maxLine) {
			//First, go through file and find the line where the variable is declared
			$declarationLines = array();
			$lineCount = 0;
			$fileHandler = fopen($this->_path, 'r');
			while (!feof($fileHandler)) {
				$line = fgets($fileHandler);
				$lineCount ++;

				foreach ($variableNames as $variableName) {
					if (startsWith(trim($line), $variableName)) {
						$declarationLines[$variableName] = $line;
						//echo "found\n";
					}
				}
				if (sizeof($variableNames) == sizeof($declarationLines)) {
					break;
				}
				if ($lineCount >= $maxLine) {
					throw new VariableDeclarationNotFoundException($this->_path,
						$variableNames);
				}
			}

			//displayArray($declarationLines);

			//Then, analyse line et get value
			$output = array();
			foreach ($declarationLines as $variableName => $line) {
				if (strpos($line, '"')) {
					$output[$variableName] = explode('"', $line)[1];
				}
				else if (strpos($line, "'")) {
					$output[$variableName] = explode("'", $line)[1];
				}
				else {
					throw new UnunderstoodVariableDeclarationException($this->_path,
						$line);
				}
			}
			//displayArray($output);
			return $output;
		}

		/**
			Replace a variable with her value in an include or require line, and put
			double quotes around it.
			@param line (string) is the include line
			@param variableDatas is an array associating each variable name with her value
			@return (string) is the modified line
		*/
		private function replaceVariableWithValue($line, $variableDatas) {
			foreach ($variableDatas as $variableName => $variableValue) {
				$line = str_replace($variableName, '"'.trim($variableValue).'"', $line);
			}
			return $line;
		}


		/**
			Replace the magic constant __DIR__ with the corresponding path
			@param line is the line with the magic constant in it (String)
			@return is also a String
		*/
		private function replaceMagicConstant($line) {
			$dirPath = str_replace($this->_name, "", $this->_path);
			$newLine = str_replace("__DIR__", '"'.$dirPath.'"', $line);

			return $newLine;
		}


		/**
			Replace the weird pattern dirname(__FILE__) with the corresponding path
			@param line is the line with that pattern in it (String)
			@param subline is the exact pattern (String)
			@return is the new line (String)
		*/
		private function replaceShittyConstant($line, $subLine) {
			$dirPath = str_replace($this->_name, "", $this->_path);
			$newLine = str_replace($subLine, '"'.$dirPath.'"', $line);

			return $newLine;
		}



		/**
			Replace the pattern $_SERVER['REL_PATH'] by the corresponding path
			@param line containing the pattern (String)
			@param subLine is the exact pattern (String)
			@return is the new Line (String)
		*/
		private function replaceRelPath($line, $subLine) {
			$repoPos = strpos($this->_path, $this->_repoName);
			$upToRepo = substr($this->_path, 0, $repoPos);
			$relPath = $upToRepo.$this->_repoName.'/';
			return str_replace($subLine, '"'.$relPath.'"', $line);
		}


		/**
			Help function : removes everything that is not a part of the path to a file
			@param line (string) is the line of the file where a file is included
			@return (string) is the new line
		*/
		private function removeUnnecessary($line) {
			//echo $line."\n";
			$tab = str_split(trim($line));
			$inSimpleQuotes = FALSE;
			$inDoubleQuotes = FALSE;
			$output = "";
			foreach ($tab as $character) {
				if ($character == "'") {
					$inSimpleQuotes = !$inSimpleQuotes;
				}
				if ($character == '"') {
					$inDoubleQuotes = !$inDoubleQuotes;
				}
				if (($inSimpleQuotes || $inDoubleQuotes) && $character != "'" && $character != '"') {
					$output.= $character;
				}
			}

			return $output;
		}


		/**
			Removes eventual '//' in path
			@param line is a String
			@return is a String
		*/
		private function removeDoubleSlash($line) {
			$line = str_replace('//', '/', $line);
			//echo $line."\n";
			return $line;
		}


		/**
			Takes a line with a namespace statement and returns only the argument
			@param line is a String
			@return is a String
		*/
		private function extractNamespace($line, $lineCount) {
			$end = strpos($line, ';');
			if ($end === FALSE) {
				$end = strpos($line, '{');
			}
			if ($end === FALSE) {
				throw new UnunderstoodNamespaceDeclarationException($this->_path,
					$lineCount);
			}

			$line = substr($line, 0, $end);

			$namespace = trim(str_replace("namespace ", "", $line));
			if ($namespace[0] == '\\') {
				$namespace = substr($namespace, 1);
			}
			return $namespace;
		}


		/**
			Takes a line with a use statement and returns only the argument
			@param line is a String
			@return is a String
		*/
		private function extractUses($line) {
			$end = strpos($line, ';');
			$line = substr($line, 0, $end);

			$argument = trim(str_replace("use ", "", $line));
			if (strpos($argument, '\\') === FALSE) {
				return FALSE;
			}
			if ($argument[0] == '\\') {
				$argument = substr($argument, 1);
			}

			return $argument;
		}


		/**
			Takes a line with a feature declaration and returns only the argument
			@param line is a String
			@return is a String
		*/
		private function extractFeature($line) {
			global $featureSyntax;

			$beginPos = strpos($line, $featureSyntax);
			if ($beginPos === FALSE) { // Should never happend
				return FALSE;
			}

			$endPos = $beginPos + strlen($featureSyntax);

			$feature = trim(substr($line, $endPos));
			return $feature;
		}




		/**
			Takes a relative path, or a path containing some '.' or '..' in it,
			and returns the absolute filled path.

			Strategy (maybe not the best, but easy and working) :
			1) First, concatenate directory of the current node with the relative path of
			his dependency.
				if the result path matches an actual file, then all right.
				if it's not, then let's try with the parent directory of the current node.
				And so on...
			=> Works for relative path !!
			If path is already absolute, no need to do this
			Note : the php function file_exists() works with path containing /../

			2) Then, remove the . and .. fields of the path with their values

			@param $path is a String
			@param $lineCount is the line where the include/require statement was found
				(for error messages)
			@param $newPath is a String
		*/
		private function fillPath($path, $lineCount) {
			if (!$this->isAbsolutePath($path)) {
				$dirname = dirname($this->_path);
				//echo $dirname."\n";
				$counter = 0;
				while (!file_exists($dirname.'/'.$path)) {
					//echo $dirname.'/'.$path."\n";
					$dirname = $this->removeLastDirectoryInPath($dirname);
					$counter ++;
					if ($counter > 20) { //Security, to avoid infinite loop
						throw new AbsolutePathReconstructionException($this->_path,
							$path, $lineCount);
					}
				}
				$path = $dirname.'/'.$path;
				//echo $path."\n";
			}


			if (!file_exists($path)) {
				throw new DependencyNotFoundException($this->_path, $path, $lineCount, FALSE);
			}


			//Replace . and .. with the corresponding directories
			//NOTE : php function realpath does the same, but needs execution rights
			//in all directories of the path
			$tab = explode('/', $path);
			$newTab = array();
			foreach ($tab as $item) {
				if ($item == '.') {
					continue;
				}
				elseif ($item == '..') {
					$newTab = array_trim_end($newTab);
				}
				else {
					array_push($newTab, $item);
				}
			}

			$newPath = implode('/', $newTab);
			if (!file_exists($newPath)) {
				throw new DependencyNotFoundException($this->_path, $path, $lineCount);
			}
			//echo "NewPath : ".$newPath;
			//echo "\n\n";
			return $newPath;
		}


		/**
			Help function for fillPath
			Determines whether a given path is absolute or not
			@param path is a String
			@return is a bool
		*/
		private function isAbsolutePath($path) {
			if (startsWith($path, '/')) {
				return TRUE;
			}
			return FALSE;
		}


		/**
			Help function for fillPath
			Takes a relative path and remove the last item
			Ex : examples/frames/directory -> exemples/frames
			@param path is a String
			@return is a String
		*/
		private function removeLastDirectoryInPath($path) {
			$tab = explode('/', $path);
			$tab = array_trim_end($tab);
			$path = implode('/', $tab);
			return $path;
		}



		/**
			Replace each \ with \\ in a String (to do before sending a String with
			a \ in a database, like namespaces).
			@param $tab is an array of String
			@return is an array of String
		*/
		private function doubleBackSlashes($tab) {
			$output = array();
			foreach ($tab as $item) {
				array_push($output, str_replace("\\", "\\\\", $item));
			}
			return $output;
		}







		/*******************************************************************************
		********************************************************************************
		****************************** QUERY * GENERATIONS *****************************
		********************************************************************************
		*******************************************************************************/


		/**
			Generates à Cypher Query that creates a Node for this instance in the
			neo4j Database
			If the file be……longs to the vendor directory, it must not be added to the graph.
			However, the namespaces it contains has to be.

			@return is a either a bool or a String
		*/
		public function generateUploadQuery() {
			//In this case, query would be null -> return FALSE to avoid useless
			//operations
			if ($this->_inVendor && sizeof($this->_namespaces) == 0) {
				return FALSE;
			}

			$path = Node::getPathFromRepo($this->_path, $this->_repoName);

			//Create Node
			$featureRelation = "IMPACTS";
			$namespaceRelation = "DECLARES";
			$query = "";


			if (!$this->_inVendor) {
				// If Node didn't exist before, then create it
				if (!in_array($path, self::$oldFileList)) {
					$query.= "MERGE (n:File {name: '".$this->_name
									."', path: '".$path
									."', extension: '".$this->_extension
									."', repository: '".$this->_repoName
									."'}) "
									." ON CREATE SET"
									."   n.size = ".intval($this->_size)
									." , n.loc =  ".intval($this->_loc)
									." "
									." ON MATCH SET"
									."   n.size = ".intval($this->_size)
									." , n.loc =  ".intval($this->_loc)
									." ";


				}
				else {
					//if it is in the newFileList, update his stats
					if (in_array($path, self::$newFileList)) {
						$query.= "MATCH (n:File {path: '$path'})
								SET n.size = ".intval($this->_size)
								.", n.loc = ".intval($this->_loc)
								." ";

					}
					// If it is already it the graph and has not been modified since,
					// then don't do anything
					else {
						$query.= "MATCH (n:File {path: '$path'}) ";
					}
				}
			}



			//Foreach of his features, create Node and relationship if not already exists
			if (!$this->_inVendor) {
				$counter = 0;
				foreach ($this->_features as $feature) {
					$counter ++;
					$query.= "MERGE (f".$counter.":Feature {name: '$feature',
									project: '".$this->_repoName."'}) ";
					$query.= "CREATE UNIQUE (n)-[:".$featureRelation."]->(f".$counter.") ";
				}
			}



			//Foreach of his namespaces, create Node and relationship if not already exists
			$counter = 0;
			foreach ($this->doubleBackSlashes($this->_namespaces) as $namespace) {
				$counter ++;
				$query.= "MERGE (ns".$counter.":Namespace {name: '$namespace'";
				;

				if ($this->_inVendor) {
					$query.= ", inVendor: ".boolval(TRUE);
				}
				else {
					$query.= ", repository: '".$this->_repoName."'";
				}
				$query.= "}) ";

				if (!$this->_inVendor) {
					$query.= "CREATE UNIQUE (n)-[:".$namespaceRelation."]->(ns".$counter.") ";
				}
			}

			//Just in case there is nothing after a Match
			$query.= " RETURN null";

			//echo $query."\n";
			return $query;
		}




		/**
			Generates a Cypher Query that creates every relation between this nodes and the nodes included or required in it.
			@return is a mixed value :
				- if there aren't any nodes included, returns FALSE
				- if there are, return is a String : the Cypher query
		*/
		public function generateFileInclusionsRelationQuery() {
			if (sizeof($this->_fileInclusions) == 0) {
				return FALSE;
			}

			$includeRelation = "IS_INCLUDED_BY";
			$requireRelation = "IS_REQUIRED_BY";

			$path 		= Node::getPathFromRepo($this->_path, $this->_repoName);
			$queryBegin = "MATCH (n:File {path: '".$path."'}) " ;
			$queryEnd	= "";

			$counter = 0;
			foreach ($this->_fileInclusions as $includedFile) {
				$includedFilePath = Node::getPathFromRepo($includedFile->getPath(),
									$this->_repoName);
				$relation = $includedFile->getRelation();
				$once = $includedFile->getOnce();

				$counter ++;

				$queryBegin .= "MATCH (n".$counter.":File {path: '$includedFilePath'}) ";

				$queryEnd 	.= "CREATE UNIQUE (n".$counter.")-[r".$counter.":"
							.$relation." {once: '".boolval($once)."'}]->(n) ";
			}

			$query = $queryBegin.$queryEnd;
			return $query;
		}



		/**
			Generates a Cypher Query that creates every relation between this nodes and
			the other namespaces it uses elements from.
			@return is a mixed value :
				- if there aren't any use statement in this file, returns FALSE
				- if there are, return is a String : the Cypher query
		*/
		public function generateUseRelationQuery() {
			if (sizeof($this->_uses) == 0) {
				return FALSE;
			}

			$uses = $this->prepareUses();

			$useRelation = "IS_USED_BY";

			$path 		= Node::getPathFromRepo($this->_path, $this->_repoName);
			$queryBegin = "MATCH (f:File {path: '".$path."'}) " ;
			$queryEnd	= "";

			$counter = 0;
			foreach ($uses as $namespace => $classNames) {
				$counter ++;

				$classNamesInString = $this->prepareClassNames($classNames);

				$queryBegin	.= "MATCH (n".$counter.":Namespace)
								WHERE n".$counter.".name = '$namespace'
							 	AND (n".$counter.".repository = 'repository' OR exists(n".$counter.".inVendor))";
				$queryEnd	.= "CREATE UNIQUE (n".$counter.")-[:$useRelation {class:
								$classNamesInString}]->(f) ";
			}

			$query = $queryBegin.$queryEnd;
			return $query;
		}



		/**
			Help function for generateUseRelationQuery :
			Prepare an array with all use arguments ready to be sent in database
			@return is an array : namespace => array(className1, className2, etc)
		*/
		private function prepareUses() {
			$uses = array();

			foreach ($this->_uses as $use) {
				$tab = explode('\\', $use);
				$class = $tab[sizeof($tab) - 1];
				$tab = array_trim_end($tab);
				$namespace = implode('\\\\', $tab);

				if(!isset($uses[$namespace])) {
					$uses[$namespace] = array($class);
				}
				else {
					array_push($uses[$namespace], $class);
				}
			}

			return $uses;
		}


		/**
			Help function for generateUseRelationQuery :
			Prepare the String in Cypher Syntax corresponding to the parameters of a
			relation use in Database
			@param classNames is an array of classes
			@return is a String like [class1, class2, class3]
		*/
		private function prepareClassNames($classNames) {
			$output = "[";

			foreach ($classNames as $class) {
				$output.="'".$class."', ";
			}
			$output = substr($output, 0, strlen($output) - 2);
			$output.= "]";

			return $output;
		}






		/*******************************************************************************
		********************************************************************************
		********************************** ACCESSORS ***********************************
		********************************************************************************
		*******************************************************************************/




		/**
			Delete the part of the part before the repo name
			@return is a String
		*/
		public static function getPathFromRepo($fullPath, $repoName) {
			if (strpos($fullPath, $repoName) === FALSE) {
				throw new WrongPathException($fullPath, $repoName);
			}
			//echo "Full path : $fullPath\n";
			//echo "Repo Name : $repoName\n";
			return $repoName.'/'.explode('/'.$repoName.'/', $fullPath)[1];
		}


		/**
			Accessor for private attributes
			@return are Strings
		*/
		public function getRepoName() {
			return $this->_repoName;
		}
		public function getName() {
			return $this->_name;
		}
		public function getPath() {
			return $this->_path;
		}
		public function getLastModified() {
			return $this->_lastModified;
		}


		/**
			Accessor for private attribute _inVendor
			@return is a bool
		*/
		public function getInVendor() {
			return $this->_inVendor;
		}


		/**
			Accessor for private attributes
			@return are Arrays
		*/
		public function getFeatures() {
			return $this->_features;
		}
		public function getIncludes() {
			return $this->_includes;
		}
		public function getRequires() {
			return $this->_requires;
		}
		public function getNamespaces() {
			return $this->_namespaces;
		}
		public function getUses() {
			return $this->_uses;
		}







		/**
			toString descriptive method
			@return is a String
		*/
		public function __toString() {
			return "name 			=> ".$this->_name
			  ."\npath 			=> ".$this->_path
			  ."\nsize 			=> ".$this->_size
			  ."\nlastModified 	=> ".$this->_lastModified
			  ."\nextension 		=> ".$this->_extension
			  ."\n\n";
		}

	}
?>

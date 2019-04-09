<?php
	
	/**
		Scans (recursively) a directory and returns all complete filenames within it in an array
		@param dir is the path to a directory to scan
		@param tab is the array in witch you want to put all filenames of the files of the directory
		@return is an array
	*/
	function scanDirectory($dir, $tab=array()) {
		if (!is_dir($dir)) { //is it a directory ?
			throw new RepositoryScanException("$dir is not a directory");
		}
		if (!($dh = opendir($dir))) { //do we have access rights ?
			throw new RepositoryScanException("Access denied to $dir");	
		}
		
		$dirsInDir = array(); //To save directories in current directory
		while (($item = readdir($dh)) !== false) { //Go through everything in directory
			if ($item == "." || $item == "..") { //Avoid going backward in directories
				continue; 
			}

			$fullPathToItem = $dir."/".$item;

			//First all files have to be added to $tab. Then, we can enter into directories
			if (is_dir($fullPathToItem)) {
				array_push($dirsInDir, $fullPathToItem);
			}
			else {
				array_push($tab, $fullPathToItem); //Save all filenames in $tab
			}
		}
		closedir($dh);

		//Now we enter into each sub directory
		foreach ($dirsInDir as $subDir) {
			$tab = array_merge($tab, scanDirectory($subDir, $tab));
		}

		//Finally return the complete $tab with all filenames
		return array_unique($tab);
	}





	/**
		new recursive function to scan the directory, much more efficient.
		@param repoPath is the path to the directory to scan
		@param dir is the path to a directory to scan (String), like repoPath, 
			but this parameter will change every time the function calls itself, and become the current subdirectory to scan
		@param subDirectoriesToIgnore is an array of directories that won't be scanned.
			Path has to be given from the repository.
			Example : if the repository is Pricer and you want to ignore the directory 
			/var/www/html/Pricer/programs/ignore : then you should precise 
			programs/ignore.
		@param filesToIgnore is an array of files to ignore. Same rule as for the 
			directories
		@param results (reference) is the array ot store the results in. the results 
			are absolute paths
	*/
	function getDirContent($repoPath, $dir, $subDirectoriesToIgnore=array(), 
							$filesToIgnore=array(), &$results=array()) {
		if (!is_dir($dir)) { //is it a directory ?
			throw new RepositoryScanException("$dir is not a directory");
		}
		if (!is_readable($dir)) { //do we have access rights ?
			throw new RepositoryScanException("Access denied to $dir");	
		}

		$files = scandir($dir);

		foreach ($files as $item) {
			if (!in_array($item, array('.', '..'))) {

				$path = realpath($dir.DIRECTORY_SEPARATOR.$item);

				$pathFromRepo = substr($path, strlen($repoPath) + 1);

				if (!is_dir($path)) {
					if (!in_array($pathFromRepo, $filesToIgnore)) {
						array_push($results, $path);
					}
				} 
				else {
					if (!in_array($pathFromRepo, $subDirectoriesToIgnore)) {
						getDirContent($repoPath, $path, $subDirectoriesToIgnore,
										$filesToIgnore, $results);
					}
				}	
			}
		}

		return $results;
	}



	/**
		Take a filenames array and return a new array containing only file with one of the given extensions. 
		@param filenames is an array
		@param extensions is an array
		@param keepNoExtension is a bool, allows to also put files without extensions 
			in the returned array
		@return is an array
	*/
	function keepSpecificTypesOnly($filenames, $extensions, $keepNoExtensionFiles=false) {
		$output = array();
		 foreach ($filenames as $filename) {
		 	if ($keepNoExtensionFiles && strpos(basename($filename), '.') === false) {
		 		array_push($output, $filename);
		 		continue;
		 	} 
		 	foreach ($extensions as $extension) {
		 		if (endswith($filename, $extension)) {
					array_push($output, $filename);
				} 
		 	}
		}
		return $output;
	}


	/**
		Take the URL of a github repository or a path of the directory and gives back 
		the name of the repo
		@param $path is a String
		@return is a String
	*/
	function getRepoName($path) {
		return @end(explode("/", $path));
	}


?>
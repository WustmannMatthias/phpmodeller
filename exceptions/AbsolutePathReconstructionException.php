<?php 
	/**
		§§ Generate exception messages
	*/

	class AbsolutePathReconstructionException extends Exception {
		
		public function __construct($file, $dependency, $line) {
			$message = "Couldn't reconstrut absolute path of dependency $dependency
				included in $file line $line. Dependency probably doesn't exists.";
			parent::__construct($message);
		}

	}
?>
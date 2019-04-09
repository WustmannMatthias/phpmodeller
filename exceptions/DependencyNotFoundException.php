<?php 
	/**
		§§ Generate exception messages
	*/
	class DependencyNotFoundException extends Exception {
		
		public function __construct($file, $dependency, $line) {
			$message = "File $dependency included in $file line $line doesn't exist. ";

			parent::__construct($message);
		}

	}
?>
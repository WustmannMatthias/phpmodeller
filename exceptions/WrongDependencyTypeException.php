<?php 
	/**
		§§ Generate exception messages
	*/
	class WrongDependencyTypeException extends Exception {
		
		public function __construct($nodePath, $line, $type) {
			$message = "Dependency of type $type found in node $nodePath (line 
						$line): should only be either include or require.";

			parent::__construct($message);
		}

	}
?>
<?php
	/**
		§§ Generate exception messages
	*/
	class UnunderstoodVariableDeclarationException extends Exception {

		public function __construct($file, $line) {
			$message = "File $file : Variable declaration should have been found 
						in following line $line but wasen't.";

			parent::__construct($message);
		}

	}
?>
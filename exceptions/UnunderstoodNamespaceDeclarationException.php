<?php
	/**
		§§ Generate exception messages
	*/
	class UnunderstoodNamespaceDeclarationException extends Exception {

		public function __construct($file, $line) {
			$message = "Namespace declaration wasen't understood in file $file line $line";

			parent::__construct($message);
		}

	}
?>
<?php
	/**
		§§ Generate exception messages
	*/
	class FileNotFoundException extends Exception {

		public function __construct($file) {
			$message = "File $file was not found.";
			parent::__construct($message);
		}
		
	}
?>
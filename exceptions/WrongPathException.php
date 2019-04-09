<?php 
	/**
		§§ Generate exception messages
	*/
	class WrongPathException extends Exception {
		
		public function __construct($fullPath, $repoName) {
			$message = "Tried to construct path relative to repository $repoName, 
			but path $fullPath doesn't contain it. ";

			parent::__construct($message);
		}

	}
?>
<?php
	/**
		§§ Generate exception messages
	*/
	class RepositoryScanException extends Exception {
		
		function __construct($message) {
			parent::__construct($message);
		}
		
	}
?>
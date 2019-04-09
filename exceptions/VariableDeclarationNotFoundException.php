<?php
	/**
		§§ Generate exception messages
	*/
	class VariableDeclarationNotFoundException extends Exception {

		public function __construct($file, $variables) {
			$message = "At least one of those variable declarations weren't found in $file : ";
			foreach ($variables as $variable) {
				$message.= $variable." ; ";
			}
			$message = substr($message, 0, strlen($message) - 2);

			parent::__construct($message);
		}

	}
?>
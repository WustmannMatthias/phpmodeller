<?php
	session_start();

	foreach (parse_ini_file('data/general_settings/database') 
		as $key => $value) {
		$_SESSION[$key] = $value;	
	}

?>

<!DOCTYPE html>
<html lang="en">
	<head>
		<title>PHP Modeller</title>
		<meta charset="UTF-8" />
		<meta name="viewport" content="width=device-width, initial-scale=1" />

		<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" />

		<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
		<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>

		<link rel="stylesheet" type="text/css" href="style/stylesheet.css" />

	</head>
	<body>
		<div class="container-fluid">
			<div class="row">
				<!--MAIN SCREEN-->
				<div class="col-lg-10 no_margin">
					<main class="container">
						<?php 
							if (isset($_GET['home']) 
								|| $_SERVER['QUERY_STRING'] == "") {
								include "frames/home.php";
							}						
							else if (isset($_GET['new_project'])) {
								include "frames/new_project.php"; 	
							}
							else if (isset($_GET['project'])) {
								include "frames/project_manager.php";
							}
							else if (isset($_GET['features'])) {
								include "frames/features.php";
							}
							else if (isset($_GET['packages'])) {
								include "frames/packages.php";
							}
							else {
								echo "404 not found";
							}
						?>
					</main>
				</div>

				<!--ASIDE MENU-->
				<div class="col-lg-2 no_margin">
					<?php include "frames/aside.php" ?>
				</div>

			</div>
		</div>
	</body>
</html>
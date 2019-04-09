
<?php
	

?>

<div class="row">
	<h1 class="center">Add a new project in database</h1>

</div>

<div class="row">
	<form class="col-lg-6 col-lg-offset-2 form-horizontal" method="post" action="index.php?new_project">
		<div class="row form-group">
			<label class="col-lg-6 control-label">Name of the project</label>
			<input class="col-lg-6" type="text" name="project" required="required" value="<?php if (isset($_POST['project'])) echo $_POST['project'];?>"/>
		</div>

		<?php
			/**
				Check if a directory corresponding to the given name exists in data/projects.
			*/

			$askForSettings = FALSE;

			if (isset($_POST['project'])) {
				$project = $_POST['project'];

				if (!is_dir(__DIR__."/../data/projects/$project")) {
					echo "<div class='row'>
							<div class='col-lg-8 col-lg-offset-4 alert alert-warning'>No directory with the given name was found in data/projects. Make sure to clone the repository before continuing.</div>
						</div>";
				}
				else if (in_array($project, $_SESSION['PROJECTS'])) {
					echo "<div class='row'>
							<div class='col-lg-8 col-lg-offset-4 alert alert-warning'>Project is already in database.</div>
						</div>";
				}
				else {
					$askForSettings = TRUE;
					$_SESSION['project'] = $project;
				}
			}


		?>
		
		<div class="row">
			<button class="btn btn-primary pull-right" type="submit" name="submit">Submit</button>
		</div>

	</form>
</div>






<?php
	if ($askForSettings) {

?>


<div class="row">
	<br>

	<h2>Settings</h2>

	<form class="col-lg-6 col-lg-offset-2 form-horizontal crawler_caller" method="post" action="index.php?new_project">
		<div class="row form-group">
			<label class="col-lg-6 control-label">Files to analyse (extensions)</label>
			<input class="col-lg-6" type="text" name="extensions" required="required" value="" />
		</div>
		
		<div class="row form-group">
			<label class="col-lg-6 control-label">Analyse files without extensions</label>
			
			<label class="col-lg-1 control-label">yes</label>
			<input class="col-lg-1" type="radio" name="withoutExtension" required="required" value="TRUE" />
			
			<label class="col-lg-1 control-label">no</label>
			<input class="col-lg-1" type="radio" name="withoutExtension" required="required" value="FALSE" />
		</div>

		<div class="row form-group">
			<label class="col-lg-6 control-label">Feature declaration</label>
			<input class="col-lg-6" type="text" name="feature" required="required" value="" />
		</div>

		<div class="row form-group">
			<label class="col-lg-6 control-label">Sub-directories to ignore</label>
			<input class="col-lg-6" type="text" name="subDirectories" value="" />
		</div>

		<div class="row form-group">
			<label class="col-lg-6 control-label">Files to ignore</label>
			<input class="col-lg-6" type="text" name="filesToIgnore" value="" />
		</div>

		<div class="row">
			<button class="btn btn-primary pull-right" type="submit" name="changeSettings">Load project</button>
		</div>
	</form>
</div>

<?php
	}

	/**
		SETTINGS
	*/
	if (isset($_POST['changeSettings'])) {

		$project = $_SESSION['project'];

		$projectsSettingsDirectory = __DIR__."/../data/projects_settings";
		if (!is_dir($projectsSettingsDirectory)) {
			mkdir($projectsSettingsDirectory);
		}

		$settingsFile = "$projectsSettingsDirectory/$project";
		
		$settings = "";
		if (isset($_POST['extensions'])) $settings.="EXTENSIONS=".$_POST['extensions']."\n";
		if (isset($_POST['withoutExtension'])) $settings.="NO_EXTENSION_FILES=".$_POST['withoutExtension']."\n";
		if (isset($_POST['feature'])) $settings.="FEATURE_SYNTAX=".$_POST['feature']."\n";
		if (isset($_POST['subDirectories'])) $settings.="SUB_DIRECTORIES=".$_POST['subDirectories']."\n";
		if (isset($_POST['filesToIgnore'])) $settings.="FILES=".$_POST['filesToIgnore']."\n"; else $settings.="FILES=";

		file_put_contents($settingsFile, $settings);



		/**
			CREATE FIRST ITERATION CONFIG FILE
		*/
		$iterationFile = __DIR__."/../data/general_settings/iteration";

		$iterationSettings = "REPOSITORY=$project\n";
		$iterationSettings.= "ITERATION_NAME=initialisation\n";
		$iterationSettings.= "DATE_BEGIN=1970-01-01\n";
		$iterationSettings.= "TIME_BEGIN=00:00\n";
		$iterationSettings.= "DATE_END=".date('Y-m-d')."\n";
		$iterationSettings.= "TIME_END=".date('H:i')."\n";

		file_put_contents($iterationFile, $iterationSettings);






		/**
			LAUNCH ENGINE
		*/
		//header("Location: ../crawler.php");
		echo "<script src='https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js'></script>";
		echo "<script type='text/javascript' src='style/loading.js'>
				</script>";


	}
?>

<br><br><br>
<div class="row">
	<div id="loading" class=""></div>
</div>
<div class="row">
	<pre id="result" class="center-block"></pre>
</div>
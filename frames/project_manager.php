
<?php
	if (isset($_GET['project'])) {
		$project = $_GET['project'];
	}
	else {
		echo "What are u doing here ?";
		exit();
	}


	$settingsFile = __DIR__."/../data/projects_settings/$project";
	$projectDirectory = __DIR__."/../data/projects/$project";

	//Check if project is loadable
	if (!is_dir($projectDirectory)) {
		echo "<div class='alert alert-warning'>Ooops ! The directory of the project wasn't found in data/projects. Make sure you clone it before continuing.</div>";
	}
	else {









	/**
		SETTINGS
	*/

	if (isset($_POST['changeSettings'])) {

		$settings = "";
		if (isset($_POST['extensions'])) $settings.="EXTENSIONS=".$_POST['extensions']."\n";
		if (isset($_POST['withoutExtension'])) $settings.="NO_EXTENSION_FILES=".$_POST['withoutExtension']."\n";
		if (isset($_POST['feature'])) $settings.="FEATURE_SYNTAX=".$_POST['feature']."\n";
		if (isset($_POST['subDirectories'])) $settings.="SUB_DIRECTORIES=".$_POST['subDirectories']."\n";
		if (isset($_POST['filesToIgnore'])) $settings.="FILES=".$_POST['filesToIgnore']."\n";

		file_put_contents($settingsFile, $settings);
	}


	$settings = parse_ini_file($settingsFile);
?>

<h1 class="center"><?php echo $project ?></h1>
<div class="row">

	<h2 class="first_h2">Settings</h2>

	<form class="col-lg-6 col-lg-offset-2 form-horizontal" method="post" action="index.php?project=<?php echo $project;?>">
		<div class="row form-group">
			<label class="col-lg-6 control-label">Files to analyse (extensions)</label>
			<input class="col-lg-6" type="text" name="extensions" required="required" value="<?php echo $settings['EXTENSIONS'];?>" />
		</div>

		<div class="row form-group">
			<label class="col-lg-6 control-label">Analyse files without extensions</label>

			<label class="col-lg-1 control-label">yes</label>
			<input class="col-lg-1" type="radio" name="withoutExtension" required="required" value="TRUE"
				<?php if ($settings['NO_EXTENSION_FILES']) echo "checked='checked'";?> />

			<label class="col-lg-1 control-label">no</label>
			<input class="col-lg-1" type="radio" name="withoutExtension" required="required" value="FALSE"
				<?php if (!$settings['NO_EXTENSION_FILES']) echo "checked='checked'";?> />
		</div>

		<div class="row form-group">
			<label class="col-lg-6 control-label">Feature declaration</label>
			<input class="col-lg-6" type="text" name="feature" required="required" value="<?php echo $settings['FEATURE_SYNTAX'];?>" />
		</div>

		<div class="row form-group">
			<label class="col-lg-6 control-label">Sub-directories to ignore</label>
			<input class="col-lg-6" type="text" name="subDirectories" value="<?php echo $settings['SUB_DIRECTORIES'];?>" />
		</div>

		<div class="row form-group">
			<label class="col-lg-6 control-label">Files to ignore</label>
			<input class="col-lg-6" type="text" name="filesToIgnore" value="<?php echo $settings['FILES'];?>" />
		</div>

		<div class="row">
			<button class="btn btn-primary pull-right" type="submit"
					name="changeSettings">Change settings</button>
		</div>
	</form>
</div>











<?php

	/**
		NEW ITERATION
	*/
		$iterationFile = __DIR__."/../data/general_settings/iteration";

		if (isset($_POST['registerIteration'])) {
			$iterationSettings = "REPOSITORY=".$project."\n";
			if (isset($_POST['iterationName'])) $iterationSettings.="ITERATION_NAME=".$_POST['iterationName']."\n";
			if (isset($_POST['iterationBegin'])) $iterationSettings.= "ITERATION_BEGIN=".$_POST['iterationBegin']."\n";
			if (isset($_POST['iterationEnd'])) $iterationSettings.= "ITERATION_END=".$_POST['iterationEnd']."\n";

			file_put_contents($iterationFile, $iterationSettings);

			echo "<script type='text/javascript' src='style/loading.js'>
					</script>";

			//header("Location: crawler.php");
		}
	?>


	<br><br>
	<div class="row">
		<h2>New Iteration</h2>

		<form class="col-lg-6 col-lg-offset-2 form-horizontal crawler_caller" method="post" action="index.php?project=<?php echo $project;?>">
			<div class="row form-group">
				<label class="col-lg-6 control-label">Iteration reference</label>
				<input class="col-lg-6" type="text" name="iterationName" required="required" />
			</div>

			<div class="row form-group">
				<label class="col-lg-6 control-label">Tag/Release begin</label>
				<input class="col-lg-6" type="text" name="iterationBegin" required="required" />
			</div>

			<div class="row form-group">
				<label class="col-lg-6 control-label">Tag/Release end</label>
				<input class="col-lg-6" type="text" name="iterationEnd" required="required" />
			</div>

			<br>
			<div class="row">
				<div class="col-lg-8 col-lg-offset-4 alert alert-info">Make sure to have pulled the project after the last commit of the iteration, otherwises data won't be consistent !</div>
			</div>

			<div class="row">
				<button class="btn btn-primary pull-right" type="submit" name="registerIteration">Register iteration</button>
			</div>

	</div>


<br><br><br>
<div id="loading" class="center-block"></div>
<pre id="result" class="center-block"></pre>




	<?php
		} //End of the else -> if directory of the projects exists
	?>

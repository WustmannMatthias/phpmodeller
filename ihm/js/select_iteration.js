
$(function() {

	$('#project_select select').change(function() {
		if ($(this).val() != "none") {
			var project = $(this).val();

			$.post("programs/get_iterations.php", {project: project})
				.done(function(data) {
					$('#iteration_select select').html(data);
					$('#iteration_select').show();
				});
		}
		else {
			$('#iteration_select').hide();
		}
	});

	$('#iteration_select select').change(function() {
		if ($(this).val() != "none") {
			$('#iteration_submit').show();
			//...
		}
		else {
			$('#iteration_submit').hide();
		}
	});
});
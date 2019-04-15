function loading(xhr) {
	$('#loading').html(
		"<br><br><p class='center'>Please wait for project to be analysed...<br><br>"
		+ "<img class='center' src='images/gears.gif' alt='loading gif' /></p>"
	);
	$('#loading').show();
	$('#result').hide();
}


function displayResult(data) {
    $('#loading').hide();
	console.log(data);
	$('#result').html(data);
	$('#result').show();
}


function ajaxFailed(jqXHR, textStatus, errorThrown) {
	$('#loading').hide();
	$('#result').html(textStatus);
	$('#result').show();
}




$(function() {

    $("#upload_button").on("click", function() {
        repository = $("input[name=repository]").val();
        url = $("input[name=url]").val();
        composer = $("input[name=composer]:checked").val();
        extensions = $("input[name=extensions]").val();
        without_extensions = $("input[name=without_extensions]:checked").val();
        sub_directories = $("input[name=sub_directories]").val();
        files_to_ignore = $("input[name=files_to_ignore]").val();

        data = JSON.stringify({
            repository_name: repository,
            repository_url: url,
            composer: composer,
            extensions: extensions,
            no_extension_files: without_extensions,
            directories_to_ignore: sub_directories,
            files_to_ignore: files_to_ignore
        });

		console.log(data)

        $.ajax({
			method: 'POST',
			url: 'http://localhost:5001/api/v1.0/model',
			data: data,
			contentType: 'application/json; charset=utf-8',
			dataType: 'json',
			crossdomain: true,
            beforeSend: loading
        }).done(displayResult).fail(ajaxFailed);
    });
});

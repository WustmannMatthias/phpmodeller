function loading(xhr) {
	$('#loading').html(
		"<br><br><p class='center'>Loading...<br><br>"
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

/**
 * Convert a json list into a <option/> tags
 */
function jsonToOptions(json, selected) {
	options = "";
	for (let i = 0; i < json.length; i++) {
		option = json[i];
		if (selected && option == selected) {
			options += "<option selected='selected' value='" + option + "'>" + option + "</option>";
		}
		else {
			options += "<option value='" + option + "'>" + option + "</option>";
		}
	}
	return options;
}


$(function() {

	$.ajax({
		method: 'GET',
		url: 'http://localhost:5001/api/v1.0/repos',
		dataType: 'json',
		crossdomain: true,
		beforeSend: loading
	}).done(function(data) {
		$('select[name=repository]').html(jsonToOptions(data));
		$('#loading').hide();
	}).fail(ajaxFailed);


	$('button[name=submit]').on('click', function() {
		repo = $('select[name=repository]').val();
		begin = $('input[name=release_begin]').val();
		end = $('input[name=release_end]').val();

		$.ajax({
			method: 'GET',
			url: 'http://localhost:5001/api/v1.0/features/' + repo + '/' + begin + '/' + end,
			dataType: 'json',
			crossdomain: true,
			beforeSend: loading
		}).done(displayResult).fail(ajaxFailed);
	});
});

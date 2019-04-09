function addPackageInput(multiple) {
	var packages;
	$.post({
		url: "programs/get_properties_list.php", 
		data: {property: 'package'},
		dataType: 'json', 
		async: false
	}).done(function(data) {
		packages = data;
	});

	var select = "<select class='form-control' name='package'";
	if (multiple) select += "multiple";
	select += ">";

	for (let i = 0; i < packages.length; i++) {
		select += "<option value='" + packages[i] + "'>" + packages[i] + "</option>";
	}

	select += "</select>";
	return select;
}

function addProjectInput(multiple) {
	var projects;
	$.post({
		url: "programs/get_properties_list.php", 
		data: {property: 'project'},
		dataType: 'json', 
		async: false
	}).done(function(data) {
		projects = data;
	});

	var select = "<select class='form-control' name='project'";
	if (multiple) select += "multiple";
	select += ">";

	for (let i = 0; i < projects.length; i++) {
		select+= "<option value='" + projects[i] + "'>" + projects[i] + "</option>";
	}
	select += "</select>";

	return select;
}



function buildForm(params, query_id) {
	//var html = "<form class='col-lg-8 col-lg-offset-2 form-horizontal query_form' action='php/query_db.php?query=" + query_id + "' method='post'>";
	var html = "<form id='query_form' class='col-lg-8 col-lg-offset-2 form-horizontal' data-identifier='" + query_id + "' onsubmit='queryDb()'>";


	for (var key in params) {
	    // skip loop if the property is from prototype
	    if (!params.hasOwnProperty(key)) continue;
	    var multiple = params[key] == 'list' ? true : false;
		if (key == 'package') {
			html += "<div class='row form-group'>\
					<label class='col-lg-3'>Select the package</label>";
			html += addPackageInput(multiple);
			html += "</div>";
		}
		else if (key == 'project') {
			html += "<div class='row form-group'>\
					<label class='col-lg-3'>Select the project</label>";
			html += addProjectInput(multiple);
			html += "</div>";
		}
	}

	html += "<div class='row form-group'>\
				<button class='btn btn-primary' type='submit' \
						name='submit'>Validate\
				</button> \
			</div>";
	html += "</form>";

	return html;
}



function queryDb() {
	event.preventDefault();
	var query_id = $('#query_form').data('identifier');

	var formData = $('#query_form').serializeArray();
	var newFormData = {};
	for (let i = 0; i < formData.length; i++) {
		var name = formData[i].name;
		var value = formData[i].value;
		
		if (newFormData.hasOwnProperty(name)) {
			if (typeof newFormData[name] == "string") {
				newFormData[name] = [newFormData[name]];
				newFormData[name].push(value);
			}
			else {
				newFormData[name].push(value);	
			}
		}
		else {
			newFormData[name] = value;
		}

	}
	console.log(newFormData);

	$.post({
		url: "programs/packages_query_db.php?query_id=" + query_id,
		data: newFormData,
		dataType: 'json',
		async: false
	}).done(function(data) {
		displayData(data);
	});
}


function displayData(data) {
	var duration = data.duration;
	console.log(duration);
	delete data.duration;

	result = data.result;
	if (!result.length) {
		result = [result];
	}
	console.log(typeof result[0]);
	console.log(result);

	var table = "";
	if (result.length == 0) {
		table = "<p>No results.</p>";
	}
	else {
		table += "<table class='table table-bordered table-striped table-hover'>";

		//header
		table += "<thead class=''>";
		var keys = Object.keys(result[0]);
		table += "<tr class='table-info'>";
		table += "<th scope='col'>#</th>";
		for (let i = 0; i < keys.length; i++) {
			table += "<th>" + keys[i] + "</th>";
		}
		table += "</tr>"; 
		table += "</thead>";


		//body
		table += "<tbody>";		
		for (let i = 0; i < result.length; i++) {
			table += "<tr>";
			table += "<th scope='row'>" + (i + 1) + "</th>";
			for (key in result[i]) {
				if (result[i].hasOwnProperty(key)) {
					table += "<td>";
					table += result[i][key];
					table += "</td>";
				}
			}
			table += "</tr>";
		}
		table += "</tbody>";

		table += "</table>";

		var p = "<p>";
		p += "Running time : " + duration.toPrecision(3) + " seconds.";
		p += "</p>";

		var html = p + table

	}

	$('#query_result').html(html);

}




$(function() {

	$('.query_li').click(function() {
		var query_id = $(this).data('identifier');

		$(".query_li").removeClass("active");
		$(this).addClass("active");

		$("#query_result").html("");


		$.post({
			url: "programs/get_query_data.php", 
			data: {query_id: query_id}, 
			dataType: 'json', 
			async: false
		}).done(function(data) {
			
			//Prepare new #select_params row
			var form = buildForm(data.params, query_id);
			$('#select_params').html(form);

		});
	});	


	



});

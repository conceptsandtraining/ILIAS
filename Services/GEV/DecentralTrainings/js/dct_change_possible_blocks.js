$(document).ready(function() {

	var selected_block = $('#blocks option:selected').val();

	if(typeof selected_block === "undefined") {
		$('#duration\\[start\\]\\[time\\]_h').attr('disabled',true);
		$('#duration\\[start\\]\\[time\\]_m').attr('disabled',true);
		$('#duration\\[end\\]\\[time\\]_h').attr('disabled',true);
		$('#duration\\[end\\]\\[time\\]_m').attr('disabled',true);
		$('#form_dct_ab input:submit').addClass('submit_disabled');
		$('#form_dct_ab input:submit').removeClass('submit');
		$('#form_dct_ab input:submit').attr('disabled',true);
		$('#dct_conten_required').hide();
		$('#dct_content_byline').hide();
	}

	$('#wp').attr('readonly',true);

	$(document).on("change","select","", function(e) {
		var target_id = $(e.target).attr("id");
		
		switch(target_id) {
			case "topic":	changeBuildingBlocks();
						break;
			case "blocks":	changeBuildingBlockInfos();
						break;
			case "duration[start][time]_h":
			case "duration[start][time]_m":
			case "duration[end][time]_h":
			case "duration[end][time]_m":
							calculateCreditPoints();
						break;
		}
	});

	$('#content').on("input propertychange", function(e) {
		var val = $('#content').val().trim();

		if(val.length > 0) {
			$('#form_dct_ab input:submit').removeClass('submit_disabled');
			$('#form_dct_ab input:submit').addClass('submit');
			$('#form_dct_ab input:submit').attr('disabled',false);
		} else {
			$('#form_dct_ab input:submit').addClass('submit_disabled');
			$('#form_dct_ab input:submit').removeClass('submit');
			$('#form_dct_ab input:submit').attr('disabled',true);
		}
	});
});

/**
*change the elements in select input ui for building block
*
*/
function changeBuildingBlocks() {
	$('#duration\\[start\\]\\[time\\]_h').attr('disabled',true);
	$('#duration\\[start\\]\\[time\\]_m').attr('disabled',true);
	$('#duration\\[end\\]\\[time\\]_h').attr('disabled',true);
	$('#duration\\[end\\]\\[time\\]_m').attr('disabled',true);
	$('#form_dct_ab input:submit').addClass('submit_disabled');
	$('#form_dct_ab input:submit').removeClass('submit');
	$('#form_dct_ab input:submit').attr('disabled',true);



	var selected = $('#topic option:selected').val();
	$.getJSON(il.buildingBlock,"selected="+selected+"&type=0", function(data) {
		$('#blocks').empty();
		$('#content').val("");
		$('#target').val("");
		$('#wp').val("");

		var items = [];

		$.each(data, function(key,val) {
			items.push('<optgroup label="'+key+'">');

			$.each(val, function(key, val) {
				items.push('<option value="' + val[0] + '">' + val[1] + '</option>');
			});
		});
		$('#blocks').append(items.join(""));
	});
}

/**
*change the building block information
*
*/
function changeBuildingBlockInfos() {
	$('#duration\\[start\\]\\[time\\]_h').attr('disabled',false);
	$('#duration\\[start\\]\\[time\\]_m').attr('disabled',false);
	$('#duration\\[end\\]\\[time\\]_h').attr('disabled',false);
	$('#duration\\[end\\]\\[time\\]_m').attr('disabled',false);
	$('#form_dct_ab input:submit').attr('disabled',false);
	$('#form_dct_ab input:submit').removeClass('submit_disabled');
	$('#form_dct_ab input:submit').addClass('submit');

	$('#content').attr('disabled',true);
	$('#content').attr('name', null);
	$('#content').css('background-color', '#DDD');
	$('#dct_conten_required').hide();
	$('#dct_content_byline').hide();
	$('#target').attr('disabled',true);
	$('#target').attr('name', null);
	$('#target').css('background-color', '#DDD');

	var selected = $('#blocks option:selected').val();
	$.getJSON(il.buildingBlock,"selected="+selected+"&type=1", function( data ) {
		$('#content').val(data["content"]);
		$('#target').val(data["target"]);
		$('#isWP').val(data["wp"]);
		$('#isBlank').val(data["is_blank"]);

		if(data["is_blank"] == 1) {
			$('#content').attr('disabled',false);
			$('#content').attr('name', 'blank_content');
			$('#content').css('background-color', '#FFF');
			$('#target').attr('disabled',false);
			$('#target').attr('name', 'blank_target');
			$('#target').css('background-color', '#FFF');
			$('#form_dct_ab input:submit').addClass('submit_disabled');
			$('#form_dct_ab input:submit').removeClass('submit');
			$('#form_dct_ab input:submit').attr('disabled',true);
			$('#dct_conten_required').show();
			$('#dct_content_byline').show();
		}

		calculateCreditPoints();
	});
}

function calculateCreditPoints() {
	var isWP = $('#isWP').val();

	var start_h = parseInt($('#duration\\[start\\]\\[time\\]_h option:selected').val());
	var start_m = parseInt($('#duration\\[start\\]\\[time\\]_m option:selected').val());
	var end_h = parseInt($('#duration\\[end\\]\\[time\\]_h option:selected').val());
	var end_m = parseInt($('#duration\\[end\\]\\[time\\]_m option:selected').val());

	var tot_h = 0;
	var tot_m = 0;

	if(end_m < start_m) {
		tot_h = -1;
		tot_m = end_m + (60 - start_m);
	} else {
		tot_m = end_m - start_m;
	}

	tot_h =  tot_h + (end_h - start_h);
	tot_m = tot_m + (tot_h * 60);
	tot_m = tot_m / 45;
	credit_points = Math.floor( tot_m );
	calc = tot_m - credit_points;
	calc = calc.toFixed(1);

	if(calc > 0 && calc < 0.6) {
		credit_points += 0.3; 
	}

	if(calc >= 0.6 && calc < 1) {
		credit_points += 0.6; 
	}

	if(isWP == "Ja") {
		$('#wp').val(credit_points);
	} else {
		$('#wp').val(0);
	}
	
	$('#ue').val(credit_points);
}
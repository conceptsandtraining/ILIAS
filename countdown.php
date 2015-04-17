<?xml version="1.0" encoding="UTF-8"?>
<?php

include("./_launchDate.php");

if( $delta <= 0 ){
    header('Location: ./index.php');
    die();
}


?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
<head>
	<title> SEEPEX.CAMPUS </title>
	<meta http-equiv="content-type" content="text/html; charset=UTF-8" />
	<meta http-equiv="content-language" content="" />
	<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1" />

<script type="text/javascript">

		var javascript_countdown = function () {
			var time_left = 10; //number of seconds for countdown
			var keep_counting = 1;

			function countdown() {
				if(time_left < 2) {
					keep_counting = 0;
				}
				time_left = time_left - 1;
			}

			function add_leading_zero(n) {
				if(n.toString().length < 2) {
					return '0' + n;
				} else {
					return n;
				}
			}

			function format_output() {

				var hours, minutes, seconds, days;
				seconds = time_left % 60;
				minutes = Math.floor(time_left / 60) % 60;
				//hours = Math.floor(time_left / 3600);
				hours = Math.floor(time_left / 3600) % 24;

				ddays = Math.floor(time_left / (3600 * 24));
				seconds = add_leading_zero( seconds );
				minutes = add_leading_zero( minutes );
				hours = add_leading_zero( hours );

				var _ret = {
					'days' 	: ddays,
					'hours' 	:  hours,
					'minutes' 	:  minutes,
					'seconds' 	:  seconds
				};
				return _ret
			}

			function show_time_left() {
				var _out = format_output();
				document.getElementById('days').innerHTML = _out.days;
				document.getElementById('hours').innerHTML = _out.hours;
				document.getElementById('minutes').innerHTML = _out.minutes;
				document.getElementById('seconds').innerHTML = _out.seconds;
			}


			function no_time_left() {
				//alert("time's up");
				location.href="./index.php";
			}

			return {
				count: function () {
					countdown();
					show_time_left();
				},
				timer: function () {
					javascript_countdown.count();

					if(keep_counting) {
						setTimeout("javascript_countdown.timer();", 1000);
					} else {
						no_time_left();
					}
				},

				setTimeLeft: function (t) {
					time_left = t;
					if(keep_counting == 0) {
						javascript_countdown.timer();
					}
				},
				init: function (t) {
					time_left = t;
					javascript_countdown.timer();
				}
			};
		}();


		function __init__(){
			//time to countdown in seconds
			<?php
				include('./_launchDate.php');
			?>
			javascript_countdown.init(<?php echo($delta);?>);
		}

	</script>

	<style type="text/css">	

		body {
			margin:0px 0px; 
			padding:0px;
			text-align:center;
			background-color: #eaeef9; 
			background-color: #000; 
			
		}
		#stage{
			background-image: url(./seepex_login.jpg);
			background-repeat: no-repeat;
			width: 678px;
			height: 470px;
			/*border: 1px solid white;*/
			text-align: left;
			margin: 100px auto auto;
			color: black;
			font-family: Arial;
		}

		#text {
		  background-color: #afafaf;
		  color: black;
		  font-size: 20px;
		  height: 191px;
		  margin: 0 9px;
		  padding-left: 80px;
		  padding-right: 80px;
		  
		  position: absolute;
		  top: 370px;
		  width: 500px;
		}

		#text span{
			display: inline-block;
			padding-left: 58px;
			padding-top: 20px;
		}
		

		#numbers, #labels{
			color: #e02b22;
			width: 600px;
			font-weight:bold;
		}

		.number{
			font-size: 80px;
			float: left;
			margin-right: 15px;
			height:70px;
			width: 120px;
			text-align:center;
		}

		.label{
			font-size: 12px;
			float: left;
			width: 120px;
			text-align:center;
			margin-right: 15px;
			margin-top: 12px;
		}



	</style>


</head>
<body>

	
		<div id="stage">

			<div id="text">

				<span>
					Because of upgrades and data migration the <br>
					SEEPEX CAMPUS is down for maintenance.
					<br>
					We'll flow again in:
				</span>
				

				<div id="numbers">
					<div id="days" class="number">dd</div>
					<div id="hours" class="number">hh</div>
					<div id="minutes" class="number">mm</div>
					<div id="seconds" class="number">ss</div>
				</div>

				<div id="labels">
					<div id="l_days" class="label">days</div>
					<div id="l_hours" class="label">hours</div>
					<div id="l_minutes" class="label">minutes</div>
					<div id="l_seconds" class="label">seconds</div>
				</div>

						
			</div>

		</div>


	
	<script type="text/javascript">
		__init__();
	</script>

</body>

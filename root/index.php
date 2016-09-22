<?php

require_once 'path.php';

require_once ROOT_DIR.'/vendor/autoload.php';
require_once ROOT_DIR.'/library/_main.php';

($nowSession->r('userCreds') != null) && headerLocation("mail.php", true);
?>
<!DOCTYPE html>
<html>
<head>
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Dashboard</title>
</head>
<body>
	<div id="particles">
		<div id="login">
			<div><img src="load/img/logoHere.png"></div>
			<form method="POST" autocomplete="off">
				<input type="text"		name="username" placeholder="Email" required>
				<input type="password"	name="password" placeholder="Password" class="showpassword" required>
				<input type="hidden"	name="userwhom" placeholder="fullName">
				<input type="submit"	name="submitForm" value="Log in" id="getUser">
				<input type="submit"	name="submitForm" value="Create" id="makeUser">
				<button id="eyeView" class="eyeShow">Show Password</button>
			</form>
		</div>
	</div>
	<link href='https://fonts.googleapis.com/css?family=Open+Sans:400,600|Josefin+Sans:400' rel='stylesheet' type='text/css'>
	<link rel="stylesheet" type="text/css" href="load/css/login.css">
	<script type="text/javascript" src="bower_components/jquery/dist/jquery.min.js"></script>
	<script type='text/javascript' src='load/js/functions.js'></script>
	<script type='text/javascript' src='load/js/jquery.particleground.min.js'></script>
	<script type="text/javascript">

		$('#login').css({'margin-top': -($('#login').height() / 2)});

		$("#viewPassword").addClass("showEye");
		$("#viewPassword").removeClass("showEye");

		$(".showpassword").each(function (index, input)
		{
			var $input = $(input);
			$("#eyeView").addClass("eyeShow");
			$("#eyeView").on("click", function (e) {
				e.preventDefault();
				var change = "";
				if ($(this).html() === "Show Password") {
					$(this).html("Hide Password");
					$(this).addClass("eyeHide");
					$(this).removeClass("eyeShow");
					change = "text";
				} else {
					$(this).html("Show Password");
					change = "password";
					$(this).addClass("eyeShow");
					$(this).removeClass("eyeHide");
				}
				var rep = $("<input type='" + change + "' />")
							.attr("id", $input.attr("id"))
							.attr("name", $input.attr("name"))
							.attr('class', $input.attr('class'))
							.attr('placeholder', $input.attr("placeholder"))
							.attr("autocomplete", $input.attr("autocomplete"))
							.val($input.val())
							.insertBefore($input);
				$input.remove();
				$input = rep;
			});
		});

		$("#getUser").on("click", function(event)
		{
			disableSubmit($(this), 3000);
			disableSubmit($("#makeUser"), 3000);
			event.preventDefault();

			var jsnFormData = $(this).parent().serialize();

			$.post('<?php echo $ajxFile; ?>', { "liveCall" : "loginUser", "liveData" : jsnFormData }, function(ajaxResponse) { //make ajax call to ajaxRespnoser.php
				console.log(ajaxResponse);
				returnedData = ajaxResponse;
				console.log(returnedData["returnedData"]);
				switch (returnedData["returnedData"]) {
					case 'logmein':
						$("#login div").html("<h2 class='flickR'>SuccessFul Login</h2>");
						location.reload();
						break;
					case 'loginretry':
						$("#login div").html("<h2 class='flickR'>Try Again</h2>");
						break;
					case 'fields empty':
						$("#login div").html("<h2 class='flickR'>Error: Empty Fields</h2>");
						break;
					default:
						$("#login div").html("<h2 class='flickR'>Try Again</h2>");
				}
			});
		});

		$("#makeUser").on("click", function(event)
		{
			disableSubmit($(this), 3000);
			disableSubmit($("#getUser"), 3000);
			event.preventDefault();

			var jsnFormData = $(this).parent().serialize();

			$.post('<?php echo $ajxFile; ?>', { "liveCall" : "createUser", "liveData" : jsnFormData }, function(ajaxResponse) { //make ajax call to ajaxRespnoser.php

				var returnedData = ajaxResponse;
				console.log(returnedData["returnedData"]);
				switch (returnedData["returnedData"]) {
					case 'auth failed':
						$("#login div").html("<h2 class='flickR'>Auth Failed</h2>");
						break;
					case 'retryCreate':
						$("#login div").html("<h2 class='flickR'>Try Again</h2>");
						break;
					case 'successCreate':
						$("#login div").html("<h2 class='flickR'>User Created</h2>");
						location.reload();
						break;
					default:
						$("#login div").html("<h2 class='flickR'>Try Again</h2>");
				}
			});
		});

		$('#particles').particleground({ dotColor: '#fff', lineColor: '#fff', parallaxMultiplier: 7, particleRadius : 7, proximity: 77, directionX : 'center' });

	</script>
</body>
</html>

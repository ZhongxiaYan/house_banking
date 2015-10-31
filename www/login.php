<?php
	require_once '../lib/config.php';
	require_once '../lib/classes/user_main.php';
	require_once 'init.php';
?> 
<!DOCTYPE html>
<html>
	<head>
		<title>House Banking</title>
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
		<link href="css/bootstrap.min.css" rel="stylesheet">

		<script src="js/jquery-2.1.4.min.js"></script>
		<script src="js/bootstrap.min.js"></script>
		<script src="js/script.js"></script>
		<style>
		</style>
	</head>
	<body>
		<?php
			require_once 'navbar.php';
			if (isset($wrong_login)) {
				echo '<div style="color:red">Incorrect Email or Password! Please try again.</div>';
			}
		?>
		<form role="form" action="login.php?submission=login" method="post" id="login-form">
			<div class="form-group">
				<label>Email:</label>
				<input type="text" class="form-control" name="login-email" required>
			</div>
			<div class="form-group">
				<label>Password:</label>
				<input type="password" class="form-control" name="login-password" required>
			</div>
			<button type="submit" class="btn btn-default">Submit</button>
		</form>
	</body>
</html>
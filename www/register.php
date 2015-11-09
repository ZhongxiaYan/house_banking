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
		<style>
		</style>
	</head>
	<body>
		<?php
			require_once 'navbar.php';

			if (get_session_status() === 'register failed') {
				echo '<div style="color:red">Email is already taken! Please use another email.</div>';
			}
		?>
		<form role="form" action="register.php?submission=register_user" method="post" id="login-form">
			<div class="form-group">
				<label>First Name:</label>
				<input type="text" class="form-control" name="register-first-name" required>
			</div>
			<div class="form-group">
				<label>Last Name:</label>
				<input type="text" class="form-control" name="register-last-name" required>
			</div>
			<div class="form-group">
				<label>Email:</label>
				<input type="text" class="form-control" name="register-email" required>
			</div>
			<div class="form-group">
				<label>Password:</label>
				<input type="password" class="form-control" name="register-password" required>
			</div>
			<div class="form-group">
				<label>Reenter Password:</label>
				<input type="password" class="form-control" name="register-password-2" required>
			</div>
			<button type="submit" class="btn btn-default">Submit</button>
		</form>
	</body>
</html>
<?php
	require_once 'closer.php';
?>
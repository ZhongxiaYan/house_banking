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

			// display message to the user depending on how they got here
			$status = get_session_status();
			if ($status === 'wrong login') {
				echo '<div style="color:red">Incorrect Email or Password! Please try again.</div>';
			} else if ($status === 'deleted') {
				echo '<div style="color:red">Account deleted! Please contact an admin.</div>';
			} else if ($status === 'unverified') {
				echo '<div style="color:red">Account is not verified by admin yet.</div>';
			} else if ($status === 'not logged in') {
				echo '<div style="color:red">Please log in before visiting any other page.</div>';
			} else if ($status === 'not admin') {
				echo '<div style="color:red">Page requires administrator privileges. Please log in as admin.</div>';
			} else if ($status === 'just registered') {
				echo '<div style="color:green">Successfully registered. Please log in.</div>';
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
<?php
	require_once 'closer.php';
?>
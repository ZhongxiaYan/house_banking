<?php
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
			#main {
				margin: 20px;
			}
		</style>
	</head>
	<body>
		<?php
			require_once 'navbar.php';
		?>
		<div id="main">
			<?php
				// display message to the user depending on how they got here
				$status = get_session_status();
				switch ($status):
					case '0': ?>

					<?php break;
					case 'wrong login': ?>
					<div style="color:red">Incorrect Email or Password! Please try again.</div>

					<?php break;
					case 'deleted': ?>
					<div style="color:red">Account deleted! Please contact an admin.</div>
					
					<?php break;
					case 'unverified': ?>
					<div style="color:red">Account is not verified by admin yet.</div>

					<?php break;
					case 'not logged in': ?>
					<div style="color:red">Please log in before visiting any other page.</div>

					<?php break;
					case 'not admin': ?>
					<div style="color:red">Page requires administrator privileges. Please log in as admin.</div>

					<?php break;
					case 'just registered': ?>
					<div style="color:green">Successfully registered. Please log in.</div>

				<?php endswitch; ?>
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
		</div>
	</body>
</html>
<?php
	require_once 'closer.php';
?>
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
		<script src="js/register.js"></script>
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
			<?php if (get_session_status() === 'register failed'): ?>
			<div style="color:red">Email is already taken! Please use another email.</div>

			<?php elseif (get_session_status() === 'register code not found'): ?>
			<div style="color:red">Register code is incorrect! Please ask admin.</div>

			<?php endif; ?>
			
			<form role="form" action="register.php?submission=register_user" method="post" id="register-form">
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
					<input type="password" class="form-control" name="register-password" id="password" required>
				</div>
				<div class="form-group">
					<label>Reenter Password:</label>
					<input type="password" class="form-control" name="register-password-2" id="password-2" required>
				</div>
				<div class="form-group">
					<label>Secret Code:</label>
					<input type="text" class="form-control" name="register-code" required>
				</div>
				<button type="submit" class="btn btn-default">Submit</button>
			</form>
		</div>
	</body>
</html>
<?php
	require_once 'closer.php';
?>
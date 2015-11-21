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
		<script src="js/admin.js"></script>
		<style>
		</style>
	</head>
	<body>
		<?php
			require_once 'navbar.php';
			if (get_session_status() === 'admin changed user'): ?>
				<div style="color:green">Successfully changed user information.</div>
			<?php endif;

			// rebuild user list for admin.php to include unverified and deleted users
			$full_users_sql = $mysqli->query('SELECT * FROM ' . $config['db']['tables']['userinfo'] . ';');
			$full_users = array();
			while ($user = $full_users_sql->fetch_assoc()) {
				$user['name'] = $user['first_name'] . ' ' . substr($user['last_name'], 0, 1);
				$full_users[$user['id']] = $user;
			}
		?>
			
		<form class="form-inline" role="form" action="admin.php?submission=verify_users" method="post" id="verify-user-form">
			<div class="table-responsive col-md-2">
				<h3>Verify Users:</h3>
				<table class="table table-bordered table-striped">
					<thead>
						<tr>
							<th>User</th>
							<th></th>
						</tr>
					</thead>
					<tbody>
						<tr>
							<td>All</td>
							<div class="checkbox"> 
								<td><input type="checkbox" class="all" name="all" value="on"></td>
							</div>
						</tr>

						<?php foreach ($full_users as $id => $user): ?>
						<?php if (!$user['verified']): ?>
						
						<tr>
							<td><?= $user['name'] ?></td>
							<div class="checkbox">
								<td>
									<input type="checkbox" name=<?= 'select' . $id ?> value="on">
								</td>
							</div>
						</tr>

						<?php endif; ?>
						<?php endforeach; ?>

					</tbody>
				</table>
			<input type="hidden" name="session_token" value=<?= htmlspecialchars($curr_user->session_token) ?>>
			<button type="submit" class="btn btn-default">Submit</button>
			</div>
		</form>

		<form class="form-inline" role="form" action="admin.php?submission=make_admins" method="post" id="make-admin-form">
			<div class="table-responsive col-md-2">
				<h3>Make Admins:</h3>
				<table class="table table-bordered table-striped">
					<thead>
						<tr>
							<th>User</th>
							<th></th>
						</tr>
					</thead>
					<tbody>
						<tr>
							<td>All</td>
							<div class="checkbox"> 
								<td><input type="checkbox" class="all" name="all" value="on"></td>
							</div>
						</tr>

						<?php foreach ($full_users as $id => $user): ?>
						<?php if (!$user['admin']): ?>
						
						<tr>
							<td><?= $user['name'] ?></td>
							<div class="checkbox">
								<td>
									<input type="checkbox" name=<?= 'select' . $id ?> value="on">
								</td>
							</div>
						</tr>
						
						<?php endif; ?>
						<?php endforeach; ?>

					</tbody>
				</table>
			<input type="hidden" name="session_token" value=<?= htmlspecialchars($curr_user->session_token) ?>>
			<button type="submit" class="btn btn-default">Submit</button>
			</div>
		</form>

		<form class="form-inline" role="form" action="admin.php?submission=delete_users" method="post" id="delete-user-form">
			<div class="table-responsive col-md-2">
				<h3>Delete Users:</h3>
				<table class="table table-bordered table-striped">
					<thead>
						<tr>
							<th>User</th>
							<th></th>
						</tr>
					</thead>
					<tbody>
						<tr>
							<td>All</td>
							<div class="checkbox"> 
								<td><input type="checkbox" class="all" name="all" value="on"></td>
							</div>
						</tr>

						<?php foreach ($full_users as $id => $user): ?>
						<?php if (!$user['deleted']): ?>
						
						<tr>
							<td><?= $user['name'] ?></td>
							<div class="checkbox">
								<td>
									<input type="checkbox" name=<?= 'select' . $id ?> value="on">
								</td>
							</div>
						</tr>
						
						<?php endif; ?>
						<?php endforeach; ?>
					</tbody>
				</table>
				<input type="hidden" name="session_token" value=<?= htmlspecialchars($curr_user->session_token) ?>>
				<button type="submit" class="btn btn-default">Submit</button>
			</div>
		</form>
		<form class="form-inline" role="form" action="admin.php?submission=register_code" method="post" id="register-code-form">
			<div class="table-responsive col-md-2">
				<h3>Generate Register Code:</h3>
				<table class="table table-bordered table-striped">
					<thead>
						<tr>
							<th>Codes:</th>
							<th></th>
						</tr>
					</thead>
					<tbody>
						<tr>
							<td>All</td>
							<div class="checkbox"> 
								<td><input type="checkbox" class="all" name="all" value="on"></td>
							</div>
						</tr>

						<?php

						$query = 'SELECT * FROM ' . $config['db']['tables']['register_codes'] . ';';
						$result = $mysqli->query($query);
						while ($row = $result->fetch_assoc()): ?>
						
						<tr>
							<td><?= $row['code'] ?></td>
							<div class="checkbox">
								<td>
									<input type="checkbox" name=<?= 'select' . $row['code'] ?> value="on">
								</td>
							</div>
						</tr>
						
						<?php endwhile; ?>
					</tbody>
				</table>
				<input type="hidden" name="session_token" value=<?= htmlspecialchars($curr_user->session_token) ?>>
				<button type="submit" class="btn btn-default" name="delete">Delete</button>
				<button type="submit" class="btn btn-default" name="generate">Generate</button>
			</div>
		</form>
	</body>
</html>
<?php
	require_once 'closer.php';
?>
<!DOCTYPE html>
<html>
	<head>
		<?php

        require_once "$WWW/views/head_header.php";

        ?>
		<script src="js/admin.js"></script>
		<style>
		</style>
	</head>
	<body>
		<?php
			require_once 'navbar.php';		
		?>
		<div style="color:green"><?= $message ?></div>
			
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

						<?php foreach ($all_users as $id => $user): ?>
						<?php if (!$user->is_verified): ?>
						
						<tr>
							<td><?= $user->name ?></td>
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
			<input type="hidden" name="session_token" value=<?= htmlspecialchars($user_session_token) ?>>
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

						<?php foreach ($all_users as $id => $user): ?>
						<?php if (!$user->is_admin): ?>
						
						<tr>
							<td><?= $user->name ?></td>
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
			<input type="hidden" name="session_token" value=<?= htmlspecialchars($user_session_token) ?>>
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

						<?php foreach ($all_users as $id => $user): ?>
						<?php if (!$user->is_deleted): ?>
						
						<tr>
							<td><?= $user->name ?></td>
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
				<input type="hidden" name="session_token" value=<?= htmlspecialchars($user_session_token) ?>>
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

						<?php foreach ($codes as $row): ?>
						
						<tr>
							<td><?= $row['code'] ?></td>
							<div class="checkbox">
								<td>
									<input type="checkbox" name=<?= 'select' . $row['code'] ?> value="on">
								</td>
							</div>
						</tr>
						
						<?php endforeach; ?>
					</tbody>
				</table>
				<input type="hidden" name="session_token" value=<?= htmlspecialchars($user_session_token) ?>>
				<button type="submit" class="btn btn-default" name="delete">Delete</button>
				<button type="submit" class="btn btn-default" name="generate">Generate</button>
			</div>
		</form>
	</body>
</html>
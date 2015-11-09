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

			.negative {
				color: red;
			}

			.positive {
				color: green;
			}

			.table thead tr th {
				background-color: wheat;
			}

			.deposit {
				background-color: #b8d1f3;
			}

			.transaction-repeat {
				background-color: #cc99ff;
			}

			.transaction-single {
				background-color: #ffccff;
			}
		</style>
	</head>
	<body>
		<?php
			require_once 'navbar.php';
		?>
		<h1>Deposits and Transaction History:</h1>
		<div class="table-responsive">
			<table class="table table-bordered">
				<thead>
			 		<tr>
						<th>Balance</th>
						<th>Date</th>
						<th>Type</th>
						<th>Name</th>
						<th>Amount</th>
						<th>Paid by</th>
						<?php
							ksort($id_to_user);
							foreach ($id_to_user as $id => $user) {
								echo '<th>' . $user . '</th>';
							}
						?>
						<th>Note</th>
					</tr>
				</thead>
				<tbody>
					<?php
						require_once 'print_house_table.php';
					?>
				</tbody>
			</table>
		</div>  
	</body>
</html>
<?php
	require_once 'closer.php';
?>
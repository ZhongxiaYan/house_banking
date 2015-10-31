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

			#adjustment-amount {
				color: green;
			}

			.transaction-expandable-row {
				background-color: #ffff99;
			}

			.deposit-expandable-row {
				background-color: #ffff99;
			}

			.expandable-row {
				background-color: grey;
			}

			.negative {
				color: red;
			}

			.positive {
				color: green;
			}

			.table thead tr th {
				background-color: wheat;
			}

			.table-hover tbody tr:hover td, .table-hover tbody tr:hover th {
				background-color: #ffff99;
			}

		</style>
	</head>
	<body>
		<?php

			require_once 'navbar.php';
		?>
		<h1>Create New:</h1>
		<h4>Deposit:</h4>
		<form class="form-inline" role="form" action="balance.php?submission=deposit" method="post" id="deposit-form">
			<div class="form-group">
				<label>Name:</label>
				<input type="text" class="form-control" name="deposit-name">
			</div>
			<div class="form-group">
				<label>Amount:</label>
				<input type="number" class="form-control" min="0" step="0.01" name="deposit-amount" id="deposit-amount" required>
			</div>
			<div class="form-group">
				<label>Date:</label>
				<?php

				echo '<input type="date" class="form-control" name="deposit-date" max="' . date('Y-m-d') . '" value="' . date('Y-m-d') . '" required>';
				
				?>
			</div>
			<div class="form-group">
				<label>Note:</label>
				<textarea class="form-control" rows="2" name="deposit-note"></textarea>
			</div>
			<button type="submit" class="btn btn-default">Submit</button>
		</form>
		<h4>Transaction:</h4>
		<form class="form-inline" role="form" action="balance.php?submission=transaction_submit" method="post" id="trans-form">
			<div class="form-group">
				<label>Name:</label>
				<input type="text" class="form-control" name="trans-name" required>
			</div>
			<div class="form-group">
				<label>Paid by:</label>
				<select class="form-control" name="trans-paid-by" id="trans-paid-by">
					<option value="0">Bank</option>
					<?php

					foreach ($id_to_user as $id => $name) {
					    echo '<option value="' . htmlspecialchars($id) . '">' . htmlspecialchars($name) . '</option>';
					}

					?>
				</select>
			</div>
			<div class="form-group" required>
				<label>Amount:</label>
				<input type="number" class="form-control" step="0.01" name="trans-total-amount" id="trans-total-amount">
			</div>
			<div class="form-group trans-repeat-toggle">
  				<label>Repeat:</label>
  				<input type="checkbox" name="trans-repeat" value="yes">
			</div>
			<div class="form-group start-date" required>
				<label>Date:</label>
				<?php

				echo '<input type="date" class="form-control" name="trans-date" max="' . date('Y-m-d') . '" value="' . date('Y-m-d') . '">';
				
				?>
			</div>
			<div class="form-group trans-repeat-info" style="display:none">
				<label>Stop Date:</label>
				<?php

				echo '<input type="date" class="form-control" name="trans-end-date" value="' . date('Y-m-d') . '">';
				
				?>
				<label>Interval:</label>
				<input type="number" class="form-control" step="1" min="1" name="trans-interval-num" id="trans-interval-num" value="1">
				<select class="form-control" name="trans-interval-unit" id="trans-interval-unit">
					<option value="d">Day</option>
					<option value="m">Month</option>
					<option value="y">Year</option>
				</select>
			</div>
			<div class="form-group">
				<label>Note:</label>
				<textarea class="form-control" rows="2" name="trans-note"></textarea>
			</div>
			<h6>Individual Costs:</h6>
			<?php

			foreach ($id_to_user as $id => $name) {
				echo '<div class="form-group">';
				echo '<label>' . htmlspecialchars($name) . ':</label>';
				echo '<input type="number" class="form-control user-amount" step="0.01" name="user_' . htmlspecialchars($id) . '_amount">';
				echo '</div>';
			}

			?>
			
			<br><br>
			<button type="submit" class="btn btn-default">Submit</button>
		</form>

		<h1>Deposits and Balance History:</h1>
    	<?php
			require_once 'print_table.php';
		?>
	</body>
</html>

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
		<script src="js/index.js"></script>
		<script src="js/json.js"></script>
		<style>

			.editting-cell {
				background-color: gray;
				cursor: pointer;
			}

			.editting-cell:hover {
				background-color: lightyellow;
			}

			.negative {
				color: red;
			}

			.positive {
				color: green;
			}

			.selected-cell {
				background-color: yellow;
				cursor: pointer;
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
		<div class="table-responsive">
			<div class="form-group form-inline">
				<label>Width:</label>
				<input type="number" class="form-control" step="1" min="0" id="table-width">
				<label>Height:</label>
				<input type="number" class="form-control" step="1" min="0" id="table-height">
			</div>
			<table class="table table-bordered table-fixed" id="editable-table" session_token=<?php echo '"' . $user_session_token . '"' ?>>
				<tbody>
					<?php
						require_once 'print_editable_table.php';
					?>
				</tbody>
			</table>
			<div class="btn-group">
				<button type="button" class="btn btn-primary" id="restore">Restore</button>
				<button type="button" class="btn btn-primary" id="interactive-resize">Interactive Crop</button>
				<button type="button" class="btn btn-primary" id="save">Save</button>
			</div>
		</div>
		<h1>Deposits and Transaction History:</h1>
		<div class="table-responsive">
			<table class="table table-bordered table-fixed">
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
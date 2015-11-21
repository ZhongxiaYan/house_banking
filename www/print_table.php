<?php

$user_amount = 'user_' . $view_user->id . '_amount';
$users['0']['name'] = 'Bank'; // adds bank as a user for printing tables

$deposits = $view_user->get_deposits(0);
$deposit_array = array();
$total_balance = 0.0; // sum up the cost for every transaction then subtract as we go
while ($row = $deposits->fetch_assoc()) {
	$deposit_array[] = $row;
	$total_balance += floatval($row['amount']);
}

$transactions = $view_user->get_single_transactions(0);
$trans_array = array();
while ($row = $transactions->fetch_assoc()) {
	$row['repeated'] = 0;
	if ($row['paid_by_id'] !== '0') { // adjust for paid_by
		$row['user_' . $row['paid_by_id'] . '_amount'] -= floatval($row['amount']);
	}
	$total_balance -= floatval($row[$user_amount]);
	$trans_array[] = $row;
}

// duplicate repeated transaction for the valid period
$repeated_transactions = $view_user->get_repeated_transactions(0);
while ($repeated_transactions && $row = $repeated_transactions->fetch_assoc()) {
	if ($row['paid_by_id'] !== '0') { // adjust for paid_by
		$row['user_' . $row['paid_by_id'] . '_amount'] -= floatval($row['amount']);
	}
	switch ($row['repeat_interval_unit']) {
		case 'd':
			$suffix = ' day';
			break;
		case 'm':
			$suffix = ' month';
			break;
		case 'y':
			$suffix = ' year';
			break;
	}

	$suffix = '+ ' . $row['repeat_interval_num'] . $suffix;
	$end_time = min(strtotime($row['end_date']), strtotime(date('Y-m-d')));
	$curr_date = $row['start_date'];
	$curr_time = strtotime($curr_date);
	while ($curr_time <= $end_time) {
		$row['repeated'] = 1;
		$row['action_time'] = $curr_date;
		
		$total_balance -= floatval($row[$user_amount]);
		$trans_array[] = $row;

		$curr_time = strtotime($curr_date . $suffix);
		$curr_date = date('Y-m-d', $curr_time);
	}
}
usort($trans_array, 'date_cmp'); // sort all of the transactions after repeated trans are added

$trans_index = 0;
$deposit_index = 0;
$trans_length = count($trans_array);
$deposit_length = count($deposit_array);

$date = date('Y-m');
$date = date('Y-m-d', strtotime($date . '+ 1 month'));
$entries_printed = 0;
$max_entries = 50; // at max how many outer rows will be printed
?>

<div class="table-responsive">
	<table class="table table-bordered">
	<thead>
		<tr>
			<th>Personal Balance</th>
			<th>Time</th>
		</tr>
	</thead>
	<tbody>

	<?php

	while ($deposit_index < $deposit_length || $trans_index < $trans_length) {
		$date = date('Y-m-d', strtotime($date . '- 1 month'));
		print_outer_row($total_balance, $date);
		print_deposit_table($deposit_array, $deposit_index, $total_balance, $date, $entries_printed, $max_entries);
		print_transaction_table($trans_array, $trans_index, $total_balance, $date, $entries_printed, $max_entries);
		$entries_printed++;
	}

	?>

	</tbody>
	</table>
</div>
<?php

unset($users['0']); // remove 'Bank' as a user (see top of this file)

function print_deposit_table($deposits, &$index, &$balance, $endtime, $entries_printed, $max_entries) {
	global $view_user;
	if (count($deposits) === 0) {
		return;
	}
	if ($index < count($deposits) && ($deposits[$index]['action_time'] >= $endtime || $entries_printed >= $max_entries)): ?>

	<tr class="hidden-row">
		<td colspan="3">
			<table class="table table-bordered">
				<thead>
					<tr>
						<th>Deposit</th>
						<th>Amount</th>
						<th>Date</th>
						<th>Note</th>
					</tr>
				</thead>
				<tbody>
				
					<?php while ($index < count($deposits) && ($deposits[$index]['action_time'] >= $endtime || $entries_printed >= $max_entries)): ?>
					<tr class="deposit-expandable-row">
						<td type="deposit-name"><?= htmlspecialchars($deposits[$index]['name']) ?></td>
						<td type="deposit-amount" class=<?= htmlspecialchars(($deposits[$index]['amount'] < 0) ? 'negative' : 'positive') ?>><?= htmlspecialchars(number_format($deposits[$index]['amount'], 2)) ?></td>
						<td type="deposit-date"><?= htmlspecialchars(date('Y-m-d', strtotime($deposits[$index]['action_time']))) ?></td>
						<td type="deposit-note"><?= htmlspecialchars($deposits[$index]['note']) ?></td>
					</tr>
					<tr class="deposit-hidden-row">
						<td colspan="4">
							<!-- edit and delete buttons -->
							<form class="form-inline" role="form" action=<?= 'balance.php?submission=deposit_delete&user=' . $view_user->id ?> method="post">
								<input type="hidden" name="session_token" value=<?= htmlspecialchars($view_user->session_token) ?>>
								<div class="btn-group">
					  				<button type="button" class="btn btn-primary deposit-edit-button" value=<?= htmlspecialchars($deposits[$index]['id']) ?>>Edit</button>
						  			<button type="submit" class="btn btn-primary deposit-delete-button" name="deposit-id" value=<?= htmlspecialchars($deposits[$index]['id']) ?>>Delete</button>
				  				</div>
		    				</form>
							<br><br>
						</td>
					</tr>
					<?php

					$balance -= $deposits[$index]['amount'];
					$index++;

					endwhile; ?>
				
				</tbody>
			</table>
		</td>
	</tr>

	<?php endif; 
}

function print_transaction_table($transactions, &$index, &$balance, $endtime, $entries_printed, $max_entries) {
	if (count($transactions) === 0) {
		return;
	}
	global $users;
	global $view_user;
	global $user_amount;
	static $transaction_fields = array(); // stores the user_x_amount keys so they can be used later
	if (count($transaction_fields) === 0) {
		foreach ($users as $id => $row) {
			if ($id !== '0' && $id !== 0) {
			    $transaction_fields['user_' . $id . '_amount'] = $id;
			}
		}
	}
	if ($index < count($transactions) && ($transactions[$index]['action_time'] >= $endtime || $entries_printed >= $max_entries)): ?>
	
	<tr class="hidden-row">
		<td colspan="3">
			<table class="table table-bordered">
		  		<thead>
		  			<tr>
		  				<th>Transaction</th>
		  				<th>Your cost</th>
		  				<th>Total Cost</th>
		  				<th>Paid by</th>
		  				<th>Date</th>
		  				<th>Note</th>
		  			</tr>
		  		</thead>
		  		<tbody>
					<?php while ($index < count($transactions) && ($transactions[$index]['action_time'] >= $endtime || $entries_printed >= $max_entries)):
						$curr_trans = $transactions[$index]; 
					?>
					<tr class="transaction-expandable-row">
			 			<td type="trans-name"><?= htmlspecialchars($curr_trans['name']) ?></td>
			 			<td class=<?= htmlspecialchars(($curr_trans[$user_amount] > 0) ? 'negative' : 'positive') ?>><?= htmlspecialchars(number_format($curr_trans[$user_amount], 2)) ?></td>
						<td type="trans-total-amount"><?= htmlspecialchars(number_format($curr_trans['amount'])) ?></td>
						<td type="trans-paid-by" user-id=<?= htmlspecialchars($curr_trans['paid_by_id']) ?>><?= htmlspecialchars($users[$curr_trans['paid_by_id']]['name']) ?></td>
						<td type="trans-date"><?= htmlspecialchars(date('Y-m-d', strtotime($curr_trans['action_time']))) ?></td>
						<td type="trans-note"><?= htmlspecialchars($curr_trans['note']) ?></td>
					</tr>
					<tr class="transaction-hidden-row">
						<td colspan="6">
							Amount by Person:
							<?php foreach ($transaction_fields as $user_x_amount => $id): ?>
							
							<div user-id=<?= htmlspecialchars($id) ?>><?= htmlspecialchars($users[$id]['name']) . ': ' . htmlspecialchars(number_format($curr_trans[$user_x_amount], 2)) ?></div>
							
							<?php endforeach; ?>
							<br>
							<!-- edit and delete buttons -->
							<form class="form-inline" role="form" action=<?= 'balance.php?submission=transaction_delete&user=' . $view_user->id ?> method="post">
								<?php if ($curr_trans['repeated'] === 0): // set to be nonrepeating ?>
								<input type="hidden" name="trans-type" trans-id=<?= htmlspecialchars($curr_trans['id']) ?> value="s">
								
								<?php else: ?>
								<input type="hidden" name="trans-type" trans-id=<?= htmlspecialchars($curr_trans['id']) ?> value="r" trans-date=<?= htmlspecialchars(date('Y-m-d', strtotime($curr_trans['start_date']))) ?>
									trans-end-date=<?= htmlspecialchars(date('Y-m-d', strtotime($curr_trans['end_date']))) ?> trans-interval-num=<?= htmlspecialchars($curr_trans['repeat_interval_num']) ?>
									trans-interval-unit=<?= htmlspecialchars($curr_trans['repeat_interval_unit']) ?>>
								
								<?php endif; ?>
								
								<input type="hidden" name="session_token" value=<?= htmlspecialchars($view_user->session_token) ?>>
								<div class="btn-group">
									<button type="button" class="btn btn-primary transaction-edit-button">Edit</button>
								  	<button type="submit" class="btn btn-primary transaction-delete-button" name="trans-id" value=<?= htmlspecialchars($curr_trans['id']) ?>>Delete</button>
								</div>
				    		</form>
						</td>
					</tr>
					<?php
					
					$balance += $curr_trans[$user_amount];
					$index++;
					
					endwhile; ?>
				</tbody>
			</table>
		</td>
	</tr>

	<?php endif;
}

function print_outer_row($balance, $time) { 
	?>
	<tr class="expandable-row">
		<td class=<?= htmlspecialchars(($balance >= 0) ? 'positive' : 'negative') ?>><?= htmlspecialchars(number_format($balance, 2)) ?></td>
	    <td><?= htmlspecialchars(date('m/Y', strtotime($time))) ?></td>
	</tr>
	<?php 
}

function date_cmp($a, $b) {
    return strtotime($a['action_time']) <= strtotime($b['action_time']);
}

?>
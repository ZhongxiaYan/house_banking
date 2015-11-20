<?php

$user_amount = 'user_' . $curr_user->id . '_amount';
$id_to_user['0'] = 'Bank'; // adds bank as a user for printing tables

$deposits = $curr_user->get_deposits($curr_user->is_admin);
$deposit_array = array();
$total_balance = 0.0; // sum up the cost for every transaction then subtract as we go
while ($row = $deposits->fetch_assoc()) {
	$deposit_array[] = $row;
	$total_balance += floatval($row['amount']);
}

$transactions = $curr_user->get_single_transactions($curr_user->is_admin);
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
$repeated_transactions = $curr_user->get_repeated_transactions($curr_user->is_admin);
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

echo '<div class="table-responsive">';
echo '<table class="table table-bordered">';
echo '<thead>
		 <tr>
			 <th>Personal Balance</th>
			 <th>Time</th>
		 </tr>
		 </thead>
		 <tbody>';
$trans_index = 0;
$deposit_index = 0;
$trans_length = count($trans_array);
$deposit_length = count($deposit_array);

$date = date('Y-m');
$date = date('Y-m-d', strtotime($date . '+ 1 month'));
$entries_printed = 0;
$max_entries = 50; // at max how many outer rows will be printed
while ($deposit_index < $deposit_length || $trans_index < $trans_length) {
	$date = date('Y-m-d', strtotime($date . '- 1 month'));
	print_outer_row($total_balance, $date);
	print_deposit_table($deposit_array, $deposit_index, $total_balance, $date, $entries_printed, $max_entries);
	print_transaction_table($trans_array, $trans_index, $total_balance, $date, $entries_printed, $max_entries);
	$entries_printed++;
}
echo '</tbody>';
echo '</table>';
echo '</div>';

unset($id_to_user['0']); // remove 'Bank' as a user (see top of this file)

function print_deposit_table($deposits, &$index, &$balance, $endtime, $entries_printed, $max_entries) {
	global $curr_user;
	if (count($deposits) === 0) {
		return;
	}
	if ($index < count($deposits) && ($deposits[$index]['action_time'] >= $endtime || $entries_printed >= $max_entries)) {
		echo '<tr class="hidden-row">';
		echo '<td colspan="3"><table class="table table-bordered">';
		echo '<thead><tr> <th>Deposit</th> <th>Amount</th> <th>Date</th> <th>Note</th> </tr></thead>';
		echo '<tbody>';
		while ($index < count($deposits) && ($deposits[$index]['action_time'] >= $endtime || $entries_printed >= $max_entries)) {
			echo '<tr class="deposit-expandable-row">';
			echo '    <td type=deposit-name>' . htmlspecialchars($deposits[$index]['name']) . '</td>';
			echo '    <td type=deposit-amount class="' . htmlspecialchars(($deposits[$index]['amount'] < 0) ? 'negative' : 'positive') . '">' . htmlspecialchars(number_format($deposits[$index]['amount'], 2)) . '</td>';
			echo '    <td type=deposit-date>' . htmlspecialchars(date('Y-m-d', strtotime($deposits[$index]['action_time']))) . '</td>';
			echo '    <td type=deposit-note>' . htmlspecialchars($deposits[$index]['note']) . '</td>';
			echo '</tr>';
			echo '<tr class="deposit-hidden-row">';
			echo '<td colspan="4">';
			// edit and delete buttons
			echo '<form class="form-inline" role="form" action="balance.php?submission=deposit_delete" method="post">';
			echo '<input type="hidden" name="session_token" value="' . htmlspecialchars($curr_user->session_token) . '">';
			echo '<div class="btn-group">
					  <button type="button" class="btn btn-primary deposit-edit-button" value="' . htmlspecialchars($deposits[$index]['id']) . '">Edit</button>
				  	  <button type="submit" class="btn btn-primary deposit-delete-button" name="deposit-id" value="' . htmlspecialchars($deposits[$index]['id']) . '">Delete</button>
				  </div>';
		    echo '</form>';
			echo '<br>';
			echo '<br>';
			echo '</td>';
			echo '</tr>';
			$balance -= $deposits[$index]['amount'];
			$index++;
		}
		echo '</tbody>';
		echo '</table></td>';
		echo '</tr>';
	}
}

function print_transaction_table($transactions, &$index, &$balance, $endtime, $entries_printed, $max_entries) {
	if (count($transactions) === 0) {
		return;
	}
	global $id_to_user;
	global $curr_user;
	global $user_amount;
	static $transaction_fields = array(); // stores the user_x_amount keys so they can be used later
	if (count($transaction_fields) === 0) {
		foreach ($transactions[$index] as $key => $value) {
			if (preg_match('/^user_(\d+)_amount$/', $key, $matches)) {
				$transaction_fields[$key] = $matches[1];
			}
		}
	}
	if ($index < count($transactions) && ($transactions[$index]['action_time'] >= $endtime || $entries_printed >= $max_entries)) {
		echo '<tr class="hidden-row">
			  <td colspan="3"><table class="table table-bordered">
			  <thead><tr> <th>Transaction</th> <th>Your cost</th> <th>Total Cost</th> <th>Paid by</th> <th>Date</th> <th>Note</th> </tr></thead>
			  <tbody>';
		while ($index < count($transactions) && ($transactions[$index]['action_time'] >= $endtime || $entries_printed >= $max_entries)) {
			$curr_trans = $transactions[$index];
			echo '<tr class="transaction-expandable-row">' .
				 '    <td type=trans-name>' . htmlspecialchars($curr_trans['name']) . '</td>' .
				 '    <td class="' . htmlspecialchars(($curr_trans[$user_amount] > 0) ? 'negative' : 'positive') . '">' . htmlspecialchars(number_format($curr_trans[$user_amount], 2)) . '</td>' .
				 '    <td type=trans-total-amount>' . htmlspecialchars(number_format($curr_trans['amount'])) . '</td>' .
				 '    <td type=trans-paid-by user-id="' . htmlspecialchars($curr_trans['paid_by_id']) . '">' . htmlspecialchars($id_to_user[$curr_trans['paid_by_id']]) . '</td>' .
				 '    <td type=trans-date>' . htmlspecialchars(date('Y-m-d', strtotime($curr_trans['action_time']))) . '</td>' .
				 '    <td type=trans-note>' . htmlspecialchars($curr_trans['note']) . '</td>' .
				 '</tr>' .
				 '<tr class="transaction-hidden-row">' .
				 '<td colspan="6">' .
				 'Amount by Person:  ';

			foreach ($transaction_fields as $user_x_amount => $id) {
				echo '<div user-id="' . htmlspecialchars($id) . '">' . htmlspecialchars($id_to_user[$id]) . ': ' . htmlspecialchars(number_format($curr_trans[$user_x_amount], 2)) . '</div>';
			}
			echo '<br>';
			// edit and delete buttons
			echo '<form class="form-inline" role="form" action="balance.php?submission=transaction_delete" method="post">';
			if ($curr_trans['repeated'] === 0) { // set to be nonrepeating
				echo '<input type="hidden" name="trans-type" trans-id="' . htmlspecialchars($curr_trans['id']) . '" value="s">';
			} else {
				echo '<input type="hidden" name="trans-type" trans-id="' . htmlspecialchars($curr_trans['id']) . '" value="r" trans-date="' . htmlspecialchars(date('Y-m-d', strtotime($curr_trans['start_date']))) . 
					'" trans-end-date="' . htmlspecialchars(date('Y-m-d', strtotime($curr_trans['end_date']))) . '" trans-interval-num="' . htmlspecialchars($curr_trans['repeat_interval_num']) .
					'" trans-interval-unit="' . htmlspecialchars($curr_trans['repeat_interval_unit']) . '">';
			}
			echo '<input type="hidden" name="session_token" value="' . htmlspecialchars($curr_user->session_token) . '">';
			echo '<div class="btn-group">';
			
			echo	  '<button type="button" class="btn btn-primary transaction-edit-button">Edit</button>
				  	  <button type="submit" class="btn btn-primary transaction-delete-button" name="trans-id" value="' . htmlspecialchars($curr_trans['id']) . '">Delete</button>
				  </div>';
		    echo '</form>';
			echo '</td>';
			echo '</tr>';
			$balance += $curr_trans[$user_amount];
			$index++;
		}
		echo '</tbody>';
		echo '</table></td>';
		echo '</tr>';
	}
}

function print_outer_row($balance, $time) {
	echo '<tr class="expandable-row">';
	echo '    <td class="' . htmlspecialchars(($balance >= 0) ? 'positive' : 'negative') . '">' . htmlspecialchars(number_format($balance, 2)) . '</td>';
	echo '    <td>' . htmlspecialchars(date('m/Y', strtotime($time))) . '</td>';
	echo '</tr>';
}

function date_cmp($a, $b) {
    return strtotime($a['action_time']) <= strtotime($b['action_time']);
}

?>
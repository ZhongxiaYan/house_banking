<?php

$user_amount = 'user_' . $curr_user->id . '_amount';
$id_to_user['0'] = 'Bank'; // adds bank as a user for printing tables

$merged_array = array();
$index = 0;

$deposits = $curr_user->get_deposits(1);
$total_balance = 0.0; // sum up the cost for every deposity/transaction then subtract as we go
while ($row = $deposits->fetch_assoc()) {
	$row['type'] = 'deposit';
	$row['paid_by_id'] = $row['user_id'];
	$merged_array[$index] = $row;
	$total_balance += floatval($row['amount']);
	$index++;
}

$transactions = $curr_user->get_single_transactions(1);
while ($row = $transactions->fetch_assoc()) {
	$row['type'] = 'transaction';
	$row['repeated'] = 0;
	$row['paid_by_amount'] = $row['amount']; // paid_by_amount is total amount paid
	$row['amount'] = (($row['paid_by_id'] === '0') ? floatval($row['paid_by_amount']) : 0);
	$total_balance -= floatval($row['amount']);
	$merged_array[$index] = $row;
	$index++;
}

// duplicate repeated transaction for the valid period
$repeated_transactions = $curr_user->get_repeated_transactions(1);
while ($repeated_transactions && $row = $repeated_transactions->fetch_assoc()) {
	$row['type'] = 'transaction (repeated)';
	$row['repeated'] = 1;
	$row['paid_by_amount'] = $row['amount'];
	$row['amount'] = (($row['paid_by_id'] === '0') ? floatval($row['paid_by_amount']) : 0);
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
		$row['action_time'] = $curr_date;
		
		$total_balance -= floatval($row['amount']);
		$merged_array[$index] = $row;

		$curr_time = strtotime($curr_date . $suffix);
		$curr_date = date('Y-m-d', $curr_time);
		$index++;
	}
}
usort($merged_array, 'date_cmp'); // sort all of the transactions after repeated trans are added
ksort($id_to_user);

$index = 0;
$length = count($merged_array);

$date = date('Y-m');
$date = date('Y-m-d', strtotime($date . '+ 1 month'));
while ($index < $length) {
	$date = date('Y-m-d', strtotime($date . '- 1 month'));
	print_table($merged_array, $index, $total_balance, $date);
}

unset($id_to_user['0']); // remove 'Bank' as a user (see top of this file)

function print_table($actions, &$index, &$balance, $endtime) {
	global $curr_user;
	global $id_to_user;
	if (count($actions) === 0) {
		return;
	}
	if ($index < count($actions) && $actions[$index]['action_time'] >= $endtime) {
		$curr_action = $actions[$index];
		while ($index < count($actions) && $curr_action['action_time'] >= $endtime) {
			$curr_action = $actions[$index];
			$is_deposit = ($curr_action['type'] === 'deposit');
			echo '<tr class="' . ($is_deposit ? 'deposit' : ($curr_action['repeated'] ? 'transaction-repeat' : 'transaction-single')) . '">';
			echo '    <td class="' . htmlspecialchars(($balance >= 0) ? 'positive' : 'negative') . '">' . htmlspecialchars(number_format($balance, 2)) . '</td>';
			echo '    <td>' . htmlspecialchars(date('Y-m-d', strtotime($curr_action['action_time']))) . '</td>';
			echo '    <td>' . htmlspecialchars($curr_action['type']) . '</td>';
			echo '    <td>' . htmlspecialchars($curr_action['name']) . '</td>';
			echo '    <td class="' . htmlspecialchars((($is_deposit ? $curr_action['amount'] : -$curr_action['amount']) < 0) ? 'negative' : 'positive') . '">' . htmlspecialchars(number_format($curr_action['amount'], 2)) . '</td>';
			
			if ($is_deposit) {
				echo '<td>' . htmlspecialchars($id_to_user[$curr_action['paid_by_id']]) . '</td>';
				for ($i = 1; $i < count($id_to_user); $i++) {
					echo '<td>n/a</td>';
				}
			} else {
				if ($curr_action['paid_by_id'] === '0') {
					echo '    <td class="' . ($curr_action['amount'] > 0 ? 'negative' : 'positive') . '">' . htmlspecialchars($id_to_user[$curr_action['paid_by_id']]) . '</td>';
				} else {
					echo '    <td class="positive">' . htmlspecialchars($id_to_user[$curr_action['paid_by_id']]) . ': ' . htmlspecialchars(number_format($curr_action['paid_by_amount'], 2)) . '</td>';
				}
				
				foreach ($id_to_user as $id => $user) {
					$user_x_amount = 'user_' . $id . '_amount';
					if ($id !== 0) { // skip bank
						echo '<td class="' . htmlspecialchars(($curr_action[$user_x_amount] > 0) ? 'negative' : 'positive') . '">' . htmlspecialchars(number_format($curr_action[$user_x_amount], 2)) . '</td>';
					}
				}
			}
			echo '    <td>' . htmlspecialchars($curr_action['note']) . '</td>';
			echo '</tr>';
			$balance += ($is_deposit ? -$curr_action['amount'] : $curr_action['amount']);
			$index++;
		}
	}
}

function date_cmp($a, $b) {
    return strtotime($a['action_time']) <= strtotime($b['action_time']);
}

?>
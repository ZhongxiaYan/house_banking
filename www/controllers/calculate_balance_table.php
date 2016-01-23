<?php

$user_amount = sprintf('user_%s_amount', $view_user->id);

$active_users['0'] = new User('0', 'Bank', null, null, '1', '0', '0'); // adds bank as an active user for printing tables

$deposit_table = new DepositTable($db);
$transaction_table = new TransactionTable($db);

$total_balance = 0.0; // sum up the net deposit - transaction

$deposit_array = array();
$deposit_balance = $deposit_table->get_deposits_array($deposit_array, $view_user->id);
$total_balance += $deposit_balance;

$trans_array = array();
$single_trans_array = array();
$single_transaction_total = $transaction_table->get_single_transactions_array($single_trans_array, $view_user->id);
$total_balance -= $single_transaction_total;

// duplicate repeated transaction for the valid period
$repeated_trans_array = array();
$repeated_transaction_total = $transaction_table->get_repeated_transactions_array($repeated_trans_array, $view_user->id);
$total_balance -= $repeated_transaction_total;

$trans_array = array_merge($single_trans_array, $repeated_trans_array);

usort($trans_array, 'date_cmp'); // sort all of the transactions after repeated trans are added

$trans_index = 0;
$deposit_index = 0;
$trans_length = count($trans_array);
$deposit_length = count($deposit_array);

$date = date('Y-m');
$date = date('Y-m-d', strtotime($date . '+ 1 month'));
$entries_printed = 0;
$max_entries = 50; // at max how many outer rows will be printed
$table = array();
while ($deposit_index < $deposit_length || $trans_index < $trans_length) {
    $date = date('Y-m-d', strtotime($date . '- 1 month'));
    
    $outer_row = get_outer_row($total_balance, $date);
    $deposit_rows = get_deposit_rows($view_user, $deposit_array, $deposit_index, $total_balance, $date, $entries_printed, $max_entries);
    $transaction_rows = get_transaction_rows($view_user, $active_users, $user_amount, $trans_array, $trans_index, $total_balance, $date, $entries_printed, $max_entries);
    
    $all_rows = array(
        'outer_row' => $outer_row,
        'deposit_rows' => $deposit_rows,
        'transaction_rows' => $transaction_rows
    );
    $table[] = $all_rows;
    
    $entries_printed++;
}

unset($active_users['0']); // remove 'Bank' as a user (see top of this file)

function get_outer_row($balance, $time) {
    $outer_row = array(
        'balance_color' => ($balance >= 0) ? 'green' : 'red',
        'balance' => number_format($balance, 2),
        'date' => date('m/Y', strtotime($time))
    );
    return $outer_row;
}

function get_deposit_rows($view_user, $deposits, &$index, &$balance, $endtime, $entries_printed, $max_entries) {
    $new_deposit_rows = array();
    if (count($deposits) === 0) {
        return $new_deposit_rows;
    }
    // loop through the original rows
    while ($index < count($deposits) && ($deposits[$index]['action_time'] >= $endtime || $entries_printed >= $max_entries)) {
        $original_deposit_row = $deposits[$index];
        $new_deposit_row = array(
            'name' => $original_deposit_row['name'],
            'id' => $original_deposit_row['id'],
            'amount_color' => ($original_deposit_row['amount'] < 0) ? 'red' : 'green',
            'amount' => number_format($original_deposit_row['amount'], 2),
            'date' => date('Y-m-d', strtotime($original_deposit_row['action_time'])),
            'note' => $original_deposit_row['note']
        );
        $new_deposit_rows[] = $new_deposit_row;
        $balance -= $original_deposit_row['amount'];
        $index++;
    }
    return $new_deposit_rows;
}

function get_transaction_rows($view_user, $active_users, $user_amount, $transactions, &$index, &$balance, $endtime, $entries_printed, $max_entries) { // TODO: only active users?
    $new_transaction_rows = array();
    if (count($transactions) === 0) {
        return $new_transaction_rows;
    }
    static $transaction_fields = array(); // stores the user_x_amount keys so they can be used later
    if (count($transaction_fields) === 0) {
        foreach ($active_users as $id => $user) {
            if ($id !== '0' && $id !== 0) {
                $transaction_fields["user_${id}_amount"] = $id;
            }
        }
    }

    // loop through the original rows
    while ($index < count($transactions) && ($transactions[$index]['action_time'] >= $endtime || $entries_printed >= $max_entries)) {
        $original_transaction_row = $transactions[$index];
        $new_transaction_row = array(
            'name' => $original_transaction_row['name'],
            'id' => $original_transaction_row['id'],
            'user_amount_color' => ($original_transaction_row[$user_amount] > 0) ? 'red' : 'green',
            $user_amount => number_format($original_transaction_row[$user_amount], 2),
            'total_amount' => number_format($original_transaction_row['amount']),
            'paid_by_id' => $original_transaction_row['paid_by_id'],
            'date' => date('Y-m-d', strtotime($original_transaction_row['action_time'])),
            'note' => $original_transaction_row['note'],
            'is_repeated' => $original_transaction_row['repeated']
        );
        $user_x_amounts = array();
        foreach ($transaction_fields as $user_x_amount => $id) {
            $user_x_amounts[$user_x_amount] = $id;
            $new_transaction_row[$user_x_amount] = number_format($original_transaction_row[$user_x_amount], 2);
        }
        $new_transaction_row['user_x_amounts'] = $user_x_amounts;
                    
        $new_transaction_rows[] = $new_transaction_row;
        $balance += $original_transaction_row[$user_amount];

        $index++;
    }
    return $new_transaction_rows;
}


?>
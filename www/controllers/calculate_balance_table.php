<?php

$view_user_amount_string = sprintf('user_%s_amount', $view_user->id);

$deposit_table = new DepositTable($db);
$transaction_table = new TransactionTable($db);

$total_balance = 0.0; // sum up the net deposit - transaction

$deposit_array = $deposit_table->get_deposits_array($view_user->id);
$single_trans_array = $transaction_table->get_single_transactions_array($view_user->id);
// duplicated repeated transaction for the valid period
$repeated_trans_array = $transaction_table->get_repeated_transactions_array($view_user->id);

$trans_array = array_merge($single_trans_array, $repeated_trans_array);

foreach ($deposit_array as $row) {
    $total_balance += $row['amount'];
}
foreach ($trans_array as $row) {
    $total_balance -= $row[$view_user_amount_string];
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
$table = array();
while ($deposit_index < $deposit_length || $trans_index < $trans_length) {
    $date = date('Y-m-d', strtotime($date . '- 1 month'));
    
    $outer_row = get_outer_row($total_balance, $date);
    $deposit_rows = get_deposit_rows($deposit_array, $deposit_index, $total_balance, $date, $entries_printed, $max_entries);
    $transaction_rows = get_transaction_rows($current_users, $view_user_amount_string, $trans_array, $trans_index, $total_balance, $date, $entries_printed, $max_entries);
    
    $all_rows = array(
        'outer_row' => $outer_row,
        'deposit_rows' => $deposit_rows,
        'transaction_rows' => $transaction_rows
    );
    $table[] = $all_rows;
    
    $entries_printed++;
}

function get_outer_row($balance, $time) {
    $outer_row = array(
        'balance_color' => ($balance >= 0) ? 'green' : 'red',
        'balance' => number_format($balance, 2),
        'date' => date('m/Y', strtotime($time))
    );
    return $outer_row;
}

function get_deposit_rows($deposits, &$index, &$balance, $endtime, $entries_printed, $max_entries) {
    $new_deposit_rows = array();
    if (count($deposits) === 0) {
        return $new_deposit_rows;
    }
    // loop through the original rows
    while ($index < count($deposits) && ($deposits[$index]['action_time'] >= $endtime || $entries_printed >= $max_entries)) {
        $deposit_row = $deposits[$index];
        $deposit_row['amount'] = number_format($deposit_row['amount'], 2);
        $deposit_row['amount_color'] = ($deposit_row['amount'] < 0) ? 'red' : 'green';
        $deposit_row['date'] = date('Y-m-d', strtotime($deposit_row['action_time']));
        $new_deposit_rows[] = $deposit_row;
        $balance -= $deposit_row['amount'];
        $index++;
    }
    return $new_deposit_rows;
}

function get_transaction_rows($current_users, $view_user_amount_string, $transactions, &$index, &$balance, $endtime, $entries_printed, $max_entries) {
    $new_transaction_rows = array();
    if (count($transactions) === 0) {
        return $new_transaction_rows;
    }
    static $transaction_fields = array(); // stores the user_x_amount keys so they can be used later
    if (count($transaction_fields) === 0) {
        foreach ($current_users as $id => $user) {
            $transaction_fields["user_${id}_amount"] = $id;
        }
    }

    // loop through the original rows
    while ($index < count($transactions) && ($transactions[$index]['action_time'] >= $endtime || $entries_printed >= $max_entries)) {
        $transaction_row = $transactions[$index];
        $transaction_row[$view_user_amount_string] = number_format($transaction_row[$view_user_amount_string], 2);
        $transaction_row['view_user_amount_color'] = ($transaction_row[$view_user_amount_string] > 0) ? 'red' : 'green';
        $transaction_row['date'] = date('Y-m-d', strtotime($transaction_row['action_time']));
    
        $user_x_amounts = array();
        foreach ($transaction_fields as $user_x_amount => $id) {
            $user_x_amounts[$user_x_amount] = $id;
            $transaction_row[$user_x_amount] = number_format($transaction_row[$user_x_amount], 2);
        }
        $transaction_row['user_x_amounts'] = $user_x_amounts;
                    
        $new_transaction_rows[] = $transaction_row;
        $balance += $transaction_row[$view_user_amount_string];

        $index++;
    }
    return $new_transaction_rows;
}

?>
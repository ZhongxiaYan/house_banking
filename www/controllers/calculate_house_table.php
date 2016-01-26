<?php

$deposit_table = new DepositTable($db);
$transaction_table = new TransactionTable($db);


$deposit_array = $deposit_table->get_deposits_array(null);
$single_trans_array = $transaction_table->get_single_transactions_array(null);
// duplicate repeated transaction for the valid period
$repeated_trans_array = $transaction_table->get_repeated_transactions_array(null);

$table = array_merge($deposit_array, $single_trans_array, $repeated_trans_array);
usort($table, 'date_cmp'); // sort all of the transactions after repeated trans are added
ksort($current_users);

$total_balance = 0.0; // sum up the net deposit - transaction
$i = count($table);
while ($i-- > 0) {
    $row = $table[$i];
    $is_deposit = ($row['type'] === 'deposit');
    $row['date'] = date('Y-m-d', strtotime($row['action_time']));
    if ($is_deposit) {
        $row['class'] = 'deposit';
        $row['amount'] = number_format($row['amount'], 2);
        $row['amount_color'] = ($row['amount'] < 0) ? 'red' : 'green';
        foreach ($current_users as $id => $user) {
            $user_x_amount = "user_${id}_amount";

            if ($id == $row['user_id']) {
                $row[$user_x_amount] = $row['amount']; // assign paid amount to user
                $row["${user_x_amount}_color"] = 'green';
            } else {
                $row[$user_x_amount] = 'n/a';
                $row["${user_x_amount}_color"] = '';
            }
        }
        $total_balance += $row['amount'];
    } else {
        $row['class'] = ($row['repeated']) ? 'transaction-repeat' : 'transaction-single';
        $total_amount = 0;
        foreach ($current_users as $id => $user) {
            $user_x_amount_string = "user_${id}_amount";
            $user_x_amount = $row[$user_x_amount_string];
            $total_amount += floatval($user_x_amount);

            $row["${user_x_amount_string}_color"] = ($user_x_amount > 0) ? 'red' : 'green';
            $row[$user_x_amount_string] = number_format($user_x_amount, 2);
        }
        $row['amount_color'] = ($total_amount > 0) ? 'red' : 'green';
        $row['amount'] = number_format($total_amount, 2);
        $total_balance -= $total_amount;
    }
    $row['balance_color'] = ($total_balance >= 0) ? 'green' : 'red';
    $row['balance'] = number_format($total_balance, 2);
    $table[$i] = $row;
}

?>
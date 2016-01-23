<?php

$user_amount = sprintf('user_%s_amount', $view_user->id);

$active_users['0'] = new User('0', 'Bank', null, null, '1', '0', '0'); // adds bank as an active user for printing tables

$deposit_table = new DepositTable($db);
$transaction_table = new TransactionTable($db);

$total_balance = 0.0; // sum up the net deposit - transaction

$deposit_array = array();
$deposit_balance = $deposit_table->get_deposits_array($deposit_array, null);
$total_balance += $deposit_balance;

$trans_array = array();
$single_trans_array = array();
$single_transaction_total = $transaction_table->get_single_transactions_array($single_trans_array, null);
$total_balance -= $single_transaction_total;

// duplicate repeated transaction for the valid period
$repeated_trans_array = array();
$repeated_transaction_total = $transaction_table->get_repeated_transactions_array($repeated_trans_array, null);
$total_balance -= $repeated_transaction_total;

$merged_array = array_merge($deposit_array, $single_trans_array, $repeated_trans_array);
usort($merged_array, 'date_cmp'); // sort all of the transactions after repeated trans are added
ksort($active_users);

$table = array();
foreach ($merged_array as $curr_action) {
    $is_deposit = ($curr_action['type'] === 'deposit');
    $row = array(
        'balance_color' => ($total_balance >= 0) ? 'green' : 'red',
        'balance' => number_format($total_balance, 2),
        'date' => date('Y-m-d', strtotime($curr_action['action_time'])),
        'type' => $curr_action['type'],
        'name' => $curr_action['name'],
        'amount' => number_format($curr_action['amount'], 2),
        'paid_by' => $active_users[$curr_action['paid_by_id']]->name,
        'note' => $curr_action['note']
    );
    if ($is_deposit) {
        $row['class'] = 'deposit';
        $row['amount_color'] = ($curr_action['amount'] < 0) ? 'red' : 'green';
        foreach ($active_users as $id => $user) { // TODO active or all?
            if ($id !== 0) {
                $user_x_amount = "user_${id}_amount";
                $row[$user_x_amount] = 'n/a';
                $row["${user_x_amount}_color"] = '';
            }
        }
        $total_balance -= $curr_action['amount'];
    } else {
        $row['class'] = ($curr_action['repeated']) ? 'transaction-repeat' : 'transaction-single';
        $row['amount_color'] = ($curr_action['amount'] > 0) ? 'red' : 'green';
        foreach ($active_users as $id => $user) {
            if ($id !== 0) { // skip bank
                $user_x_amount = "user_${id}_amount";
                $row[$user_x_amount] = number_format($curr_action[$user_x_amount], 2);
                $row["${user_x_amount}_color"] = ($curr_action[$user_x_amount] > 0) ? 'red' : 'green';
            }
        }
        $total_balance += $curr_action['amount'];
    }
    $table[] = $row;
}

unset($active_users['0']);

?>
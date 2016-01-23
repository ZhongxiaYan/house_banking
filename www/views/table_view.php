<?php

require_once $PAGES['util'];
$active_users['0'] = new User('0', 'Bank', null, null, '1', '0', '0'); // adds bank as an active user for printing tables

?>

<h1>Deposits and Balance History:</h1>
<div class="table-responsive">
    <table class="table table-bordered">
    <thead>
        <tr>
            <th>Personal Balance</th>
            <th>Time</th>
        </tr>
    </thead>
    <tbody>

        <?php foreach ($table as $all_rows):

        $outer_row = $all_rows['outer_row'];
        $deposit_rows = $all_rows['deposit_rows'];
        $transaction_rows = $all_rows['transaction_rows'];

        ?>
        <tr class="expandable-row">
            <td class=<?= e($outer_row['balance_color']) ?>><?= e($outer_row['balance']) ?></td>
            <td><?= e($outer_row['date']) ?></td>
        </tr>

        <?php if (count($deposit_rows) > 0): ?>
        
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

                    <?php foreach ($deposit_rows as $deposit_row): ?>
                        <tr class="deposit-expandable-row">
                            <td type="deposit-name"><?= e($deposit_row['name']) ?></td>
                            <td type="deposit-amount" class=<?= e($deposit_row['amount_color']) ?>><?= e($deposit_row['amount']) ?></td>
                            <td type="deposit-date"><?= e($deposit_row['date']) ?></td>
                            <td type="deposit-note"><?= e($deposit_row['note']) ?></td>
                        </tr>
                        <tr class="deposit-hidden-row">
                            <td colspan="4">
                                <!-- edit and delete buttons -->
                                <form class="form-inline" role="form" action=<?= 'balance.php?submission=deposit_delete&user=' . $view_user->id ?> method="post">
                                    <input type="hidden" name="session_token" value=<?= e($user_session_token) ?>>
                                    <div class="btn-group">
                                        <button type="button" class="btn btn-primary deposit-edit-button" value=<?= e($deposit_row['id']) ?>>Edit</button>
                                        <button type="submit" class="btn btn-primary deposit-delete-button" name="deposit-id" value=<?= e($deposit_row['id']) ?>>Delete</button>
                                    </div>
                                </form>
                                <br><br>
                            </td>
                        </tr>
                    <?php endforeach; ?>

                    </tbody>
                </table>
            </td>
        </tr>

        <?php endif; ?>

        <?php if (count($transaction_rows) > 0): ?>
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
                    
                    <?php foreach ($transaction_rows as $transaction_row): ?>

                        <tr class="transaction-expandable-row">
                            <td type="trans-name"><?= e($transaction_row['name']) ?></td>
                            <td class=<?= e($transaction_row['user_amount_color']) ?>><?= e($transaction_row[$user_amount]) ?></td>
                            <td type="trans-total-amount"><?= e($transaction_row['total_amount']) ?></td>
                            <td type="trans-paid-by" user-id=<?= e($transaction_row['paid_by_id']) ?>><?= e($active_users[$transaction_row['paid_by_id']]->name) ?></td>
                            <td type="trans-date"><?= e($transaction_row['date']) ?></td>
                            <td type="trans-note"><?= e($transaction_row['note']) ?></td>
                        </tr>
                        <tr class="transaction-hidden-row">
                            <td colspan="6">
                                Amount by Person:

                                <?php

                                $user_x_amounts = $transaction_row['user_x_amounts'];
                                foreach ($user_x_amounts as $user_x_amount => $id): ?>
                                
                                <div user-id=<?= e($id) ?>><?= e($active_users[$id]->name) . ': ' . e($transaction_row[$user_x_amount]) ?></div>
                                
                                <?php endforeach; ?>

                                <br>
                                <!-- edit and delete buttons -->
                                <form class="form-inline" role="form" action=<?= 'balance.php?submission=transaction_delete&user=' . $view_user->id ?> method="post">

                                    <input type="hidden" name="trans-is-repeated" trans-id=<?= e($transaction_row['id']) ?> value=<?= e($transaction_row['is_repeated']) ?>>                                 
                                    <input type="hidden" name="session_token" value=<?= e($user_session_token) ?>>

                                    <div class="btn-group">
                                        <button type="button" class="btn btn-primary transaction-edit-button">Edit</button>
                                        <button type="submit" class="btn btn-primary transaction-delete-button" name="trans-id" value=<?= e($transaction_row['id']) ?>>Delete</button>
                                    </div>
                                </form>
                            </td>
                        </tr>
                    
                    <?php endforeach; ?>

                    </tbody>
                </table>
            </td>
        </tr>

        <?php endif; ?>

    <?php 

    endforeach;
    unset($active_users['0']); // remove 'Bank' as a user (see top of this file)
    
    ?>

    </tbody>
    </table>
</div>

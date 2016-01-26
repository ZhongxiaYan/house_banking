<?php

require_once $PAGES['util'];

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
                            <td><?= e($deposit_row['name']) ?></td>
                            <td class=<?= e($deposit_row['amount_color']) ?>><?= e($deposit_row['amount']) ?></td>
                            <td><?= e($deposit_row['date']) ?></td>
                            <td><?= e($deposit_row['note']) ?></td>
                        </tr>
                        <tr class="deposit-hidden-row">
                            <td colspan="4">
                                <!-- edit and delete buttons -->
                                <form class="form-inline" role="form" action=<?= 'balance.php?submission=deposit_delete&user=' . $view_user->id ?> method="post">
                                    <input type="hidden" name="session-token" value=<?= e($user_session_token) ?>>
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
                            <th>Amount</th>
                            <th>Date</th>
                            <th>Note</th>
                        </tr>
                    </thead>
                    <tbody>
                    
                    <?php foreach ($transaction_rows as $transaction_row): ?>

                        <tr class="transaction-expandable-row">
                            <td><?= e($transaction_row['name']) ?></td>
                            <td class=<?= e($transaction_row['view_user_amount_color']) ?>><?= e($transaction_row[$view_user_amount_string]) ?></td>
                            <td><?= e($transaction_row['date']) ?></td>
                            <td><?= e($transaction_row['note']) ?></td>
                        </tr>
                        <tr class="transaction-hidden-row">
                            <td colspan="6">
                                Amount by Person:

                                <?php

                                $user_x_amounts = $transaction_row['user_x_amounts'];
                                foreach ($user_x_amounts as $user_x_amount => $id): ?>
                                
                                <div><?= e($current_users[$id]->name) . ': ' . e($transaction_row[$user_x_amount]) ?></div>
                                
                                <?php endforeach; ?>

                                <br>
                                <!-- edit and delete buttons -->
                                <form class="form-inline" role="form" action=<?= 'balance.php?submission=transaction_delete&user=' . $view_user->id ?> method="post">

                                    <input type="hidden" class="trans-is-repeated" name="trans-is-repeated" value=<?= e($transaction_row['repeated']) ?>>                                 
                                    <input type="hidden" name="session-token" value=<?= e($user_session_token) ?>>

                                    <div class="btn-group">
                                        <button type="button" class="btn btn-primary transaction-edit-button" name="trans-id" value=<?= e($transaction_row['id']) ?>>Edit</button>
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
    
    ?>

    </tbody>
    </table>
</div>

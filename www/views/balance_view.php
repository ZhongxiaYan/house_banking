<?php

require_once $PAGES['util'];

?>
<!DOCTYPE html>
<html>
    <head>
        <?php
            require_once 'head_header.php';
        ?>
        <script src="js/balance.js"></script>
        <style>

            #adjustment-amount {
                color: green;
            }

            #main {
                margin: 20px;
            }

            .transaction-expandable-row {
                background-color: #ffff99;
            }

            .deposit-expandable-row {
                background-color: #ffff99;
            }

            .drag-dest {
                background-color: #dfe2db;
                padding: 10px;
                border-width: 2px;
                border-style: dashed;
                border-color: black;
                border-radius: 10px;
            }

            .drag-src {
                background-color: #cbe32d;                
                padding: 5px;
                border-width: 2px;
                border-style: solid;
                border-color: #a8cd1b;
                border-radius: 5px;
            }

            .drag-user {
                background-color: #cbe32d;
                margin-bottom: 10px;
                padding: 5px;
                border-width: 2px;
                border-style: solid;
                border-color: #a8cd1b;
                border-radius: 5px;
            }

            .paid > .drag-user > .amount-div {
                color: green;
            }

            .owed > .drag-user > .amount-div {
                color: red;
            }

            a.close-button {
                float: right;
                margin-top: -10px;
                margin-right: -10px;
                cursor: pointer;
                color: #fff;
                border: 1px solid #AEAEAE;
                border-radius: 30px;
                background: #605F61;
                font-size: 31px;
                font-weight: bold;
                display: inline-block;
                line-height: 0px;
                padding: 11px 3px;
                z-index: 10;
            }

            .close-button:before {
                content: "×";
            }

            .expandable-row {
                background-color: grey;
            }

            .red {
                color: red;
            }

            .green {
                color: green;
            }

            .table thead tr th {
                background-color: wheat;
            }

            .table-hover tbody tr:hover td, .table-hover tbody tr:hover th {
                background-color: #ffff99;
            }

        </style>
    </head>
    <body>
        <?php
            require_once 'navbar.php';
        ?>
        <div id="main">
            <h1>Create New:</h1>
            <h4>Deposit:</h4>
            <form class="form-inline" role="form" action=<?= 'balance.php?submission=deposit_add&user=' . $view_user->id ?> method="post" id="deposit-form">
                <div class="form-group">
                    <label>Name:</label>
                    <input type="text" class="form-control" name="deposit-name">
                </div>
                <div class="form-group">
                    <label>Amount:</label>
                    <input type="number" class="form-control input-sm" min="0" step="0.01" name="deposit-amount" id="deposit-amount" required>
                </div>
                <div class="form-group">
                    <label>Date:</label>
                    <input type="date" class="form-control" name="deposit-date" max=<?= e($current_date) ?> value=<?= e($current_date) ?> required>
                </div>
                <div class="form-group">
                    <label>Note:</label>
                    <textarea class="form-control" rows="2" name="deposit-note"></textarea>
                </div>
                <input type="hidden" name="session_token" value=<?= e($user_session_token) ?>>
                <button type="submit" class="btn btn-default">Submit</button>
            </form>
            <h4>Transaction:</h4>
            <form class="form-inline" role="form" action=<?= 'balance.php?submission=transaction_add&user=' . $view_user->id ?> method="post" id="trans-form">
                <div class="form-group">
                    <label>Name:</label>
                    <input type="text" class="form-control" name="trans-name" required>
                </div>
                <div class="form-group trans-repeat-toggle">
                    <label>Repeat:</label>
                    <input type="checkbox" name="trans-is-repeated" value="1">
                </div>
                <div class="form-group" required>
                    <label>Date:</label>
                    <input type="date" class="form-control" name="trans-date" max=<?= e($current_date) ?> value=<?= e($current_date) ?> id="trans-start-date">
                </div>
                <div class="form-group trans-repeat-info" style="display:none">
                    <label>Stop Date:</label>
                    <input type="date" class="form-control" name="trans-end-date" min=<?= e($current_date) ?> value=<?= e($current_date) ?> id="trans-end-date">
                    <label>Interval:</label>
                    <input type="number" class="form-control input-sm" step="1" min="1" name="trans-interval-num" id="trans-interval-num" value="1">
                    <select class="form-control" name="trans-interval-unit" id="trans-interval-unit">
                        <option value="d">Day</option>
                        <option value="m">Month</option>
                        <option value="y">Year</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Note:</label>
                    <textarea class="form-control" rows="2" name="trans-note"></textarea>
                </div>

                <br><br>
                <label>Drag these users if needed:</label>

                <?php foreach ($active_users as $id => $user): ?>

                <div class=<?= e("'form-group drag-src user-$id'") ?> user-id=<?= e($id) ?> draggable="true" origin="source">
                    <label><?= e($user->name) ?></label>
                </div>

                <?php endforeach; ?>

                <br><br>

                <div class="row">
                    <div class="col-md-5 drag-dest paid">
                        <label>Users who paid money (out of pocket):</label>
                        <br>
                        <div class="btn-group">
                            <button type="button" class="btn btn-primary split-even">Split Evenly</button>
                            <button type="button" class="btn btn-primary split-prop">Split Proportionally</button>
                            <button type="button" class="btn btn-primary split-custom">Custom Amounts</button>
                        </div>
                        <br>
                        <br>
                    </div>
                    <div class="col-md-5 col-md-offset-2 drag-dest owed">
                        <label>Users who owed money:</label>
                        <br>
                        <div class="btn-group">
                            <button type="button" class="btn btn-primary split-even">Split Evenly</button>
                            <button type="button" class="btn btn-primary split-prop">Split Proportionally</button>
                            <button type="button" class="btn btn-primary split-custom">Custom Amounts</button>
                        </div>
                        <br>
                        <br>
                    </div>
                </div>
                <br>

                <label>Net amounts owed:</label>
                <br>

                <?php foreach ($active_users as $id => $user): ?>

                <div class="form-group user-final-amt">
                    <label><?= e("$user->name:") ?></label>
                    <input type="number" class=<?= e("'form-control input-sm user-$id'") ?> step="0.01" name=<?= e("user_${id}_amount") ?> user-id=<?= e($id) ?> value="0" readonly>
                </div>

                <?php endforeach; ?>

                <br><br>

                <input type="hidden" name="session_token" value=<?= e($user_session_token) ?>>
                <button type="submit" class="btn btn-default">Submit</button>
            </form>

            <?php
                require_once 'table_view.php';
            ?>
        </div>
    </body>
</html>
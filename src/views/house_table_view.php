<h1>Deposits and Transaction History:</h1>
    <div class="table-responsive">
        <table class="table table-bordered table-fixed">
            <thead>
                <tr>
                    <th>Balance</th>
                    <th>Date</th>
                    <th>Type</th>
                    <th>Name</th>
                    <th>Amount</th>
                    <?php
                    
                    foreach ($current_users as $id => $user): ?>
                    
                    <th><?= $user->name ?></th>
                    
                    <?php endforeach; ?>
                    <th>Note</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($table as $row): ?>

                <tr class=<?= e($row['class']) ?>>
                    <td class=<?= e($row['balance_color']) ?>><?= e($row['balance']) ?></td>
                    <td><?= e($row['date']) ?></td>
                    <td><?= e($row['type']) ?></td>
                    <td><?= e($row['name']) ?></td>
                    <td class=<?= e($row['amount_color']) ?>><?= e($row['amount']) ?></td>

                    <?php foreach ($current_users as $id => $user): 
                    $user_x_amount_string = "user_${id}_amount"
                    ?>

                    <td class=<?= e($row["${user_x_amount_string}_color"]) ?>><?= $row[$user_x_amount_string] ?></td>

                    <?php endforeach; ?>
                    
                    <td><?= e($row['note']) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
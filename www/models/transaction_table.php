<?php

class TransactionTable {

    private $db;
    private $single_table;
    private $repeated_table;

    public function __construct($db) {
        global $CONFIG;
        $this->db = $db;
        $this->single_table = $CONFIG['db']['tables']['transactions_single'];
        $this->repeated_table = $CONFIG['db']['tables']['transactions_repeated'];
    }

    public function get_transaction($transaction_id, $is_repeated) {
        $query = sprintf('SELECT * FROM %s WHERE id=?', ($is_repeated) ? $this->repeated_table : $this->single_table);
        $stmt = $this->db->prepare($query);
        if (!$stmt->bind_param('i', $transaction_id)) {
            echo 'Binding parameter failed: (' . $stmt->errno . ') ' . $stmt->error;
        }
        if (!$stmt->execute()) {
            echo 'Execution failed: (' . $stmt->errno . ') ' . $stmt->error;
        }
        return $stmt->get_result()->fetch_assoc();
    }

    public function get_single_transactions($user_id) {
        $all = ($user_id === null);
        if ($all) {
            $query = sprintf('SELECT * FROM %s ORDER BY action_time DESC;', $this->single_table);
        } else {
            $query = sprintf('SELECT * FROM %s WHERE abs(%s) > 1e-6 ORDER BY action_time DESC;', $this->single_table, "user_${user_id}_amount");
        }
        return $this->db->query($query);
    }

    /**
     *  @param $user_id  null if want all transactions
     */
    public function get_single_transactions_array($user_id) {
        $transaction_array = array();
        $transactions = $this->get_single_transactions($user_id);
        while ($transactions && $row = $transactions->fetch_assoc()) {
            $row['type'] = 'transaction';
            $row['repeated'] = 0;
            $transaction_array[] = $row;
        }
        return $transaction_array;
    }

    /**
     *  @param $users_amounts  a map from strings 'user_x_amount' => float amounts, one for each user
     */
    public function add_single_transaction($name, $users_amounts, $datetime, $note, $maker_id) {
        $query_params = 'name, note, action_time, changed_by_id';
        $query_args = '?, ?, ?, ?';

        // construct the query parameter and question marks based on the $users_amounts given
        $user_amounts_array = array();
        $user_amounts_ref = array();
        $parameter_str = 'sssi';
        $count = 0;
        foreach ($users_amounts as $user_x_amount_string => $user_amount) {  // TODO account for the case where some change and others don't
            $query_params = "$query_params, $user_x_amount_string";
            $query_args = "$query_args, ?";
            $user_amounts_array[$count] = $user_amount;
            $user_amounts_ref[$count] = &$user_amounts_array[$count];
            $parameter_str .= 'd';
            $count++;
        }
        $query = sprintf('INSERT INTO %s (%s) VALUES (%s);', $this->single_table, $query_params, $query_args);
        $stmt = $this->db->prepare($query);
        $args = array_merge(array(&$parameter_str, &$name, &$note, &$datetime, &$maker_id), $user_amounts_ref);
        if (!call_user_func_array(array($stmt, 'bind_param'), $args)) {
            echo 'Binding parameter failed: (' . $stmt->errno . ') ' . $stmt->error;
        }
        if (!$stmt->execute()) {
            echo 'Execution failed: (' . $stmt->errno . ') ' . $stmt->error;
        }
    }

    /**
     *  @param $users_amounts  a map from strings 'user_x_amount' => float amounts, one for each user
     */
    public function edit_single_transaction($trans_id, $name, $users_amounts, $datetime, $note, $edittor_id) {
        $query_args = 'name=?, note=?, action_time=?, changed_by_id=?';

        $user_amounts_array = array();
        $user_amounts_ref = array();
        $parameter_str = 'sssi';
        $count = 0;
        foreach ($users_amounts as $user => $user_amount) {
            $query_args .= ', ' . $user . '=?';
            $user_amounts_array[$count] = $user_amount;
            $user_amounts_ref[$count] = &$user_amounts_array[$count];
            $parameter_str .= 'd';
            $count++;
        }
        $parameter_str .= 'i';

        $query = sprintf('UPDATE %s SET %s where id=?;', $this->single_table, $query_args);
        $stmt = $this->db->prepare($query);
        $args = array_merge(array(&$parameter_str, &$name, &$note, &$datetime, &$edittor_id), $user_amounts_ref, array(&$trans_id));
        if (!call_user_func_array(array($stmt, 'bind_param'), $args)) {
            echo 'Binding parameter failed: (' . $stmt->errno . ') ' . $stmt->error;
        }
        if (!$stmt->execute()) {
            echo 'Execution failed: (' . $stmt->errno . ') ' . $stmt->error;
        }
    }

    public function delete_single_transaction($transaction_id) {
        $query = sprintf('DELETE FROM %s WHERE id=?;', $this->single_table);
        $stmt = $this->db->prepare($query);
        if (!$stmt->bind_param('i', $transaction_id)) {
            echo 'Binding parameter failed: (' . $stmt->errno . ') ' . $stmt->error;
        }
        if (!$stmt->execute()) {
            echo 'Execution failed: (' . $stmt->errno . ') ' . $stmt->error;
        }
    }

    public function get_repeated_transactions($user_id) {
        $all = ($user_id === null);
        if ($all) {
            $query = sprintf('SELECT * FROM %s;', $this->repeated_table);
        } else {
            $query = sprintf('SELECT * FROM %s WHERE abs(%s) > 1e-6;', $this->repeated_table, "user_${user_id}_amount");
        }
        return $this->db->query($query);
    }

    /**
     *  @param $user_id  null if want all transactions
     */
    public function get_repeated_transactions_array($user_id) {
        $transaction_array = array();
        $all = ($user_id === null);
        // duplicate repeated transaction for the valid period
        $repeated_transactions = $this->get_repeated_transactions($user_id);
        while ($repeated_transactions && $row = $repeated_transactions->fetch_assoc()) {
            $row['type'] = 'transaction (repeated)';
            $row['repeated'] = 1;

            // parse repeat interval
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

            // apply repeat interval from start to end while adding repeated transactions to the transaction array
            $suffix = '+ ' . $row['repeat_interval_num'] . $suffix;
            $end_time = min(strtotime($row['end_date']), strtotime(date('Y-m-d')));
            $curr_date = $row['start_date'];
            $curr_time = strtotime($curr_date);
            while ($curr_time <= $end_time) {
                $row['action_time'] = $curr_date;
                
                $transaction_array[] = $row;

                $curr_time = strtotime($curr_date . $suffix);
                $curr_date = date('Y-m-d', $curr_time);
            }
        }
        return $transaction_array;
    }

    public function add_repeated_transaction($name, $users_amounts, $start_date, $end_date, $repeat_interval_num, $repeat_interval_unit, $note, $maker_id) {
        $query_params = 'name, note, start_date, end_date, repeat_interval_unit, repeat_interval_num, changed_by_id';
        $query_args = '?, ?, ?, ?, ?, ?, ?';

        $user_amounts_array = array();
        $user_amounts_ref = array();
        $parameter_str = 'sssssii';
        $count = 0;
        foreach ($users_amounts as $user => $user_amount) {
            $query_params = "$query_params, $user"; // TODO simplify
            $query_args = "$query_args, ?";
            $user_amounts_array[$count] = $user_amount;
            $user_amounts_ref[$count] = &$user_amounts_array[$count];
            $parameter_str .= 'd';
            $count++;
        }
        $query = sprintf('INSERT INTO %s (%s) VALUES (%s)', $this->repeated_table, $query_params, $query_args);
        $stmt = $this->db->prepare($query);
        $args = array_merge(array(&$parameter_str, &$name, &$note, &$start_date, &$end_date, &$repeat_interval_unit, &$repeat_interval_num, &$maker_id), $user_amounts_ref);
        if (!call_user_func_array(array($stmt, 'bind_param'), $args)) {
            echo 'Binding parameter failed: (' . $stmt->errno . ') ' . $stmt->error;
        }
        if (!$stmt->execute()) {
            echo 'Execution failed: (' . $stmt->errno . ') ' . $stmt->error;
        }
    }

    public function edit_repeated_transaction($trans_id, $name, $users_amounts, $start_date, $end_date, $repeat_interval_num, $repeat_interval_unit, $note, $edittor_id) {
        $query_args = 'name=?, note=?, start_date=?, end_date=?, repeat_interval_unit=?, repeat_interval_num=?, changed_by_id=?';

        $user_amounts_array = array();
        $user_amounts_ref = array();
        $parameter_str = 'sssssii';
        $count = 0;
        foreach ($users_amounts as $user => $user_amount) {
            $query_args .= ', ' . $user . '=?'; // TODO append smarter
            $user_amounts_array[$count] = $user_amount;
            $user_amounts_ref[$count] = &$user_amounts_array[$count];
            $parameter_str .= 'd';
            $count++;
        }
        $parameter_str .= 'i';
        
        $query = sprintf('UPDATE %s SET %s where id=?', $this->repeated_table, $query_args);
        $stmt = $this->db->prepare($query);
        $args = array_merge(array(&$parameter_str, &$name, &$note, &$start_date, &$end_date, &$repeat_interval_unit, &$repeat_interval_num, &$edittor_id), $user_amounts_ref, array(&$trans_id));
        if (!call_user_func_array(array($stmt, 'bind_param'), $args)) {
            echo 'Binding parameter failed: (' . $stmt->errno . ') ' . $stmt->error;
        }
        if (!$stmt->execute()) {
            echo 'Execution failed: (' . $stmt->errno . ') ' . $stmt->error;
        }
    }

    public function delete_repeated_transaction($transaction_id) {
        $query = sprintf('DELETE FROM %s WHERE id=?;', $this->repeated_table);
        $stmt = $this->db->prepare($query);
        if (!$stmt->bind_param('i', $transaction_id)) {
            echo 'Binding parameter failed: (' . $stmt->errno . ') ' . $stmt->error;
        }
        if (!$stmt->execute()) {
            echo 'Execution failed: (' . $stmt->errno . ') ' . $stmt->error;
        }
    }    

}

?>
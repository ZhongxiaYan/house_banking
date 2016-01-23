<?php

class DepositTable {

    private $db;
    private $table;

    public function __construct($db) {
        global $CONFIG;
        $this->db = $db;
        $this->table = $CONFIG['db']['tables']['deposits'];
    }

    /**
     *  @param $depositor_id   the id of the depositor
     *  @param $all            get all deposits if 1, just the specified depositor's if 0
     */
    public function get_deposits($depositor_id) {
        $all = ($depositor_id === null);
        if ($all) {
            $query = sprintf('SELECT * FROM %s ORDER BY action_time DESC;', $this->table);
        } else {
            $query = sprintf('SELECT * FROM %s WHERE user_id = %s ORDER BY action_time DESC;', $this->table, $depositor_id);
        }
        return $this->db->query($query);
    }

    /**
     *  @param $depositor_id  null if want all deposits
     */
    public function get_deposits_array(&$deposit_array, $depositor_id) {
        $deposit_array = array();
        $deposits = $this->get_deposits($depositor_id);
        $balance = 0.0; // sum up the cost for every transaction then subtract as we go
        while ($row = $deposits->fetch_assoc()) {
            $row['type'] = 'deposit';
            if ($depositor_id === null) {
                $row['paid_by_id'] = $row['user_id'];
            } else {
                $row['paid_by_id'] = $depositor_id;
            }
            $deposit_array[] = $row;
            $balance += floatval($row['amount']);
        }
        return $balance;
    }

    /** 
     *  @param $depositor_id  the user toward whom the deposit will go to
     *  @param $deposit_id    the id of the deposit itself in the database
     *  @param $adder_id    the id of the user (possibly an admin) that adds this deposit
     */
    public function add_deposit($depositor_id, $name, $amount, $datetime, $note, $adder_id) {
        $query = sprintf('INSERT INTO %s (name, user_id, amount, action_time, note, changed_by_id) 
                            VALUES (?, ?, ?, ?, ?, ?);', $this->table);
        $stmt = $this->db->prepare($query);
        if (!$stmt->bind_param('sidssi', $name, $depositor_id, $amount, $datetime, $note, $adder_id)) {
            echo 'Binding parameter failed: (' . $stmt->errno . ') ' . $stmt->error;
        }
        if (!$stmt->execute()) {
            echo 'Execution failed: (' . $stmt->errno . ') ' . $stmt->error;
        }
    }

    /** 
     *  @param $deposit_id    the id of the deposit itself in the database
     *  @param $edittor_id    the id of the user (possibly an admin) that changes this deposit
     */
    public function edit_deposit($deposit_id, $name, $amount, $datetime, $note, $edittor_id) {
        $query = sprintf('UPDATE %s SET name=?, amount=?, action_time=?, note=?, changed_by_id=? WHERE id=?;', $this->table);
        $stmt = $this->db->prepare($query);
        if (!$stmt->bind_param('sdssii', $name, $amount, $datetime, $note, $edittor_id, $deposit_id)) {
            echo 'Binding parameter failed: (' . $stmt->errno . ') ' . $stmt->error;
        }
        if (!$stmt->execute()) {
            echo 'Execution failed: (' . $stmt->errno . ') ' . $stmt->error;
        }
    }

    /**
     *  @param $deposit_id    the id of the deposit itself in the database
     */
    public function delete_deposit($deposit_id) {
        $query = sprintf('DELETE FROM %s WHERE id=?;', $this->table);
        $stmt = $this->db->prepare($query);
        if (!$stmt->bind_param('i', $deposit_id)) {
            echo 'Binding parameter failed: (' . $stmt->errno . ') ' . $stmt->error;
        }
        if (!$stmt->execute()) {
            echo 'Execution failed: (' . $stmt->errno . ') ' . $stmt->error;
        }
    }
}

?>
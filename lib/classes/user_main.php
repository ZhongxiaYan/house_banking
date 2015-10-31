<?php


class UserMain {

	private $transactions_s;
	private $transactions_r;
	private $deposits;
	private $user_id;
	private $user_name;
	private $db;
	private $pay_table;
	private $trans_table;

	public function __construct($user_id, $name, $db, $deposit_table, $trans_single_table, $trans_repeated_table) {
		$this->user_id = $user_id;
		$this->user_name = $name;
		$this->db = $db;
		$this->pay_table = $deposit_table;
		$this->trans_table = $trans_single_table;
		$this->trans_table_r = $trans_repeated_table;
	}

	public function get_balance() {
		return 0;
	}

	public function get_single_transactions($all) {
		$query = sprintf('SELECT * FROM %s%s ORDER BY action_time DESC;', $this->trans_table,
							($all ? '' : (' WHERE abs(user_' . $this->user_id . '_amount) > 1e-6')));
		return $this->transactions_s = $this->db->query($query);
	}

	public function make_single_transaction($name, $amount, $paid_by_id, $users_amounts, $action_time, $note) {
		$query_head = 'INSERT INTO ' . $this->trans_table . ' (name, amount, paid_by_id, note, action_time, made_by';
		$query_tail = ') VALUES (?, ?, ?, ?, ?, ?';
		$user_amounts_array = array();
		$user_amounts_ref = array();
		$parameter_str = 'sdisss';
		$count = 0;
		foreach ($users_amounts as $user => $user_amount) {
			$query_head = $query_head . ', ' . $user;
			$query_tail = $query_tail . ', ?';
			$user_amounts_array[$count] = $user_amount;
			$user_amounts_ref[$count] = &$user_amounts_array[$count];
			$parameter_str .= 'd';
			$count++;
		}
		$query = $query_head . $query_tail . ');';
		$stmt = $this->db->prepare($query);
		$args = array_merge(array(&$parameter_str, &$name, &$amount, &$paid_by_id, &$note, &$action_time, &$this->user_name), $user_amounts_ref);
		if (!call_user_func_array(array($stmt, 'bind_param'), $args)) {
			echo 'Binding parameter failed: (' . $stmt->errno . ') ' . $stmt->error;
		}
		if (!$stmt->execute()) {
			echo 'Execution failed: (' . $stmt->errno . ') ' . $stmt->error;
		}
	}

	public function edit_single_transaction($trans_id, $name, $amount, $paid_by_id, $users_amounts, $action_time, $note) {
		$query_head = 'UPDATE ' . $this->trans_table . ' SET name=?, amount=?, paid_by_id=?, note=?, action_time=?, made_by=?';
		$query_tail = ' WHERE id=?;';
		$user_amounts_array = array();
		$user_amounts_ref = array();
		$parameter_str = 'sdisss';
		$count = 0;
		foreach ($users_amounts as $user => $user_amount) {
			$query_head .= ', ' . $user . '=?';
			$user_amounts_array[$count] = $user_amount;
			$user_amounts_ref[$count] = &$user_amounts_array[$count];
			$parameter_str .= 'd';
			$count++;
		}
		$parameter_str .= 'i';
		$query = $query_head . $query_tail;
		$stmt = $this->db->prepare($query);
		$args = array_merge(array(&$parameter_str, &$name, &$amount, &$paid_by_id, &$note, &$action_time, &$this->user_name), $user_amounts_ref, array(&$trans_id));
		if (!call_user_func_array(array($stmt, 'bind_param'), $args)) {
			echo 'Binding parameter failed: (' . $stmt->errno . ') ' . $stmt->error;
		}
		if (!$stmt->execute()) {
			echo 'Execution failed: (' . $stmt->errno . ') ' . $stmt->error;
		}
	}

	public function delete_single_transaction($transaction_id) {
		$query = sprintf('DELETE FROM %s WHERE id=?;', $this->trans_table);
		$stmt = $this->db->prepare($query);
		if (!$stmt->bind_param('i', $transaction_id)) {
			echo 'Binding parameter failed: (' . $stmt->errno . ') ' . $stmt->error;
		}
		if (!$stmt->execute()) {
			echo 'Execution failed: (' . $stmt->errno . ') ' . $stmt->error;
		}
	}

	public function get_repeated_transactions($all) {
		$query = sprintf('SELECT * FROM %s%s;', $this->trans_table_r,
							($all ? '' : (' WHERE abs(user_' . $this->user_id . '_amount) > 1e-6')));
		return $this->transactions_r = $this->db->query($query);
	}

	public function make_repeated_transaction($name, $amount, $paid_by_id, $users_amounts, $start_date, $end_date, $repeat_interval_num, $repeat_interval_unit, $note) {
		$query_head = 'INSERT INTO ' . $this->trans_table_r . ' (name, amount, paid_by_id, note, start_date, end_date, repeat_interval_unit, repeat_interval_num, made_by';
		$query_tail = ') VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?';
		$user_amounts_array = array();
		$user_amounts_ref = array();
		$parameter_str = 'sdissssis';
		$count = 0;
		foreach ($users_amounts as $user => $user_amount) {
			$query_head = $query_head . ', ' . $user;
			$query_tail = $query_tail . ', ?';
			$user_amounts_array[$count] = $user_amount;
			$user_amounts_ref[$count] = &$user_amounts_array[$count];
			$parameter_str .= 'd';
			$count++;
		}
		$query = $query_head . $query_tail . ');';
		$stmt = $this->db->prepare($query);
		$args = array_merge(array(&$parameter_str, &$name, &$amount, &$paid_by_id, &$note, &$start_date, &$end_date, &$repeat_interval_unit, &$repeat_interval_num, &$this->user_name), $user_amounts_ref);
		if (!call_user_func_array(array($stmt, 'bind_param'), $args)) {
			echo 'Binding parameter failed: (' . $stmt->errno . ') ' . $stmt->error;
		}
		if (!$stmt->execute()) {
			echo 'Execution failed: (' . $stmt->errno . ') ' . $stmt->error;
		}
	}

	public function edit_repeated_transaction($trans_id, $name, $amount, $paid_by_id, $users_amounts, $start_date, $end_date, $repeat_interval_num, $repeat_interval_unit, $note) {
		$query_head = 'UPDATE ' . $this->trans_table_r . ' SET name=?, amount=?, paid_by_id=?, note=?, start_date=?, end_date=?, repeat_interval_unit=?, repeat_interval_num=?, made_by=?';
		$query_tail = ' WHERE id=?;';
		$user_amounts_array = array();
		$user_amounts_ref = array();
		$parameter_str = 'sdissssis';
		$count = 0;
		foreach ($users_amounts as $user => $user_amount) {
			$query_head .= ', ' . $user . '=?';
			$user_amounts_array[$count] = $user_amount;
			$user_amounts_ref[$count] = &$user_amounts_array[$count];
			$parameter_str .= 'd';
			$count++;
		}
		$parameter_str .= 'i';
		$query = $query_head . $query_tail;
		$stmt = $this->db->prepare($query);
		$args = array_merge(array(&$parameter_str, &$name, &$amount, &$paid_by_id, &$note, &$start_date, &$end_date, &$repeat_interval_unit, &$repeat_interval_num, &$this->user_name), $user_amounts_ref, array(&$trans_id));
		if (!call_user_func_array(array($stmt, 'bind_param'), $args)) {
			echo 'Binding parameter failed: (' . $stmt->errno . ') ' . $stmt->error;
		}
		if (!$stmt->execute()) {
			echo 'Execution failed: (' . $stmt->errno . ') ' . $stmt->error;
		}
	}

	public function delete_repeated_transaction($transaction_id) {
		$query = sprintf('DELETE FROM %s WHERE id=?;', $this->trans_table_r);
		$stmt = $this->db->prepare($query);
		if (!$stmt->bind_param('i', $transaction_id)) {
			echo 'Binding parameter failed: (' . $stmt->errno . ') ' . $stmt->error;
		}
		if (!$stmt->execute()) {
			echo 'Execution failed: (' . $stmt->errno . ') ' . $stmt->error;
		}
	}

	public function get_deposits() {
		$query = sprintf('SELECT * 
							FROM %s WHERE user_id = %d ORDER BY action_time DESC;',
							$this->pay_table, $this->user_id);
		return $this->deposits = $this->db->query($query);
	}

	public function make_deposit($name, $amount, $datetime, $note) {
		$query = sprintf('INSERT INTO %s (name, user_id, user_name, amount, action_time, note) 
							VALUES (?, ?, ?, ?, ?, ?);', $this->pay_table);
		$stmt = $this->db->prepare($query);
		if (!$stmt->bind_param('sisdss', $name, $this->user_id, $this->user_name, $amount, $datetime, $note)) {
			echo 'Binding parameter failed: (' . $stmt->errno . ') ' . $stmt->error;
		}
		if (!$stmt->execute()) {
			echo 'Execution failed: (' . $stmt->errno . ') ' . $stmt->error;
		}
	}

	public function edit_deposit($deposit_id, $name, $amount, $datetime, $note) {
		$query = sprintf('UPDATE %s SET name=?, amount=?, action_time=?, note=? WHERE id=?;', $this->pay_table);
		$stmt = $this->db->prepare($query);
		if (!$stmt->bind_param('sdssi', $name, $amount, $datetime, $note, $deposit_id)) {
			echo 'Binding parameter failed: (' . $stmt->errno . ') ' . $stmt->error;
		}
		if (!$stmt->execute()) {
			echo 'Execution failed: (' . $stmt->errno . ') ' . $stmt->error;
		}
	}

	public function delete_deposit($deposit_id) {
		$query = sprintf('DELETE FROM %s WHERE id=?;', $this->pay_table);
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
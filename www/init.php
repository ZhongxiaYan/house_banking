<?php

require_once '../lib/config.php';
require_once '../lib/classes/user_main.php';

if (session_id() == '')
	session_start();

$mysqli = mysqli_connect($config['db']['host'], $config['db']['username'], $config['db']['password'], $config['db']['dbname']);
if ($mysqli->connect_error) {
	die('Connection failed: ' . $mysqli->connect_error);
}

$curr_user;
$users;
$page = basename($_SERVER['PHP_SELF']);

if (array_key_exists('user_id', $_SESSION) && array_key_exists('user_session_token', $_SESSION)) { // logged in
	date_default_timezone_set('America/Los_Angeles');
	$user_id = $_SESSION['user_id'];
	$user_session_token = $_SESSION['user_session_token'];
	$stmt = $mysqli->prepare('SELECT * FROM ' . $config['db']['tables']['userinfo'] . ' WHERE id = ?;');
	if (!$stmt->bind_param('s', $user_id)) {
		echo 'Binding parameter failed: (' . $stmt->errno . ') ' . $stmt->error;
	}
	if (!$stmt->execute()) {
		echo 'Execution failed: (' . $stmt->errno . ') ' . $stmt->error;
	} else {
		$user_info = $stmt->get_result()->fetch_assoc();
		$curr_user = new UserMain($user_id, $user_session_token, $user_info['first_name'] . ' ' . $user_info['last_name'], 
			$user_info['verified'], $user_info['admin'],  $user_info['deleted'], $mysqli, $config['db']['tables']['deposits'], 
			$config['db']['tables']['transactions_single'], $config['db']['tables']['transactions_repeated']);
	}
	$users_sql = $mysqli->query('SELECT * FROM ' . $config['db']['tables']['userinfo'] . ' WHERE verified=1 AND deleted=0;');
	$users = array();
	$users_sql->data_seek(0);
	while ($user = $users_sql->fetch_assoc()) {
		$user['name'] = $user['first_name'] . ' ' . substr($user['last_name'], 0, 1);
		$users[$user['id']] = $user;
	}
	if ($curr_user->is_deleted || (!$curr_user->is_verified)) {
		if ($curr_user->is_deleted) {
			set_session_status('deleted');
		} else {
			set_session_status('unverified');
		}
		unset($curr_user);
		set_clear_user();
		if ($page !== 'login.php') {
			set_redirect('login.php');
		}
	}
}

if (isset($curr_user)) {
	if ($curr_user->is_admin) {
		$view_user_id = array_key_exists('user', $_GET) ? $_GET['user'] : $curr_user->id;
		$view_user_info = $users[$view_user_id];
		$view_user = new UserMain($view_user_id, $curr_user->session_token, $view_user_info['name'], $view_user_info['verified'],
			$view_user_info['admin'],  $user_info['deleted'], $mysqli, $config['db']['tables']['deposits'],
			$config['db']['tables']['transactions_single'], $config['db']['tables']['transactions_repeated']);
	} else {
		$view_user = $curr_user;
	}
}

// Post/Redirect/Get structure for form submission
if (array_key_exists('submission', $_GET)) { // submitted some form, redirect to the same page
	if ($_GET['submission'] === 'logout') { // clear everything
		set_clear_user();
		set_redirect('login.php');
	} else if (count($_POST) > 0) {
		$_SESSION['submission'] = $_GET['submission'];
		foreach ($_POST as $key => $value) {
			$_SESSION[$key] = $value;
		}
		set_redirect($page . '?user=' . $view_user->id);
	}
} else if (array_key_exists('submission', $_SESSION)) {
	set_clear();
	if ($_SESSION['submission'] === 'register_user') {
		// checks that register code is correct
		$query = 'SELECT * FROM ' . $config['db']['tables']['register_codes'] . ' WHERE code="' . $_SESSION['register-code'] . '";';
		$result = $mysqli->query($query);
		if (!$result || $result->num_rows === 0) {			
			set_session_status('register code not found');
		} else {
			$result = register_user($_SESSION['register-first-name'], $_SESSION['register-last-name'], $_SESSION['register-email'], $_SESSION['register-password'], $mysqli, $config['db']['tables']['userinfo'], $config['db']['tables']['transactions_single'], $config['db']['tables']['transactions_repeated']);
			if ($result) {
				// delete the register code that was used
				$mysqli->query('DELETE FROM ' . $config['db']['tables']['register_codes'] . ' WHERE code="' . $_SESSION['register-code'] . '";');

				set_session_status('just registered');
				set_redirect('login.php');
			} else {
				set_session_status('register failed');
				set_redirect('register.php');
			}
		}
	} else if ($_SESSION['submission'] === 'login') {
		$userinfo = login($_SESSION['login-email'], $_SESSION['login-password'], $mysqli, $config['db']['tables']['userinfo']);
		if ($userinfo) { // logged in
			$_SESSION['user_id'] = $userinfo['id'];
			$_SESSION['user_session_token'] = hash("sha512", mt_rand(0, mt_getrandmax()));	
			set_session_status('just logged in');
			set_redirect('balance.php');
		} else { // incorrect password
			set_session_status('wrong login');
		}
	} else if ($_SESSION['session_token'] === $user_session_token) { // must have correct session token if trying to submit
		set_session_status('submission');
		switch ($_SESSION['submission']) {
			case 'deposit_submit':
				$view_user->make_deposit($_SESSION['deposit-name'], $_SESSION['deposit-amount'], $_SESSION['deposit-date'], $_SESSION['deposit-note'], $curr_user->id);
				break;
			case 'deposit_edit':
				$view_user->edit_deposit($_SESSION['deposit-id'], $_SESSION['deposit-name'], $_SESSION['deposit-amount'], $_SESSION['deposit-date'], $_SESSION['deposit-note'], $curr_user->id);
				break;
			case 'deposit_delete':
				$view_user->delete_deposit($_SESSION['deposit-id']);
				break;
			case 'transaction_submit':
				$user_amounts = array();
				$paid_by_id = get_user_amounts($user_amounts);
				if (array_key_exists('trans-repeat', $_SESSION) && $_SESSION['trans-repeat'] === 'yes') {
					$view_user->make_repeated_transaction($_SESSION['trans-name'], $_SESSION['trans-total-amount'], 
						$paid_by_id, $user_amounts, $_SESSION['trans-date'], $_SESSION['trans-end-date'], 
						$_SESSION['trans-interval-num'], $_SESSION['trans-interval-unit'], $_SESSION['trans-note'], $curr_user->id);
				} else {
					$view_user->make_single_transaction($_SESSION['trans-name'], $_SESSION['trans-total-amount'], 
						$paid_by_id, $user_amounts, $_SESSION['trans-date'], $_SESSION['trans-note'], $curr_user->id);				
				}
				break;
			case 'transaction_edit':
				$user_amounts = array();
				$paid_by_id = get_user_amounts($user_amounts);
				if (array_key_exists('trans-repeat', $_SESSION) && $_SESSION['trans-repeat'] === 'yes') {
					$view_user->edit_repeated_transaction($_SESSION['trans-id'], $_SESSION['trans-name'], 
						$_SESSION['trans-total-amount'], $paid_by_id, $user_amounts, $_SESSION['trans-date'],
						$_SESSION['trans-end-date'], $_SESSION['trans-interval-num'], $_SESSION['trans-interval-unit'], $_SESSION['trans-note'], $curr_user->id);
				} else {
					$view_user->edit_single_transaction($_SESSION['trans-id'], $_SESSION['trans-name'], 
						$_SESSION['trans-total-amount'], $paid_by_id, $user_amounts, $_SESSION['trans-date'], $_SESSION['trans-note'], $curr_user->id);				
				}
				break;
			case 'transaction_delete':
				if ($_SESSION['trans-type'] === 's') {
					$view_user->delete_single_transaction($_SESSION['trans-id']);
				} else {
					$view_user->delete_repeated_transaction($_SESSION['trans-id']);
				}
				break;
		}
		if (isset($curr_user) && $curr_user->is_admin) {
			switch ($_SESSION['submission']) {
				case 'verify_users':
					$column = 'verified';
				case 'make_admins':
					$column = isset($column) ? $column : 'admin';
				case 'delete_users':
					$column = isset($column) ? $column : 'deleted';
					$changed_ids = array();
					foreach ($_SESSION as $key => $value) {
						if (preg_match('/^select(\d+)$/', $key, $match)) {
							$changed_ids[] = $match[1];
						}
					}
					change_user($column, $changed_ids, $mysqli, $config['db']['tables']['userinfo']);
					set_session_status('admin changed user');
					set_redirect('admin.php');
					break;
				case 'register_code':
					change_register_code($mysqli);
					break;
			}
		}
	}
}

// set redirect and status
if (isset($curr_user)) { // logged in
	if ($page === 'admin.php' && !$curr_user->is_admin) {
		set_session_status('not admin');
		set_redirect('login.php');
	} else if ($page === 'login.php' || $page === 'register.php') {
		set_session_status('already logged in');
		set_redirect('balance.php');
	}
} else if (!get_session_status()) {
	if (!($page === 'login.php' || $page === 'register.php')) {
		set_session_status('not logged in');
		set_redirect('login.php');
	}
}

if (has_clear()) {
	clear_session();
}
if (has_redirect()) {
	redirect();
}

	

function set_session_status($status) {
	$_SESSION['session_status'] = $status;
}

function get_session_status() {
	if (isset($_SESSION['session_status'])) {
		return $_SESSION['session_status'];
	} else {
		return 0;
	}
}

function unset_session_status() {
	unset($_SESSION['session_status']);
}

function has_clear() {
	return isset($_SESSION['session_clear']);
}

function set_clear() {
	if (!has_clear()) {
		$_SESSION['session_clear'] = 1;
	}
}

function set_clear_user() {
	$_SESSION['session_clear'] = 2;
}

function clear_session() {
	$keep_info = array();
	if (isset($_SESSION['session_status'])) {
		$keep_info['session_status'] = $_SESSION['session_status'];
	}
	if (isset($_SESSION['session_redirect'])) {
		$keep_info['session_redirect'] = $_SESSION['session_redirect'];
	}

	if ($_SESSION['session_clear'] === 1) {
		if (isset($_SESSION['user_id'])) {
			$keep_info['user_id'] = $_SESSION['user_id'];
		}
		if (isset($_SESSION['user_session_token'])) {
			$keep_info['user_session_token'] = $_SESSION['user_session_token'];
		}
	}
	session_unset();
	$_SESSION = array_merge($_SESSION, $keep_info);
}

function set_redirect($page) {
	$_SESSION['session_redirect'] = $page;
}

function redirect() {
	$redirect_page = $_SESSION['session_redirect'];
	unset($_SESSION['session_redirect']);
	header('HTTP/1.1 303 See Other');
	header('Location: ' . $redirect_page);
	exit;
}

function has_redirect() {
	return isset($_SESSION['session_redirect']);
}

function register_user($first_name, $last_name, $email, $password, $db, $user_table, $trans_s_table, $trans_r_table) {
	
	$query = sprintf('SELECT * FROM %s WHERE email=?;', $user_table);
	$stmt = $db->prepare($query);
	if (!$stmt->bind_param('s', $email)) {
		echo 'Binding parameter failed: (' . $stmt->errno . ') ' . $stmt->error;
	}
	if (!$stmt->execute()) {
		echo 'Execution failed: (' . $stmt->errno . ') ' . $stmt->error;
	}
	if ($stmt->get_result()->fetch_assoc()) {
		return 0;
	}
	$query = sprintf('INSERT INTO %s (first_name, last_name, email, pass_salt_hash) 
						VALUES (?, ?, ?, ?);', $user_table);
	$stmt = $db->prepare($query);
	$hashed_pass = password_hash($password, PASSWORD_DEFAULT);
	if (!$stmt->bind_param('ssss', $first_name, $last_name, $email, $hashed_pass)) {
		echo 'Binding parameter failed: (' . $stmt->errno . ') ' . $stmt->error;
	}
	if (!$stmt->execute()) {
		echo 'Execution failed: (' . $stmt->errno . ') ' . $stmt->error;
	}
	$new_id = $db->insert_id;
	$query = sprintf('ALTER TABLE %s ADD COLUMN user_%d_amount FLOAT(12, 4) DEFAULT 0;', $trans_s_table, $new_id);
	$db->query($query);
	$query = sprintf('ALTER TABLE %s ADD COLUMN user_%d_amount FLOAT(12, 4) DEFAULT 0;', $trans_r_table, $new_id);
	$db->query($query);

	return 1;
}

function change_user($column, $changed_ids, $db, $user_table) {
	if (sizeof($changed_ids) == 0) {
		return;
	}
	$query_head = 'UPDATE ' . $user_table . ' SET ' . $column . '=1 WHERE id=?';
	$changed_ids_ref = array();
	$changed_ids_ref[0] = &$changed_ids[0];
	$parameter_str = 'i';
	for ($i = 1; $i < sizeof($changed_ids); $i++) { // we want to iterate the length - 1 times
		$query_head .= ' OR id=?';
		$parameter_str .= 'i';
		$changed_ids_ref[$i] = &$changed_ids[$i];
	}
	$query = $query_head . ';';
	$stmt = $db->prepare($query);
	$args = array_merge(array(&$parameter_str), $changed_ids_ref);
	if (!call_user_func_array(array($stmt, 'bind_param'), $args)) {
		echo 'Binding parameter failed: (' . $stmt->errno . ') ' . $stmt->error;
	}
	if (!$stmt->execute()) {
		echo 'Execution failed: (' . $stmt->errno . ') ' . $stmt->error;
	}
}

function login($email, $password, $db, $user_table) {
	$stmt = $db->prepare('SELECT id, first_name, last_name, email, pass_salt_hash FROM ' . $user_table . ' WHERE email = ?;');
	if (!$stmt->bind_param('s', $email)) {
		echo 'Binding parameter failed: (' . $stmt->errno . ') ' . $stmt->error;
	}
	if (!$stmt->execute()) {
		echo 'Execution failed: (' . $stmt->errno . ') ' . $stmt->error;
	} else {
		$user_info = $stmt->get_result()->fetch_assoc();
		if (password_verify($password, $user_info['pass_salt_hash'])) {
			return $user_info;
		}
	}
	return 0;
}

function get_user_amounts(&$user_amounts) {
	global $users;
	$paid_by_id = 0;
	foreach ($users as $id => $user) {
		$key = 'user_' . $id . '_amount';
		if ($id == $_SESSION['trans-paid-by']) {
			$paid_by_id = $id;
		}
		$user_amounts[$key] = $_SESSION[$key];
	}
	return $paid_by_id;
}

function change_register_code($db) {
	global $config;
	if (array_key_exists('delete', $_SESSION)) {
		foreach ($_SESSION as $key => $value) {
			if (preg_match('/^select(\w+)$/', $key, $match)) {
				$query = 'DELETE FROM ' . $config['db']['tables']['register_codes'] . ' WHERE code=?;';
				$stmt = $db->prepare($query);
				if (!$stmt->bind_param('s', $match[1])) {
					echo 'Binding parameter failed: (' . $stmt->errno . ') ' . $stmt->error;
				}
				if (!$stmt->execute()) {
					echo 'Execution failed: (' . $stmt->errno . ') ' . $stmt->error;
				}
			}
		}
		set_session_status('admin deleted register codes');
	} else if (array_key_exists('generate', $_SESSION)) {
		$new_code = '';
		$lines = file($config['paths']['word_list']);
		for ($i = 0; $i < 5; $i++) {
			$rand = rand(0, 998);
			$new_code .= preg_replace('/\s+/S', "", $lines[$rand]);
		}
		$query = 'INSERT INTO ' . $config['db']['tables']['register_codes'] . ' (code) VALUES ("' . $new_code . '");';
		$db->query($query);
		set_session_status('admin generated register codes');
	}
}

?>
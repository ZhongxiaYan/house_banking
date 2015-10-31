<?php

if (session_id() == '')
	session_start();

$mysqli = mysqli_connect($config['db']['host'], $config['db']['username'], $config['db']['password'], $config['db']['dbname']);
if ($mysqli->connect_error) {
	die('Connection failed: ' . $mysqli->connect_error);
}

$curr_user;
$user_id;
$users;

if (array_key_exists('user_id', $_SESSION)) { // logged in
	date_default_timezone_set('America/Los_Angeles');
	$user_id = $_SESSION['user_id'];
	$stmt = $mysqli->prepare('SELECT first_name, last_name FROM ' . $config['db']['tables']['userinfo'] . ' WHERE id = ?;');
	if (!$stmt->bind_param('s', $user_id)) {
		echo 'Binding parameter failed: (' . $stmt->errno . ') ' . $stmt->error;
	}
	if (!$stmt->execute()) {
		echo 'Execution failed: (' . $stmt->errno . ') ' . $stmt->error;
	} else {
		$user_names = $stmt->get_result()->fetch_assoc();
		$curr_user = new UserMain($user_id, $user_names['first_name'] . ' ' . $user_names['last_name'], $mysqli, $config['db']['tables']['deposits'], $config['db']['tables']['transactions_single'], $config['db']['tables']['transactions_repeated']);
	}
	$users = $mysqli->query('SELECT id, first_name, last_name, email FROM ' . $config['db']['tables']['userinfo']);
	$id_to_user = array();
	$users->data_seek(0);
	while ($user = $users->fetch_assoc()) {
	    $id_to_user[$user['id']] = $user['first_name'] . ' ' . substr($user['last_name'], 0, 1);
	}
} else if (basename($_SERVER['PHP_SELF']) == 'login.php' || basename($_SERVER['PHP_SELF']) == 'register.php') {

} else {
	header('HTTP/1.1 303 See Other');
	header('Location: login.php');
	exit;
}

// Post/Redirect/Get structure for form submission
if (array_key_exists('submission', $_GET)) { // submitted some form, redirect to the same page
	if ($_GET['submission'] == 'logout') { // clear everything
		echo 'hi';
		session_unset();
		session_destroy();
	} else if (count($_POST) > 0) {
		$_SESSION['submission'] = $_GET['submission'];
		foreach ($_POST as $key => $value) {
			$_SESSION[$key] = $value;
		}
	}
    header('HTTP/1.1 303 See Other');
    header('Location: ' . basename($_SERVER['PHP_SELF']));
    exit;
} else if (array_key_exists('submission', $_SESSION)) {
	switch ($_SESSION['submission']) {
		case 'deposit':
			$curr_user->make_deposit($_SESSION['deposit-name'], $_SESSION['deposit-amount'], $_SESSION['deposit-date'], $_SESSION['deposit-note']);
			break;
		case 'deposit_edit':
			$curr_user->edit_deposit($_SESSION['deposit-id'], $_SESSION['deposit-name'], $_SESSION['deposit-amount'], $_SESSION['deposit-date'], $_SESSION['deposit-note']);
			break;
		case 'deposit_delete':
			$curr_user->delete_deposit($_SESSION['deposit-id']);
			break;
		case 'transaction_submit':
			$user_amounts = array();
			$paid_by_id = get_user_amounts($user_amounts);
			if (array_key_exists('trans-repeat', $_SESSION) && $_SESSION['trans-repeat'] === 'yes') {
				$curr_user->make_repeated_transaction($_SESSION['trans-name'], $_SESSION['trans-total-amount'], 
					$paid_by_id, $user_amounts, $_SESSION['trans-date'], $_SESSION['trans-end-date'], 
					$_SESSION['trans-interval-num'], $_SESSION['trans-interval-unit'], $_SESSION['trans-note']);
			} else {
				$curr_user->make_single_transaction($_SESSION['trans-name'], $_SESSION['trans-total-amount'], 
					$paid_by_id, $user_amounts, $_SESSION['trans-date'], $_SESSION['trans-note']);				
			}
			break;
		case 'transaction_edit':
			$user_amounts = array();
			$paid_by_id = get_user_amounts($user_amounts);
			if (array_key_exists('trans-repeat', $_SESSION) && $_SESSION['trans-repeat'] === 'yes') {
				$curr_user->edit_repeated_transaction($_SESSION['trans-id'], $_SESSION['trans-name'], 
					$_SESSION['trans-total-amount'], $paid_by_id, $user_amounts, $_SESSION['trans-date'],
					$_SESSION['trans-end-date'], $_SESSION['trans-interval-num'], $_SESSION['trans-interval-unit'], $_SESSION['trans-note']);
			} else {
				echo 'hi';
				echo $_SESSION['trans-id'];
				echo $_SESSION['trans-paid-by'];
				echo $_SESSION['trans-total-amount'];
				$curr_user->edit_single_transaction($_SESSION['trans-id'], $_SESSION['trans-name'], 
					$_SESSION['trans-total-amount'], $paid_by_id, $user_amounts, $_SESSION['trans-date'], $_SESSION['trans-note']);				
			}
			break;
		case 'transaction_delete':
			if ($_SESSION['trans-type'] === 's') {
				$curr_user->delete_single_transaction($_SESSION['trans-id']);
			} else {
				$curr_user->delete_repeated_transaction($_SESSION['trans-id']);
			}
			break;
		case 'register_user':
			register_user($_SESSION['register-first-name'], $_SESSION['register-last-name'], $_SESSION['register-email'], $_SESSION['register-password'], $mysqli, $config['db']['tables']['userinfo'], $config['db']['tables']['transactions_single'], $config['db']['tables']['transactions_repeated']);
			break;
		case 'login':
			$userinfo = login($_SESSION['login-email'], $_SESSION['login-password'], $mysqli, $config['db']['tables']['userinfo']);
		
			session_unset();
			if ($userinfo) { // logged in
				$_SESSION['user_id'] = $userinfo['id'];
				
				header('HTTP/1.1 303 See Other');
		    	header('Location: balance.php');
				exit;
			} else { // incorrect password
				$wrong_login = 1;
			}
			break;
	}
	if (array_key_exists('submission', $_SESSION)) {
		session_unset();
		$_SESSION['user_id'] = $user_id;
	}
}

function register_user($first_name, $last_name, $email, $password, $db, $user_table, $trans_s_table, $trans_r_table) {
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
	$query = sprintf('ALTER TABLE %s ADD COLUMN user_%d_amount FLOAT(12, 4) DEFAULT 0;', $trans_r_table, $new_id);
	$db->query($query);	
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
	global $id_to_user;
	$paid_by_id = 0;
	foreach ($id_to_user as $id => $name) {
		$key = 'user_' . $id . '_amount';
		if ($id == $_SESSION['trans-paid-by']) {
			$paid_by_id = $id;
		}
		$user_amounts[$key] = $_SESSION[$key];
	}
	return $paid_by_id;
}

?>
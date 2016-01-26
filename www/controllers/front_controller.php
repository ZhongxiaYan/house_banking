<?php

require_once dirname(__FILE__) . '/../../lib/config.php';
require_once $PAGES['util'];
require_once "$LIB/classes/user.php";

// general set up
date_default_timezone_set('America/Los_Angeles');
if (session_id() == '') {
    session_start();
}

if (isset($page_name)) { // set the controller to the $page_name that required this file
    set_session_if_none('controller', $page_name);
    set_session_if_none('action', 'view');  
}

$mysqli = mysqli_connect($CONFIG['db']['host'], $CONFIG['db']['username'], $CONFIG['db']['password'], $CONFIG['db']['dbname']); // TODO switch?
if ($mysqli->connect_error) {
    die('Connection failed: ' . $mysqli->connect_error);
}


// Post/Redirect/Get structure for form submission: move all $_POST into $_SESSION for further processing
if (array_key_exists('submission', $_GET)) { // submitted some form, redirect to the same page
    $_SESSION['action'] = $_GET['submission'];
    foreach ($_POST as $key => $value) {
        if (array_key_exists($key, $SESSION_LOGIN_VARS)) { // do not let $_POST set the session log in variables
            continue;
        }
        if ((!array_key_exists('session-token', $_POST) || $_POST['session-token'] !== $_SESSION['user_session_token'])
                                 && get_session('controller') !== 'login') { // prevent man in the middle attack by logging out
            clear_session();
            redirect($PAGES['login'] . '?submission=logout');
            exit;
        }
        $_SESSION[$key] = $value;
    }
    unset($_GET['submission']);
    $curr_page = $page;
    if (count($_GET) > 0) {
        $redirect_page = $curr_page . '?' . http_build_query($_GET);
    } else {
        $redirect_page = $curr_page;
    }
    redirect($redirect_page);
    exit;
}


// get controller, action, and status from $_SESSION
$controller_name = pop_session('controller');
$action = pop_session('action');
$status = pop_session('status');


// query all the accounts
$all_users = array();
$current_users = array();
$curr_user = null;
$view_user = null;

$users_sql = $mysqli->query(sprintf('SELECT * FROM %s;', $CONFIG['db']['tables']['userinfo']));
while ($user_info = $users_sql->fetch_assoc()) {
    $user = new User(
        $user_info['id'], 
        sprintf('%s %s', $user_info['first_name'], $user_info['last_name']),
        $user_info['email'],
        $user_info['pass_salt_hash'],
        $user_info['verified'],
        $user_info['admin'],
        $user_info['deleted']
    );
    $all_users[$user_info['id']] = $user;
    if ($user->is_verified) {
        $current_users[$user_info['id']] = $user;
    }
}


$controller = null;
// see if current user is logged in
if (array_key_exists('user_id', $_SESSION) && array_key_exists('user_session_token', $_SESSION)) { // logged in
    $user_id = $_SESSION['user_id'];
    $user_session_token = $_SESSION['user_session_token'];

    // somehow current user is not a user at all...
    if (!array_key_exists($user_id, $all_users)) {
        clear_session();
        set_session('status', 'id_not_exists');
        if ($controller_name !== 'error') {
            redirect($PAGES['error']);
            exit;
        }
    } else {
        $curr_user = $all_users[$user_id];
    }

    // check if $curr_user is still an active user
    $view_user = $curr_user;
    if ($curr_user !== null && !$curr_user->is_active) { // verified and not deleted
        $status;
        if ($curr_user->is_deleted) {
            $status = 'deleted';
        } else {
            $status = 'unverified';
        }
        clear_session();
        set_session('status', $status);
        set_session('action', 'logout');
        // redirect if not already at login
        if ($controller_name !== 'login') {        
            redirect($PAGES['login']);
            exit;
        }
        $curr_user = null;
    } else if ($curr_user !== null) {
        if ($curr_user->is_admin && array_key_exists('user', $_GET)) {
            $view_user = $all_users[$_GET['user']];
        }
        // nonadmins can't visit admin.php
        if (!$curr_user->is_admin && $controller_name === 'admin') {
            redirect($PAGES['index']);
            exit;
        }
    }
} else { // not logged in
    switch ($controller_name) {
        case 'login':
        case 'register':
            break;
        default: // redirect to login otherwise
            clear_session();
            set_session('status', 'not_logged_in');
            redirect($PAGES['login']);
            exit;
    }
}

if (in_array($action, $CONTROLLER_TO_ACTIONS[$controller_name])) {
    require_once "$WWW/controllers/${controller_name}_controller.php";
    $class_name = ucfirst($controller_name . 'Controller');
    $controller = new $class_name($mysqli, $all_users, $current_users, $curr_user, $view_user);
} else {
    clear_session();
    set_session('status', 'no_action');
    redirect($PAGES['error']);
    exit;
}

$controller->execute($action, $_SESSION, $status);

clear_session();

?>
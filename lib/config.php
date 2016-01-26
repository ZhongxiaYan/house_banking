<?php

$env = getenv('CLEARDB_DATABASE_URL');
if (!$env) {
    $env = getenv('DATABASE_URL');
}
$url = parse_url($env);
$CONFIG = array(
    'db' => array(
        'dbname' => substr($url['path'], 1),
        'username' => $url['user'],
        'password' => $url['pass'],
        'host' => $url['host'],
        'tables' => array(
            'userinfo' => 'userinfo',
            'deposits' => 'userpaymenthistory',
            'transactions_single' => 'transactions',
            'transactions_repeated' => 'repeatedtransactions',
            'cellinfo' => 'cellinfo',
            'register_codes' => 'registercodes'
        )
    ),
    'paths' => array(
        'resources' => '/house_banking/resources',
        'images' => '/house_banking/public_html/img',
        'word_list' => '../lib/words.txt'
    )
);

ini_set('error_reporting', 'true');
error_reporting(E_ALL|E_STRCT);

// never copy these into $_SESSION from $_POST
$SESSION_LOGIN_VARS = array(
    'user_session_token' => 1, 
    'user_id' => 1
);

$ROOT_DIR = __DIR__ . '/../';
$WWW = "$ROOT_DIR/www";
$LIB = "$ROOT_DIR/lib";

// map each controller to its possible actions
$CONTROLLER_TO_ACTIONS = array(
    'register' => ['view', 'register'],
    'login' => ['view', 'login', 'logout'],
    'index' => ['view', 'alter_table', 'check_table'],
    'balance' => ['view', 'deposit_add', 'deposit_edit', 'deposit_delete', 
                  'transaction_add', 'transaction_edit', 'transaction_delete', 
                  'get_deposit_ajax', 'get_transaction_ajax'],
    'admin' => ['view', 'verify_users', 'make_admins', 'delete_users', 'register_code'],
    'error' => ['view']
);

$PAGES = array(
    'front' => "$WWW/controllers/front_controller.php",
    'util' => "$LIB/util.php"
);
foreach ($CONTROLLER_TO_ACTIONS as $key => $value) { // add the links from $CONTROLLER_TO_ACTIONS
    $PAGES[$key] = "$key.php";
}

$NO_LOGIN_PAGES = array(
    'login',
    'register'
);

?>
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
            'userinfo' => 'UserInfo',
            'deposits' => 'UserPaymentHistory',
            'transactions_single' => 'Transactions',
            'transactions_repeated' => 'RepeatedTransactions',
            'cellinfo' => 'Cellinfo',
            'register_codes' => 'RegisterCodes',
            'recovery_codes' => 'RecoveryCodes'
        )
    ),
    'mailer' => array (
        'email' => getenv('HOUSE_EMAIL_ADDRESS'),
        'password' => getenv('HOUSE_EMAIL_PASSWORD')
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
$SRC = "$ROOT_DIR/src";
$LIB = "$ROOT_DIR/lib";

// map each controller to its possible actions
$CONTROLLER_TO_ACTIONS = array(
    'register' => ['view', 'register'],
    'login' => ['view', 'login', 'generate_code', 'recover', 'logout'],
    'index' => ['view', 'alter_table', 'check_table'],
    'balance' => ['view', 'deposit_add', 'deposit_edit', 'deposit_delete', 
                  'transaction_add', 'transaction_edit', 'transaction_delete', 
                  'get_deposit_ajax', 'get_transaction_ajax'],
    'admin' => ['view', 'verify_users', 'make_admins', 'delete_users', 'register_code'],
    'changeinfo' => ['view', 'change'],
    'error' => ['view']
);

$PAGES = array(
    'front' => "$SRC/controllers/front_controller.php",
    'util' => "$LIB/util.php",
    'autoload' => "$ROOT_DIR/vendor/autoload.php"
);
foreach ($CONTROLLER_TO_ACTIONS as $key => $value) { // add the links from $CONTROLLER_TO_ACTIONS
    $PAGES[$key] = "$key.php";
}

$NO_LOGIN_PAGES = array(
    'login',
    'register'
);

?>
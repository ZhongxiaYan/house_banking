<?php

$env = getenv('CLEARDB_DATABASE_URL');
if (!$env) {
	$env = getenv('DATABASE_URL');
}
$url = parse_url($env);
$config = array(
	'db' => array(
		'dbname' => substr($url['path'], 1),
		'username' => $url['user'],
		'password' => $url['pass'],
		'host' => $url['host'],
		'tables' => array(
			'userinfo' => 'userinfo',
			'deposits' => 'userpaymenthistory',
			'transactions_single' => 'transactions',
			'transactions_repeated' => 'repeatedtransactions'
		)
	),
	'paths' => array(
		'resources' => '/house_banking/resources',
		'images' => '/house_banking/public_html/img',
		'editable_table' => 'editable_table.ser',
		'register_codes' => 'register_codes.ser',
		'word_list' => '../lib/words.txt'
	)
);

ini_set('error_reporting', 'true');
error_reporting(E_ALL|E_STRCT);

?>
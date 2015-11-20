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

?>
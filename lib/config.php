<?php

$url = parse_url(getenv("CLEARDB_DATABASE_URL"));

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
		'editable_table' => 'editable_table.txt'
	)
);

ini_set('error_reporting', 'true');
error_reporting(E_ALL|E_STRCT);

?>
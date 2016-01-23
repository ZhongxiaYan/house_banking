<?php

require_once dirname(__FILE__) . '/../lib/config.php';

$page = basename($_SERVER['PHP_SELF']);
$page_name = pathinfo($_SERVER['PHP_SELF'], PATHINFO_FILENAME);
require_once $PAGES['front'];

?>

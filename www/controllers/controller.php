<?php

class LoginController {

	private $all_users;
	private $active_users;
	private $curr_user;
	private $db;
	private $status;
	private $session;
	public $page;

	public function __construct($db, $all_users, $active_users, $curr_user, $view_user) {
		$this->db = $db;
		$this->all_users = $all_users;
		$this->active_users = $active_users;
		$this->curr_user = $curr_user;
		$this->view_user = $view_user;
		$this->page = 'login.php';
	}

	public function execute($action, $session, $status) {
		$this->status = $status;
		$this->session = $session;
		$this->{ $action }(); // call the method corresponding to the name
	}
}

?>
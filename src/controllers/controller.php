<?php

class Controller {

	private $all_users;
	private $current_users;
	private $curr_user;
	private $db;
	private $status;
	private $session;
	public $page;

	public function __construct($db, $all_users, $current_users, $curr_user, $view_user) {
		$this->db = $db;
		$this->all_users = $all_users;
		$this->current_users = $current_users;
		$this->curr_user = $curr_user;
		$this->view_user = $view_user;
		$this->page = 'controller.php';
	}

	public function execute($action, $session, $status) {
		$this->status = $status;
		$this->session = $session;
		$this->{ $action }(); // call the method corresponding to the name
	}
}

?>
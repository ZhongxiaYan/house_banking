<?php
	
class ErrorController {

	public function __construct($db, $all_users, $current_users, $curr_user, $view_user) {
		$this->db = $db;
		$this->all_users = $all_users;
		$this->current_users = $current_users;
		$this->curr_user = $curr_user;
		$this->view_user = $view_user;
		$this->page = 'error.php';
	}

	public function execute($action, $session, $status) {
		$this->status = $status;
		$this->session = $session;
		$this->{ $action }(); // call the method corresponding to the name
	}

	public function view() {
		global $SRC;
		global $LIB;
		global $PAGES;

		$status = $this->status;
		$message = null;
		switch ($status) {
			case 'id_not_exists':
				$message = 'User ID does not exist';
				break;
			case 'no_action':
				$message = 'Action is not supported';
				break;
			default:
				$message = $status;
		}
		session_unset();

		require_once "$SRC/views/error_view.php";
	}
}

?>
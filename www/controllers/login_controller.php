<?php

class LoginController {

    private $all_users;
    private $active_users;
    private $curr_user;
    private $db;
    private $status;
    private $session;
    public $page;

    public function __construct($db, $all_users, $active_users, $curr_user, $view_user) { // TODO add inheritance
        $this->db = $db;
        $this->all_users = $all_users;
        $this->active_users = $active_users;
        $this->curr_user = $curr_user;
        $this->page = 'login.php';
    }

    public function execute($action, $session, $status) {
        $this->status = $status;
        $this->session = $session;
        $this->{ $action }(); // call the method corresponding to the name
    }

    public function login() {
        global $PAGES;

        // get log in information
        $email = $this->session['login-email'];
        $password = $this->session['login-password'];
        $curr_user = null;
        foreach ($this->all_users as $id => $user) {
            if ($user->email === $email) {
                $curr_user = $user;
                break;
            }
        }

        clear_session();
        if ($curr_user !== null && password_verify($password, $curr_user->pass_salt_hash)) { // password correct
            set_session('user_id', $curr_user->id);
            set_session('user_session_token', hash("sha512", mt_rand(0, mt_getrandmax())));
            set_session('status', 'just_logged_in');
            $this->curr_user = $curr_user;
        } else {
            $this->status = 'wrong_login';
        }
        $this->view();
    }

    public function logout() {
        $this->curr_user = null;
        $this->view();
    }

    public function view() {
        global $WWW;
        global $PAGES;

        if ($this->curr_user !== null) { // redirect to balance automatically if logged in
            redirect($PAGES['balance']);
        }
        session_unset();

        $page = $this->page;
        $status = $this->status;
        $logged_in = 0;
        $color = null;
        $message = null;
        switch ($status) {
            case null:
                break;
            case 'wrong_login':
                $color = 'red';
                $message = 'Incorrect Email or Password! Please try again.';
                break;
            case 'deleted':
                $color = 'red';
                $message = 'Account deleted! Please contact an admin.';
                break;
            case 'unverified':
                $color = 'red';
                $message = 'Account is not verified by admin yet.';
                break;
            case 'not_logged_in':
                $color = 'red';
                $message = 'Please log in before visiting any other page.';
                break;
            case 'not_admin':
                $color = 'red';
                $message = 'Page requires administrator privileges. Please log in as admin.';
                break;
            case 'just_registered':
                $color = 'green';
                $message = 'Successfully registered. Please log in.';
                break;
            default:
                $color = 'red';
                $message = 'Unknown status code ' . htmlspecialchars($status);
        }
        require_once "$WWW/views/login_view.php";
    }
}

?>
<?php

class LoginController {

    private $all_users;
    private $current_users;
    private $curr_user;
    private $db;
    private $status;
    private $session;
    public $page;

    public function __construct($db, $all_users, $current_users, $curr_user, $view_user) { // TODO add inheritance
        $this->db = $db;
        $this->all_users = $all_users;
        $this->current_users = $current_users;
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

    public function generate_code() {
        global $CONFIG;
        global $PAGES;
        global $SRC;
        global $LIB;

        $email = $this->session['login-email'];
        $curr_user = null;
        foreach ($this->all_users as $user) {
            if ($user->email === $email) {
                $curr_user = $user;
                break;
            }
        }

        clear_session();
        $rec_code_table = $CONFIG['db']['tables']['recovery_codes'];
        $return = array(); // to be turned into json and sent as response
        if ($curr_user != null) {
            $code = null;
            $query = sprintf('SELECT code FROM %s WHERE user_id=%s;', $rec_code_table, $curr_user->id);
            $result = $this->db->query($query);
            if ($entry = $result->fetch_assoc()) {
                $code = $entry['code'];
            } else {
                require_once "$LIB/util.php";
                $code = generate_random_word_comb();
                $query = sprintf('INSERT INTO %s (user_id, code) VALUES (%s, "%s");', $rec_code_table, $curr_user->id, $code);
                $this->db->query($query);
            }
            $recovery_string = "Please enter the following code to continue your recovery process: $code. If you did not initialize this process, please contact an admin.";
            email(array($curr_user), "House Banking Password Recovery", $recovery_string, $recovery_string);
            $return['success'] = '1';
        } else {
            $return['success'] = '0';
        }
        echo json_encode($return, JSON_FORCE_OBJECT); // should be in the view but wtvr
    }

    public function recover() {
        global $CONFIG;
        global $PAGES;
        global $SRC;
        global $LIB;

        $email = $this->session['login-email'];
        $entered_code = $this->session['recovery-code'];
        $curr_user = null;
        foreach ($this->all_users as $user) {
            if ($user->email === $email) {
                $curr_user = $user;
                break;
            }
        }

        clear_session();
        $rec_code_table = $CONFIG['db']['tables']['recovery_codes'];
        if ($curr_user != null) {
            $code = null;
            $query = sprintf('SELECT code FROM %s WHERE user_id=%s;', $rec_code_table, $curr_user->id);
            $result = $this->db->query($query);
            if ($entry = $result->fetch_assoc()) {
                $code = $entry['code'];
            }
            if ($entered_code === $code) {
                $user_table = $CONFIG['db']['tables']['userinfo'];
                $hashed_pass = password_hash($code, PASSWORD_DEFAULT);
                $query = sprintf('UPDATE %s SET pass_salt_hash="%s" WHERE id=%s;', $user_table, $hashed_pass, $curr_user->id);
                $this->db->query($query);
                $query = sprintf('DELETE FROM %s WHERE user_id=%s;', $rec_code_table, $curr_user->id);
                $this->db->query($query);
                $this->status = 'recovery_done';
            } else {
                $this->status = 'wrong_recovery';
            }
        } else {
            $this->status = 'wrong_recovery';
        }
        $this->view();
    }

    public function logout() {
        $this->curr_user = null;
        $this->view();
    }

    public function view() {
        global $SRC;
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
            case 'wrong_recovery':
                $color = 'red';
                $message = 'Email or code incorrect.';
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
            case 'recovery_done':
                $color = 'green';
                $message = 'Successfully recovered password. Please log in with the recovery code as password and edit the password upon login.';
                break;
            default:
                $color = 'red';
                $message = 'Unknown status code ' . htmlspecialchars($status);
        }
        require_once "$SRC/views/login_view.php";
    }
}

?>
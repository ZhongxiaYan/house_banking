<?php

class RegisterController {

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
        $this->page = 'register.php';
    }

    public function execute($action, $session, $status) {
        $this->status = $status;
        $this->session = $session;
        $this->{ $action }(); // call the method corresponding to the name
    }

    // return 1 if correct register code
    private function check_valid_register_code($register_code, $register_code_table) {
        $query = sprintf('SELECT * FROM %s WHERE code=?;', $register_code_table);
        $stmt = $this->db->prepare($query);
        if (!$stmt->bind_param('s', $register_code)) {
            echo 'Binding parameter failed: (' . $stmt->errno . ') ' . $stmt->error;
        }
        if (!$stmt->execute()) {
            echo 'Execution failed: (' . $stmt->errno . ') ' . $stmt->error;
        }
        $result = $stmt->get_result();
        if (!$result || $result->num_rows === 0) {
            return 0;
        }
        return 1;
    }

    private function check_email_available($email) {
        foreach ($this->all_users as $user) {
            if ($user->email === $email) {
                return 0;
            }
        }
        return 1;
    }

    public function register() {
        global $CONFIG;
        global $PAGES;

        if ($this->curr_user !== null) { // redirect to balance if logged in
            redirect($PAGES['balance']);
            exit;
        }

        $session = $this->session;
        $first_name = $session['register-first-name'];
        $last_name = $session['register-last-name'];
        $email = $session['register-email'];
        $password = $session['register-password'];
        $register_code = $session['register-code'];
        $register_code_table = $CONFIG['db']['tables']['register_codes'];
        
        clear_session();
        if ($this->check_valid_register_code($register_code, $register_code_table)) {
            if ($this->check_email_available($email)) {
                // add new user into userinfo table
                $user_table = $CONFIG['db']['tables']['userinfo'];
                $query = sprintf('INSERT INTO %s (first_name, last_name, email, pass_salt_hash) VALUES (?, ?, ?, ?);', $user_table);
                $stmt = $this->db->prepare($query);
                $hashed_pass = password_hash($password, PASSWORD_DEFAULT);
                if (!$stmt->bind_param('ssss', $first_name, $last_name, $email, $hashed_pass)) {
                    echo 'Binding parameter failed: (' . $stmt->errno . ') ' . $stmt->error;
                }
                if (!$stmt->execute()) {
                    echo 'Execution failed: (' . $stmt->errno . ') ' . $stmt->error;
                }

                // add new column to transactions table
                $new_id = $this->db->insert_id;
                $single_trans_table = $CONFIG['db']['tables']['transactions_single'];
                $query = sprintf('ALTER TABLE %s ADD COLUMN user_%d_amount FLOAT(12, 4) DEFAULT 0;', $single_trans_table, $new_id);
                $this->db->query($query);

                // add new column to repeatedtransactions table
                $repeated_trans_table = $CONFIG['db']['tables']['transactions_repeated'];
                $query = sprintf('ALTER TABLE %s ADD COLUMN user_%d_amount FLOAT(12, 4) DEFAULT 0;', $repeated_trans_table, $new_id);
                $this->db->query($query);
            
                // delete the register code that was used
                $query = sprintf('DELETE FROM %s WHERE code="%s";', $register_code_table, $register_code);
                $this->db->query($query);

                set_session('status', 'just_registered');
                redirect($PAGES['login']);
                exit;
            } else {
                $this->status = 'email_taken';
            }
        } else {
            $this->status = 'no_register_code';
        }
        $this->view();
    }

    private function view() {
        global $WWW;
        global $PAGES;

        if ($this->curr_user !== null) { // redirect to balance automatically if logged in
            redirect($PAGES['balance']);
            exit;
        }
        session_unset();

        $page = $this->page;
        $status = $this->status;
        $logged_in = 0;
        $message = null;
        switch ($status) {
            case null:
                break;
            case 'no_register_code':
                $message = 'Register code is incorrect! Please ask admin.';
                break;
            case 'email_taken':
                $message = 'Email is already taken! Please use another email.';
                break;
            default:
                $message = 'Unknown status code ' . htmlspecialchars($status);
        }
        require_once "$WWW/views/register_view.php";
    }
}

?>
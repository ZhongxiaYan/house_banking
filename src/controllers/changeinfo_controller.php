<?php

class ChangeinfoController {

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
        $this->page = 'change_info';
    }

    public function execute($action, $session, $status) {
        $this->status = $status;
        $this->session = $session;
        $this->{ $action }(); // call the method corresponding to the name
    }

    private function view() {
        global $SRC;
        global $PAGES;
        $curr_user = $this->curr_user;
        $view_user = $this->view_user;
        $all_users = $this->all_users;
        $user_session_token = $this->session['user_session_token'];
        $status = $this->status;
        $message = null;
        $color = null;
        $page = $this->page;
        $logged_in = 1;
        clear_session();
        switch ($status) {
            case null:
                break;
            case 'incorrect_password':
                $message = 'Incorrect password. Please retype current password to proceed.';
                $color = 'red';
                break;
            case 'success':
                $message = 'Successfully changed user information';
                $color = 'green';
                break;
            default:
                $message = 'Unknown error occured';
                $color = 'red';
        }
        require_once "$SRC/views/changeinfo_view.php";
    }

    private function change() {
        global $CONFIG;
        $curr_user = $this->curr_user;

        $session = $this->session;
        $new_first_name = $session['change-first-name'];
        $new_last_name = $session['change-last-name'];
        $new_email = $session['change-email'];
        $new_password = $session['change-password'];
        $current_password = $session['current-password'];
        if (!password_verify($current_password, $curr_user->pass_salt_hash)) {
            $this->status = 'incorrect_password';
        } else {
            $user_table = $CONFIG['db']['tables']['userinfo'];
            if ($new_password) {
                $hashed_pass = password_hash($new_password, PASSWORD_DEFAULT);
                $query = sprintf('UPDATE %s SET first_name=?, last_name=?, email=?, pass_salt_hash=? WHERE id=%s;', $user_table, $curr_user->id);
                $stmt = $this->db->prepare($query);
                if (!$stmt->bind_param('ssss', $new_first_name, $new_last_name, $new_email, $hashed_pass)) {
                    echo 'Binding parameter failed: (' . $stmt->errno . ') ' . $stmt->error;
                }
                if (!$stmt->execute()) {
                    echo 'Execution failed: (' . $stmt->errno . ') ' . $stmt->error;
                }
                $curr_user->pass_salt_hash = $hashed_pass;
            } else {
                $hashed_pass_phrase = '';
                $query = sprintf('UPDATE %s SET first_name=?, last_name=?, email=? WHERE id=%s;', $user_table, $curr_user->id);
                $stmt = $this->db->prepare($query);
                if (!$stmt->bind_param('sss', $new_first_name, $new_last_name, $new_email)) {
                    echo 'Binding parameter failed: (' . $stmt->errno . ') ' . $stmt->error;
                }
                if (!$stmt->execute()) {
                    echo 'Execution failed: (' . $stmt->errno . ') ' . $stmt->error;
                }
            }
            $this->status = 'success';
            $curr_user->first_name = $new_first_name;
            $curr_user->last_name = $new_last_name;
            $curr_user->name = sprintf('%s %s', $new_first_name, $new_last_name);
            $curr_user->email = $new_email;
            $this->curr_user = $curr_user;
            $this->all_users[$curr_user->id] = $curr_user;
            $this->current_users[$curr_user->id] = $curr_user;
        }
        $this->view();
    }
}

?>
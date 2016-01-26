<?php

class AdminController {

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
        $this->page = 'index.php';
    }

    public function execute($action, $session, $status) {
        $this->status = $status;
        $this->session = $session;
        $this->{ $action }(); // call the method corresponding to the name
    }

    public function view() {
        global $WWW;
        global $PAGES;
        global $CONFIG;

        clear_session();

        $page = $this->page;
        $user_session_token = $this->session['user_session_token'];
        $status = $this->status;
        $logged_in = 1;
        $message = null;
        $curr_user = $this->curr_user;
        $view_user = $this->view_user;
        $all_users = $this->all_users;

        // list the register codes
        $query = sprintf('SELECT * FROM %s;', $CONFIG['db']['tables']['register_codes']);
        $codes = $this->db->query($query)->fetch_all(MYSQLI_ASSOC);

        switch ($status) {
            case null:
                break;
            case 'changed_user':
                $message = 'Successfully changed user information.';
                break;
            case 'deleted_code':
                $message = 'Successfully deleted register code.';
                break;
            case 'generated_code':
                $message = 'Successfully added register code.';
                break;
            default:
                $message = 'Unknown status code ' . htmlspecialchars($status);
        }
        require_once "$WWW/views/admin_view.php";
    }

    private function get_changed_ids($session) {
        $changed_ids = array();
        foreach ($session as $key => $value) {
            if (preg_match('/^select(\d+)$/', $key, $match)) {
                $changed_ids[] = $match[1];
            }
        }
        return $changed_ids;
    }

    private function change_user_setting($column, $changed_ids) {
        if (sizeof($changed_ids) == 0) {
            return;
        }
        global $CONFIG;
        $user_table = $CONFIG['db']['tables']['userinfo'];
        $query_head = sprintf('UPDATE %s SET %s=1 WHERE id=?', $user_table, $column);
        $changed_ids_ref = array();
        $changed_ids_ref[0] = &$changed_ids[0];
        $parameter_str = 'i';
        for ($i = 1; $i < sizeof($changed_ids); $i++) { // we want to iterate the length - 1 times
            $query_head .= ' OR id=?'; // TODO make neater
            $parameter_str .= 'i';
            $changed_ids_ref[$i] = &$changed_ids[$i];
        }
        $query = $query_head . ';';

        $stmt = $this->db->prepare($query);
        $args = array_merge(array(&$parameter_str), $changed_ids_ref);
        if (!call_user_func_array(array($stmt, 'bind_param'), $args)) {
            echo 'Binding parameter failed: (' . $stmt->errno . ') ' . $stmt->error;
        }
        if (!$stmt->execute()) {
            echo 'Execution failed: (' . $stmt->errno . ') ' . $stmt->error;
        }

        // change $all_users and $current_users to reflect change
        $all_users = $this->all_users;
        $current_users = $this->current_users;
        foreach ($changed_ids as $id) {
            $user = $all_users[$id];
            $user->{"is_$column"} = 1;
            if ($column === 'verified') { // add to active if verify
                $current_users[$id] = $user;
            }
        }
        $this->all_users = $all_users;
        $this->current_users = $current_users;
    }

    private function verify_users() {
        $session = $this->session;
        $column = 'verified';
        $changed_ids = $this->get_changed_ids($session);
        $this->change_user_setting($column, $changed_ids);

        $this->status = 'changed_user';
        $this->view();
    }

    private function make_admins() {
        $session = $this->session;
        $column = 'admin';
        $changed_ids = $this->get_changed_ids($session);
        $this->change_user_setting($column, $changed_ids);

        $this->status = 'changed_user';
        $this->view();
    }

    private function delete_users() {
        $session = $this->session;
        $column = 'deleted';
        $changed_ids = $this->get_changed_ids($session);
        $this->change_user_setting($column, $changed_ids);

        $this->status = 'changed_user';
        $this->view();
    }

    private function register_code() {
        global $CONFIG;
        $session = $this->session;
        $register_code_table = $CONFIG['db']['tables']['register_codes'];

        if (array_key_exists('delete', $session)) {
            foreach ($session as $key => $value) {
                if (preg_match('/^select(\w+)$/', $key, $match)) {
                    $query = sprintf('DELETE FROM %s WHERE code=?;', $register_code_table);
                    $stmt = $this->db->prepare($query);
                    if (!$stmt->bind_param('s', $match[1])) {
                        echo 'Binding parameter failed: (' . $stmt->errno . ') ' . $stmt->error;
                    }
                    if (!$stmt->execute()) {
                        echo 'Execution failed: (' . $stmt->errno . ') ' . $stmt->error;
                    }
                }
            }
            $this->status = 'deleted_code';
        } else if (array_key_exists('generate', $session)) {
            $new_code = '';
            $lines = file($CONFIG['paths']['word_list']);
            for ($i = 0; $i < 5; $i++) {
                $rand = rand(0, 998);
                $new_code .= preg_replace('/\s+/S', "", $lines[$rand]);
            }
            $query = sprintf('INSERT INTO %s (code) VALUES ("%s");', $register_code_table, $new_code);
            $this->db->query($query);
            $this->status = 'generated_code';
        }
        $this->view();
    }
}

?>
<?php
    
class BalanceController {

    private $all_users;
    private $active_users;
    private $curr_user;
    private $view_user;
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
        $this->page = 'balance.php';
    }

    public function execute($action, $session, $status) {
        $this->status = $status;
        $this->session = $session;
        $this->{ $action }(); // call the method corresponding to the name
    }

    private function view() {
        global $WWW;
        global $LIB;
        global $PAGES;

        $page = $this->page;
        $current_date = date('Y-m-d');
        $logged_in = 1;
        $all_users = $this->all_users;
        $active_users = $this->active_users;
        $curr_user = $this->curr_user;
        $view_user = $this->view_user;
        $user_session_token = $this->session['user_session_token'];
        $db = $this->db;

        require_once "$LIB/util.php";
        require_once "$LIB/classes/deposit_table.php";
        require_once "$LIB/classes/transaction_table.php";
        require_once "$WWW/controllers/calculate_balance_table.php";
        require_once "$WWW/views/balance_view.php";
    }

    private function get_deposit_table() {
        global $LIB;
        require_once "$LIB/classes/deposit_table.php";
        return new DepositTable($this->db);
    }

    private function deposit_add() {
        $table = $this->get_deposit_table();
        $session = $this->session;

        $depositor_id = $this->view_user->id;
        $name = $session['deposit-name'];
        $amount = $session['deposit-amount'];
        $datetime = $session['deposit-date'];
        $note = $session['deposit-note'];
        $adder_id = $this->curr_user->id;
        $table->add_deposit($depositor_id, $name, $amount, $datetime, $note, $adder_id);

        clear_session();
        $this->view();
    }

    private function deposit_edit() {
        $table = $this->get_deposit_table();
        $session = $this->session;

        $deposit_id = $session['deposit-id'];
        $name = $session['deposit-name'];
        $amount = $session['deposit-amount'];
        $datetime = $session['deposit-date'];
        $note = $session['deposit-note'];
        $edittor_id = $this->curr_user->id;
        $table->edit_deposit($deposit_id, $name, $amount, $datetime, $note, $edittor_id);

        clear_session();
        $this->view();
    }

    private function deposit_delete() {
        $table = $this->get_deposit_table();
        $session = $this->session;

        $deposit_id = $session['deposit-id'];
        $table->delete_deposit($deposit_id);

        clear_session();
        $this->view();
    }

    private function get_transaction_table() {
        global $LIB;
        require_once "$LIB/classes/transaction_table.php";
        return new TransactionTable($this->db);
    }

    private function get_user_amounts(&$user_amounts) {
        $paid_by_id = 0;
        $session = $this->session;
        foreach ($this->active_users as $id => $user) { // TODO figure out what users
            $key = "user_${id}_amount";
            if ($id == $session['trans-paid-by']) {
                $paid_by_id = $id;
            }
            $user_amounts[$key] = $session[$key];
        }
        return $paid_by_id;
    }

    private function transaction_add() {
        $table = $this->get_transaction_table();
        $session = $this->session;

        $name = $session['trans-name'];
        $is_repeated = array_key_exists('trans-is-repeated', $session) && $session['trans-is-repeated'] === '1';
        $amount = $session['trans-total-amount'];
        $note = $session['trans-note'];
        $maker_id = $this->curr_user->id;

        $users_amounts = array();
        $paid_by_id = $this->get_user_amounts($users_amounts);

        if ($is_repeated) {
            $start_date = $session['trans-date'];
            $end_date = $session['trans-end-date'];
            $repeat_interval_unit = $session['trans-interval-unit'];
            $repeat_interval_num = $session['trans-interval-num'];

            $table->add_repeated_transaction($name, $amount, $paid_by_id, $users_amounts, $start_date, $end_date, $repeat_interval_num, $repeat_interval_unit, $note, $maker_id);
        } else {
            $datetime = $session['trans-date'];

            $table->add_single_transaction($name, $amount, $paid_by_id, $users_amounts, $datetime, $note, $maker_id);
        }

        clear_session();
        $this->view();        
    }

    private function transaction_edit() {
        $table = $this->get_transaction_table();
        $session = $this->session;

        $trans_id = $session['trans-id'];
        $name = $session['trans-name'];
        $is_repeated = array_key_exists('trans-is-repeated', $session) && $session['trans-is-repeated'] === '1';
        $amount = $session['trans-total-amount'];
        $note = $session['trans-note'];
        $edittor_id = $this->curr_user->id;

        $users_amounts = array();
        $paid_by_id = $this->get_user_amounts($users_amounts);

        if ($is_repeated) {
            $start_date = $session['trans-date'];
            $end_date = $session['trans-end-date'];
            $repeat_interval_unit = $session['trans-interval-unit'];
            $repeat_interval_num = $session['trans-interval-num'];

            $table->edit_repeated_transaction($trans_id, $name, $amount, $paid_by_id, $users_amounts, $start_date, $end_date, $repeat_interval_num, $repeat_interval_unit, $note, $edittor_id); // TODO need to edit all users or just the ones given
        } else {
            $datetime = $session['trans-date'];

            $table->edit_single_transaction($trans_id, $name, $amount, $paid_by_id, $users_amounts, $datetime, $note, $edittor_id);
        }

        clear_session();
        $this->view();        
    }


    private function transaction_delete() {
        $table = $this->get_transaction_table();
        $session = $this->session;

        $transaction_id = $session['trans-id'];
        $is_repeated = $session['trans-is-repeated'];
        if ($is_repeated) {
            $table->delete_repeated_transaction($transaction_id);
        } else {
            $table->delete_single_transaction($transaction_id);
        }

        clear_session();
        $this->view();        
    }

    //         case 'transaction_edit':
    //             $user_amounts = array();
    //             $paid_by_id = get_user_amounts($user_amounts);
    //             if (array_key_exists('trans-repeat', $_SESSION) && $_SESSION['trans-repeat'] === 'yes') {
    //                 $view_user->edit_repeated_transaction($_SESSION['trans-id'], $_SESSION['trans-name'], 
    //                     $_SESSION['trans-total-amount'], $paid_by_id, $user_amounts, $_SESSION['trans-date'],
    //                     $_SESSION['trans-end-date'], $_SESSION['trans-interval-num'], $_SESSION['trans-interval-unit'], $_SESSION['trans-note'], $curr_user->id);
    //             } else {
    //                 $view_user->edit_single_transaction($_SESSION['trans-id'], $_SESSION['trans-name'], 
    //                     $_SESSION['trans-total-amount'], $paid_by_id, $user_amounts, $_SESSION['trans-date'], $_SESSION['trans-note'], $curr_user->id);             
    //             }
    //             break;
    //         case 'transaction_delete':
    //             if ($_SESSION['trans-type'] === 's') {
    //                 $view_user->delete_single_transaction($_SESSION['trans-id']);
    //             } else {
    //                 $view_user->delete_repeated_transaction($_SESSION['trans-id']);
    //             }
    //             break;
}

?>
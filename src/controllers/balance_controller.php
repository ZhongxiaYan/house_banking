<?php
    
class BalanceController {

    private $all_users;
    private $current_users;
    private $curr_user;
    private $view_user;
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
        $this->page = 'balance.php';
    }

    public function execute($action, $session, $status) {
        $this->status = $status;
        $this->session = $session;
        $this->{ $action }(); // call the method corresponding to the name
    }

    private function view() {
        global $SRC;
        global $LIB;
        global $PAGES;

        $page = $this->page;
        $current_date = date('Y-m-d');
        $logged_in = 1;
        $all_users = $this->all_users;
        $current_users = $this->current_users;
        $curr_user = $this->curr_user;
        $view_user = $this->view_user;
        $user_session_token = $this->session['user_session_token'];
        $db = $this->db;

        require_once "$LIB/util.php";
        require_once "$SRC/models/deposit_table.php";
        require_once "$SRC/models/transaction_table.php";
        require_once "$SRC/views/calculate_balance_table.php";
        require_once "$SRC/views/balance_view.php";
    }

    private function get_user_amounts($session) {
        $user_amounts = array();
        foreach ($this->current_users as $id => $user) {
            $key = "user_${id}_amount";
            $user_amounts[$key] = $session[$key];
        }
        return $user_amounts;
    }

    private function get_deposit_table() {
        global $SRC;
        require_once "$SRC/models/deposit_table.php";
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

    private function get_deposit_ajax() {
        $table = $this->get_deposit_table();
        $session = $this->session;
        $deposit_id = $session['deposit-id'];

        $deposit = $table->get_deposit($deposit_id);
        $deposit['date'] = date('Y-m-d', strtotime($deposit['action_time']));
        $deposit['success'] = '1';
        
        clear_session();
        echo json_encode($deposit, JSON_FORCE_OBJECT);
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
        global $SRC;
        require_once "$SRC/models/transaction_table.php";
        return new TransactionTable($this->db);
    }

    private function get_transaction_ajax() {
        $table = $this->get_transaction_table();
        $session = $this->session;
        $trans_id = $session['trans-id'];
        $is_repeated = array_key_exists('trans-is-repeated', $session) && $session['trans-is-repeated'] === '1';

        $transaction = $table->get_transaction($trans_id, $is_repeated);
        $transaction['is_repeated'] = $is_repeated ? '1' : '0';
        if ($is_repeated) {
            $transaction['date'] = date('Y-m-d', strtotime($transaction['start_date']));
            $transaction['end_date'] = date('Y-m-d', strtotime($transaction['end_date']));
        } else {            
            $transaction['date'] = date('Y-m-d', strtotime($transaction['action_time']));
        }
        $transaction['success'] = '1';
        
        clear_session();
        echo json_encode($transaction, JSON_FORCE_OBJECT);
    }

    private function transaction_add() {
        $table = $this->get_transaction_table();
        $session = $this->session;

        $name = $session['trans-name'];

        $is_repeated = array_key_exists('trans-is-repeated', $session) && $session['trans-is-repeated'] === '1';

        $note = $session['trans-note'];
        $maker_id = $this->curr_user->id;

        
        $user_amounts = $this->get_user_amounts($session);

        if ($is_repeated) {
            $start_date = $session['trans-date'];
            $end_date = $session['trans-end-date'];
            $repeat_interval_unit = $session['trans-interval-unit'];
            $repeat_interval_num = $session['trans-interval-num'];

            $table->add_repeated_transaction($name, $user_amounts, $start_date, $end_date, $repeat_interval_num, $repeat_interval_unit, $note, $maker_id);
        } else {
            $datetime = $session['trans-date'];

            $table->add_single_transaction($name, $user_amounts, $datetime, $note, $maker_id);
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
        $note = $session['trans-note'];
        $edittor_id = $this->curr_user->id;

        $user_amounts = $this->get_user_amounts($session);

        if ($is_repeated) {
            $start_date = $session['trans-date'];
            $end_date = $session['trans-end-date'];
            $repeat_interval_unit = $session['trans-interval-unit'];
            $repeat_interval_num = $session['trans-interval-num'];

            $table->edit_repeated_transaction($trans_id, $name, $user_amounts, $start_date, $end_date, $repeat_interval_num, $repeat_interval_unit, $note, $edittor_id); // TODO need to edit all users or just the ones given
        } else {
            $datetime = $session['trans-date'];

            $table->edit_single_transaction($trans_id, $name, $user_amounts, $datetime, $note, $edittor_id);
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
}

?>
<?php

class IndexController {

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

    /**
     *  @return a list of rows with data, each row mapping to a map of column to value
     */
    private function get_cells() {
        global $CONFIG;
        $query = sprintf('SELECT * FROM %s ORDER BY row_index', $CONFIG['db']['tables']['cellinfo']);
        $cells_sql = $this->db->query($query);
        $cell_map = array();
        if ($cells_sql && $cell = $cells_sql->fetch_assoc()) { // if any data at all
            $curr_row = $cell['row_index'];
            $curr_row_array = array($cell['column_index'] => $cell['val']);
            while ($cell = $cells_sql->fetch_assoc()) {
                if ($cell['row_index'] !== $curr_row) { // encounter new row, start a new array
                    $cell_map[$curr_row] = $curr_row_array;
                    $curr_row_array = array();
                    $curr_row = $cell['row_index'];
                }
                $curr_row_array[$cell['column_index']] = $cell['val'];
            }
            $cell_map[$curr_row] = $curr_row_array;
        }
        return $cell_map;
            
    }

    /**
     *  count dimension of the $cell_map
     */
    private function get_dimensions($cell_map, &$rows, &$columns) {        
        $rows = 0;
        $columns = 0;
        if (is_array($cell_map)) {
            foreach ($cell_map as $key => $value) {
                $rows = max($rows, $key + 1);
                $columns = max($columns, max(array_keys($value)) + 1);
            }
        }
    }

    /** 
     *  @param $cell_map  same structure as returned by get_cells
     *  @return           a 2 dimensional table of values
     */
    private function convert_cells_to_table($cell_map, $rows, $columns) {
        $table = array();
        for ($y = 0; $y < $rows; $y++) {
            $new_row = array();
            if (isset($cell_map[$y])) {
                $row = $cell_map[$y];
            } else {
                $row = array();
            }
            for ($x = 0; $x < $columns; $x++) {
                $str = isset($row[$x]) ? $row[$x] : '';
                $new_row[] = $str;
            }
            $table[] = $new_row;
        }
        return $table;
    }

    private function are_cell_maps_same($A, $B) {
        if (count($A) !== count($B)) {
            return false;
        }
        foreach ($A as $row_index => $row) {
            if (count($row) !== count($B[$row_index])) {
                return false;
            }
            foreach ($row as $column_index => $data) {
                if ($B[$row_index][$column_index] !== $data) {
                    return false;
                }
            }
        }
        return true;
    }

    /**
     *  update the database to reflect $cell_map
     */
    private function update_editable_table($cell_map) {
        global $CONFIG;
        // delete the old table
        $cell_info_table = $CONFIG['db']['tables']['cellinfo'];
        $query = sprintf('DELETE FROM %s;', $cell_info_table);
        $this->db->query($query);

        if (count($cell_map) > 0) { // new table not empty
            // write the new table
            $query = sprintf('INSERT INTO %s (row_index, column_index, val) VALUES ', $cell_info_table);
            $first = 1;

            $parameter_str = '';
            $temp_arr = array('', '', '');
            $args = array(&$parameter_str);
            $count = 0;
            foreach ($cell_map as $row_index => $row) {
                foreach ($row as $column_index => $val) {
                    if ($first === 0) {
                        $query .= ', ';
                    }
                    $first = 0;
                    $query .= '(?, ?, ?)';
                    $temp_arr[$count] = $row_index;
                    $temp_arr[$count + 1] = $column_index;
                    $temp_arr[$count + 2] = $val;
                    $args[] = &$temp_arr[$count];
                    $args[] = &$temp_arr[$count + 1];
                    $args[] = &$temp_arr[$count + 2];
                    $parameter_str .= 'sss';
                    $count += 3;
                }
            }
            $query .= ';';
            $stmt = $this->db->prepare($query);
            if (!call_user_func_array(array($stmt, 'bind_param'), $args)) {
                echo 'Binding parameter failed: (' . $stmt->errno . ') ' . $stmt->error;
            }
            if (!$stmt->execute()) {
                echo 'Execution failed: (' . $stmt->errno . ') ' . $stmt->error;
            }            
        }
    }

    private function view() {
        global $LIB;
        global $SRC;
        global $PAGES;
        global $CONFIG;


        $page = $this->page;
        $user_session_token = $this->session['user_session_token'];
        $status = $this->status;
        $logged_in = 1;
        $db = $this->db;
        $curr_user = $this->curr_user;
        $view_user = $this->view_user;
        $all_users = $this->all_users;
        $current_users = $this->current_users;
        ksort($current_users);

        $cell_map = $this->get_cells();
        $rows = 0;
        $columns = 0;
        $this->get_dimensions($cell_map, $rows, $columns); // get the actual dimensions
        $editable_table = $this->convert_cells_to_table($cell_map, $rows, $columns);

        require_once "$SRC/models/deposit_table.php";
        require_once "$SRC/models/transaction_table.php";        
        require_once "$SRC/views/calculate_house_table.php";
        require_once "$SRC/views/index_view.php";
        
        clear_session();
    }

    /**
     *  updated from ajax
     */
    private function alter_table() {
        $session = $this->session;

        $cell_map = $this->get_cells();
        $rows = 0;
        $columns = 0;
        $this->get_dimensions($cell_map, $rows, $columns); // get the actual dimensions

        // check if the data has be changed by others by seeing if the original data on the page
        // is the same as the current data on the server
        $before_changes = array_key_exists('before', $session) ? $session['before'] : array();
        $same = $this->are_cell_maps_same($cell_map, $before_changes);
        $return = array(); // to be turned into json and sent as response
        if ($same) {
            $after_changes = array_key_exists('after', $session) ? $session['after'] : array();
            $this->update_editable_table($after_changes);
            $return['success'] = '1';
        } else {
            $return['reference'] = $cell_map;
            $return['success'] = '0';
        }
        clear_session();
        echo json_encode($return, JSON_FORCE_OBJECT); // should be in the view but wtvr
    }

    /**
     *  send the current cell map as reference for ajax
     */
    private function check_table() {
        $cell_map = $this->get_cells();
        $rows = 0;
        $columns = 0;
        $this->get_dimensions($cell_map, $rows, $columns); // get the actual dimensions

        $return['reference'] = $cell_map;
        clear_session();
        echo json_encode($return, JSON_FORCE_OBJECT); // should be in the view but wtvr
    }

}

?>